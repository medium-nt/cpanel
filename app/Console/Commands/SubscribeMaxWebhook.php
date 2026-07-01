<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SubscribeMaxWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'max:subscribe-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Подписать webhook MAX на входящие события (запускать один раз после деплоя на прод)';

    /**
     * Регистрирует URL вебхука в MAX Bot API (POST /subscriptions).
     */
    public function handle(): int
    {
        $token = config('services.max.token');
        $apiUrl = config('services.max.api_url');
        $webhookUrl = config('services.max.webhook_url');

        if (! $token || ! $webhookUrl) {
            $this->error('MAX_BOT_TOKEN или MAX_WEBHOOK_URL не заданы в .env');

            return self::FAILURE;
        }

        $response = Http::withHeaders(['Authorization' => $token])
            ->withOptions(['verify' => config('services.max.verify_ssl', true)])
            ->timeout(10)
            ->post($apiUrl.'/subscriptions', [
                'url' => $webhookUrl,
                'update_types' => ['message_created'],
            ]);

        if ($response->successful()) {
            $this->info('Webhook MAX подписан: '.$webhookUrl);
            $this->line($response->body());

            return self::SUCCESS;
        }

        $this->error('Ошибка подписки webhook MAX: HTTP '.$response->status());
        $this->line($response->body());

        return self::FAILURE;
    }
}
