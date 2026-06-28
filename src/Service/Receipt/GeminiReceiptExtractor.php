<?php

namespace App\Service\Receipt;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Extrae datos de tickets usando la API de visión de Google Gemini (free tier).
 */
final class GeminiReceiptExtractor implements ReceiptExtractorInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(GEMINI_API_KEY)%')]
        private readonly string $apiKey,
        #[Autowire('%env(GEMINI_MODEL)%')]
        private readonly string $model,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function extract(string $imageBytes, string $mimeType, array $categoryNames): ExtractedReceipt
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('GEMINI_API_KEY no está configurada.');
        }

        $catList = $categoryNames === [] ? '(no hay categorías)' : implode(', ', $categoryNames);
        $prompt = <<<TXT
            Extrae los datos del ticket de compra de la imagen. Responde SOLO con un JSON con esta forma exacta:
            {"amount": number, "date": "YYYY-MM-DD" o null, "merchant": string o null, "category": string o null, "confidence": number}
            - amount: el TOTAL pagado, en euros, con punto decimal (ej. 23.40).
            - date: la fecha del ticket como "YYYY-MM-DD", o null si no se distingue.
            - merchant: el nombre del comercio, o null.
            - category: elige EXACTAMENTE una de estas categorías si encaja, si no null: {$catList}.
            - confidence: tu confianza (0 a 1) en el importe extraído.
            TXT;

        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            rawurlencode($this->model),
        );

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'query' => ['key' => $this->apiKey],
                'json' => [
                    'contents' => [[
                        'parts' => [
                            ['inline_data' => ['mime_type' => $mimeType, 'data' => base64_encode($imageBytes)]],
                            ['text' => $prompt],
                        ],
                    ]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0,
                    ],
                ],
                'timeout' => 30,
            ]);
            $data = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->error('Gemini: petición falló: ' . $e->getMessage());
            throw new \RuntimeException('No se pudo contactar con el lector de tickets.', 0, $e);
        }

        if (isset($data['error'])) {
            $this->logger->error('Gemini: error API: ' . json_encode($data['error']));
            throw new \RuntimeException('El lector de tickets devolvió un error.');
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $json = json_decode($text, true);
        if (!is_array($json)) {
            $this->logger->error('Gemini: respuesta no-JSON: ' . $text);
            throw new \RuntimeException('No se entendió la respuesta del lector de tickets.');
        }

        return $this->toReceipt($json);
    }

    /**
     * @param array<string, mixed> $json
     */
    private function toReceipt(array $json): ExtractedReceipt
    {
        $amount = null;
        if (isset($json['amount']) && is_numeric($json['amount']) && (float) $json['amount'] > 0) {
            $amount = number_format((float) $json['amount'], 2, '.', '');
        }

        $date = null;
        if (!empty($json['date']) && is_string($json['date'])) {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', substr($json['date'], 0, 10));
            if ($parsed instanceof \DateTimeImmutable) {
                $date = $parsed;
            }
        }

        $merchant = (isset($json['merchant']) && is_string($json['merchant']) && trim($json['merchant']) !== '')
            ? mb_substr(trim($json['merchant']), 0, 255)
            : null;

        $category = (isset($json['category']) && is_string($json['category']) && trim($json['category']) !== '')
            ? trim($json['category'])
            : null;

        $confidence = (isset($json['confidence']) && is_numeric($json['confidence']))
            ? (float) $json['confidence']
            : 0.0;

        return new ExtractedReceipt($amount, $date, $merchant, $category, $confidence);
    }
}
