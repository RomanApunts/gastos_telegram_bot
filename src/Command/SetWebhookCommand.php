<?php

namespace App\Command;

use App\Service\Telegram\TelegramApi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Registra (o elimina) el webhook del bot en Telegram.
 * Ej: php bin/console app:bot:set-webhook https://tudominio.com/telegram/webhook
 */
#[AsCommand(
    name: 'app:bot:set-webhook',
    description: 'Registra el webhook del bot en Telegram (o lo elimina con --delete)',
)]
final class SetWebhookCommand extends Command
{
    public function __construct(
        private readonly TelegramApi $api,
        #[Autowire('%env(TELEGRAM_WEBHOOK_SECRET)%')]
        private readonly string $webhookSecret,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('url', InputArgument::OPTIONAL, 'URL pública HTTPS del webhook')
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Elimina el webhook en lugar de registrarlo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Comprobamos el token de paso.
        $me = $this->api->getMe();
        if (!($me['ok'] ?? false)) {
            $io->error('El token del bot no es válido: ' . ($me['description'] ?? 'respuesta inesperada'));

            return Command::FAILURE;
        }
        $io->writeln("Bot: @{$me['result']['username']}");

        if ($input->getOption('delete')) {
            $res = $this->api->deleteWebhook();
            $io->success('Webhook eliminado: ' . json_encode($res, JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $url = (string) $input->getArgument('url');
        if ($url === '') {
            $io->error('Indica la URL del webhook o usa --delete.');

            return Command::INVALID;
        }
        if (!str_starts_with($url, 'https://')) {
            $io->error('Telegram exige que el webhook sea HTTPS.');

            return Command::INVALID;
        }
        if ($this->webhookSecret === '' || str_contains($this->webhookSecret, 'cambia-esto')) {
            $io->warning('TELEGRAM_WEBHOOK_SECRET no está configurado con un valor seguro en .env.local.');
        }

        $res = $this->api->setWebhook($url, $this->webhookSecret);
        if ($res['ok'] ?? false) {
            $io->success("Webhook registrado en {$url}");

            return Command::SUCCESS;
        }

        $io->error('No se pudo registrar el webhook: ' . ($res['description'] ?? json_encode($res)));

        return Command::FAILURE;
    }
}
