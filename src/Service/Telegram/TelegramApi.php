<?php

namespace App\Service\Telegram;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Cliente mínimo de la Bot API de Telegram.
 */
final class TelegramApi
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(TELEGRAM_BOT_TOKEN)%')]
        private readonly string $token,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendMessage(string $chatId, string $text): void
    {
        try {
            $this->httpClient->request('POST', $this->endpoint('sendMessage'), [
                'json' => ['chat_id' => $chatId, 'text' => $text],
            ])->getStatusCode();
        } catch (\Throwable $e) {
            $this->logger->error('Telegram sendMessage falló: ' . $e->getMessage());
        }
    }

    public function sendPhoto(string $chatId, string $photoPath, ?string $caption = null): void
    {
        try {
            $fields = ['chat_id' => $chatId];
            if ($caption !== null && $caption !== '') {
                $fields['caption'] = $caption;
            }
            $fields['photo'] = DataPart::fromPath($photoPath);

            $form = new FormDataPart($fields);
            $this->httpClient->request('POST', $this->endpoint('sendPhoto'), [
                'headers' => $form->getPreparedHeaders()->toArray(),
                'body' => $form->bodyToIterable(),
            ])->getStatusCode();
        } catch (\Throwable $e) {
            $this->logger->error('Telegram sendPhoto falló: ' . $e->getMessage());
        }
    }

    /** Devuelve el file_path de Telegram para un file_id, o null. */
    public function getFilePath(string $fileId): ?string
    {
        try {
            $res = $this->httpClient->request('POST', $this->endpoint('getFile'), [
                'json' => ['file_id' => $fileId],
            ])->toArray(false);

            return $res['result']['file_path'] ?? null;
        } catch (\Throwable $e) {
            $this->logger->error('Telegram getFile falló: ' . $e->getMessage());

            return null;
        }
    }

    /** Descarga el contenido binario de un fichero por su file_path. */
    public function downloadFile(string $filePath): ?string
    {
        try {
            return $this->httpClient->request(
                'GET',
                "https://api.telegram.org/file/bot{$this->token}/{$filePath}",
            )->getContent();
        } catch (\Throwable $e) {
            $this->logger->error('Telegram downloadFile falló: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Envía un mensaje con teclado inline. Devuelve el message_id, o null.
     *
     * @param array<int, array<int, array{text: string, callback_data: string}>> $inlineKeyboard
     */
    public function sendMessageWithKeyboard(string $chatId, string $text, array $inlineKeyboard): ?int
    {
        try {
            $res = $this->httpClient->request('POST', $this->endpoint('sendMessage'), [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'reply_markup' => ['inline_keyboard' => $inlineKeyboard],
                ],
            ])->toArray(false);

            return $res['result']['message_id'] ?? null;
        } catch (\Throwable $e) {
            $this->logger->error('Telegram sendMessageWithKeyboard falló: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Edita un mensaje existente. Si $inlineKeyboard es null, se quitan los botones.
     *
     * @param array<int, array<int, array{text: string, callback_data: string}>>|null $inlineKeyboard
     */
    public function editMessageText(string $chatId, int $messageId, string $text, ?array $inlineKeyboard = null): void
    {
        try {
            $payload = ['chat_id' => $chatId, 'message_id' => $messageId, 'text' => $text];
            if ($inlineKeyboard !== null) {
                $payload['reply_markup'] = ['inline_keyboard' => $inlineKeyboard];
            }
            $this->httpClient->request('POST', $this->endpoint('editMessageText'), ['json' => $payload])->getStatusCode();
        } catch (\Throwable $e) {
            $this->logger->error('Telegram editMessageText falló: ' . $e->getMessage());
        }
    }

    /** Confirma la recepción de una pulsación de botón (quita el "reloj" de carga). */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): void
    {
        try {
            $payload = ['callback_query_id' => $callbackQueryId];
            if ($text !== null) {
                $payload['text'] = $text;
            }
            $this->httpClient->request('POST', $this->endpoint('answerCallbackQuery'), ['json' => $payload])->getStatusCode();
        } catch (\Throwable $e) {
            $this->logger->error('Telegram answerCallbackQuery falló: ' . $e->getMessage());
        }
    }

    public function sendDocument(string $chatId, string $filePath, string $filename, ?string $caption = null): void
    {
        try {
            $fields = ['chat_id' => $chatId];
            if ($caption !== null && $caption !== '') {
                $fields['caption'] = $caption;
            }
            $fields['document'] = DataPart::fromPath(
                $filePath,
                $filename,
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );

            $form = new FormDataPart($fields);
            $this->httpClient->request('POST', $this->endpoint('sendDocument'), [
                'headers' => $form->getPreparedHeaders()->toArray(),
                'body' => $form->bodyToIterable(),
            ])->getStatusCode();
        } catch (\Throwable $e) {
            $this->logger->error('Telegram sendDocument falló: ' . $e->getMessage());
        }
    }

    /** @return array<string, mixed> */
    public function setWebhook(string $url, string $secret): array
    {
        return $this->httpClient->request('POST', $this->endpoint('setWebhook'), [
            'json' => [
                'url' => $url,
                'secret_token' => $secret,
                'allowed_updates' => ['message', 'callback_query'],
                'drop_pending_updates' => true,
            ],
        ])->toArray(false);
    }

    /** @return array<string, mixed> */
    public function deleteWebhook(): array
    {
        return $this->httpClient->request('POST', $this->endpoint('deleteWebhook'))->toArray(false);
    }

    /** @return array<string, mixed> */
    public function getMe(): array
    {
        return $this->httpClient->request('GET', $this->endpoint('getMe'))->toArray(false);
    }

    private function endpoint(string $method): string
    {
        return "https://api.telegram.org/bot{$this->token}/{$method}";
    }
}
