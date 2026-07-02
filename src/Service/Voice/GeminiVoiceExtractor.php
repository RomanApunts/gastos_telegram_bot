<?php

namespace App\Service\Voice;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Interpreta notas de voz usando la API de Google Gemini (free tier):
 * transcribe el audio, decide si es gasto o ingreso y extrae los datos.
 */
final class GeminiVoiceExtractor implements VoiceExtractorInterface
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

    public function extract(
        string $audioBytes,
        string $mimeType,
        array $categoryNames,
        \DateTimeImmutable $today,
    ): ExtractedVoiceEntry {
        if ($this->apiKey === '') {
            throw new \RuntimeException('GEMINI_API_KEY no está configurada.');
        }

        $catList = $categoryNames === [] ? '(no hay categorías)' : implode(', ', $categoryNames);
        $todayStr = $today->format('Y-m-d');
        $prompt = <<<TXT
            Eres un asistente de finanzas personales. Escucha esta nota de voz en español y
            extrae el movimiento que describe. Responde SOLO con un JSON con esta forma exacta:
            {"type": "gasto" o "ingreso", "amount": number o null, "date": "YYYY-MM-DD" o null, "category": string o null, "description": string o null, "transcript": string, "confidence": number}
            Reglas:
            - transcript: transcribe literalmente lo que se dice.
            - type: "ingreso" si la persona RECIBE dinero (cobré, me ingresaron, me pagaron, nómina, devolución, me devolvieron, factura cobrada); "gasto" si GASTA o paga o compra. Si dudas, usa "gasto".
            - amount: el importe en euros con punto decimal (ej. 12.50). null si no se dice.
            - date: hoy es {$todayStr}. Resuelve expresiones relativas ("hoy", "ayer", "anteayer", "el lunes") a "YYYY-MM-DD". null si no se menciona ninguna fecha.
            - category: SOLO para gastos, elige EXACTAMENTE una de estas categorías si encaja, si no null: {$catList}. Para ingresos, siempre null.
            - description: concepto breve sin el importe (ej. "gasolina", "cena", "nómina"). null si no hay.
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
                            ['inline_data' => ['mime_type' => $mimeType, 'data' => base64_encode($audioBytes)]],
                            ['text' => $prompt],
                        ],
                    ]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0,
                    ],
                ],
                'timeout' => 45,
            ]);
            $data = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->error('Gemini voz: petición falló: ' . $e->getMessage());
            throw new \RuntimeException('No se pudo contactar con el intérprete de voz.', 0, $e);
        }

        if (isset($data['error'])) {
            $this->logger->error('Gemini voz: error API: ' . json_encode($data['error']));
            throw new \RuntimeException('El intérprete de voz devolvió un error.');
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $json = json_decode($text, true);
        if (!is_array($json)) {
            $this->logger->error('Gemini voz: respuesta no-JSON: ' . $text);
            throw new \RuntimeException('No se entendió la respuesta del intérprete de voz.');
        }

        return $this->toEntry($json);
    }

    /**
     * @param array<string, mixed> $json
     */
    private function toEntry(array $json): ExtractedVoiceEntry
    {
        $type = (isset($json['type']) && $json['type'] === ExtractedVoiceEntry::TYPE_INCOME)
            ? ExtractedVoiceEntry::TYPE_INCOME
            : ExtractedVoiceEntry::TYPE_EXPENSE;

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

        $category = ($type === ExtractedVoiceEntry::TYPE_EXPENSE
            && isset($json['category']) && is_string($json['category']) && trim($json['category']) !== '')
            ? trim($json['category'])
            : null;

        $description = (isset($json['description']) && is_string($json['description']) && trim($json['description']) !== '')
            ? mb_substr(trim($json['description']), 0, 255)
            : null;

        $transcript = (isset($json['transcript']) && is_string($json['transcript']) && trim($json['transcript']) !== '')
            ? mb_substr(trim($json['transcript']), 0, 500)
            : null;

        $confidence = (isset($json['confidence']) && is_numeric($json['confidence']))
            ? (float) $json['confidence']
            : 0.0;

        return new ExtractedVoiceEntry($type, $amount, $date, $category, $description, $transcript, $confidence);
    }
}
