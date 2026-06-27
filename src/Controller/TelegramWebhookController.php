<?php

namespace App\Controller;

use App\Telegram\UpdateProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TelegramWebhookController extends AbstractController
{
    public function __construct(
        private readonly UpdateProcessor $processor,
        #[Autowire('%env(TELEGRAM_WEBHOOK_SECRET)%')]
        private readonly string $webhookSecret,
    ) {
    }

    #[Route('/telegram/webhook', name: 'telegram_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $header = (string) $request->headers->get('X-Telegram-Bot-Api-Secret-Token', '');
        if ($this->webhookSecret === '' || !hash_equals($this->webhookSecret, $header)) {
            return new Response('forbidden', Response::HTTP_FORBIDDEN);
        }

        $update = json_decode($request->getContent(), true);
        if (is_array($update)) {
            $this->processor->process($update);
        }

        // Siempre 200 para que Telegram no reintente el mismo update.
        return new Response('ok');
    }
}
