<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'pwa:generate-vapid-keys';
    protected $description = 'Generate VAPID keys untuk Web Push Notification';

    public function handle()
    {
        $this->info('Generating VAPID keys...');

        try {
            $keys = VAPID::createVapidKeys();

            $this->info('');
            $this->info('=======================================');
            $this->info('VAPID Keys berhasil di-generate!');
            $this->info('=======================================');
            $this->info('');
            $this->info('Public Key:');
            $this->line($keys['publicKey']);
            $this->info('');
            $this->info('Private Key:');
            $this->line($keys['privateKey']);
            $this->info('');
            $this->info('Tambahkan ke file .env:');
            $this->line("VAPID_PUBLIC_KEY={$keys['publicKey']}");
            $this->line("VAPID_PRIVATE_KEY={$keys['privateKey']}");
            $this->line("VAPID_SUBJECT=mailto:digitaldesagaluga@gmail.com");
            $this->info('');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
