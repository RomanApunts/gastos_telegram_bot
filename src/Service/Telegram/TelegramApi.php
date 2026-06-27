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

    /** @return array<string, mixed> */
    public function setWebhook(string $url, string $secret): array
    {
        return $this->httpClient->request('POST', $this->endpoint('setWebhook'), [
            'json' => [
                'url' => $url,
                'secret_token' => $secret,
                'allowed_updates' => ['message'],
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
