<?php

namespace App\Console\Commands;

use App\Models\TechnicianGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class TestTelegramBotCommand extends Command
{
    protected $signature = 'telegram:test 
                            {--chat_id= : Custom Chat ID to send test message to}
                            {--topic_id= : Custom Topic ID / Thread ID for forum supergroups}
                            {--group= : Technician Group ID or Code to test (e.g. 1 or "TEAM MTC 1")}';

    protected $description = 'Uji koneksi bot, verifikasi token, dan kirim pesan uji coba ke grup Telegram atau grup teknisi';

    public function handle(): int
    {
        $this->newLine();
        $this->info('========================================================');
        $this->info('   PENGUJIAN BOT TELEGRAM - CENTRAL TICKET SYSTEM       ');
        $this->info('========================================================');
        $this->newLine();

        $botToken = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');
        $chatId   = $this->option('chat_id');
        $topicId  = $this->option('topic_id');
        $targetGroupName = 'Global Fallback Group';

        // Check if testing a specific technician group
        if ($this->option('group')) {
            $groupQuery = $this->option('group');
            $group = TechnicianGroup::where('id', $groupQuery)
                ->orWhere('code', $groupQuery)
                ->orWhere('name', $groupQuery)
                ->first();

            if (!$group) {
                $this->error("[-] Grup Teknisi '{$groupQuery}' tidak ditemukan di database.");
                return self::FAILURE;
            }

            $chatId = $group->telegram_chat_id;
            $topicId = $group->telegram_topic_id;
            $targetGroupName = "{$group->name} ({$group->code})";

            $this->line("<fg=cyan>[*] Menguji Grup Teknisi: {$targetGroupName}</>");
        }

        if (empty($chatId)) {
            $chatId = config('services.telegram.chat_id') ?: env('TELEGRAM_GROUP_CHAT_ID');
        }

        $this->table(
            ['Parameter', 'Status / Nilai'],
            [
                ['TELEGRAM_BOT_TOKEN', !empty($botToken) ? substr($botToken, 0, 10) . '...' . substr($botToken, -5) : '<fg=red>Belum Diatur</>'],
                ['TARGET GROUP / NAMA', $targetGroupName],
                ['TELEGRAM CHAT ID', !empty($chatId) ? $chatId : '<fg=red>Belum Diatur</>'],
                ['TELEGRAM TOPIC ID', !empty($topicId) ? "#{$topicId}" : '<fg=gray>None</>'],
            ]
        );

        if (empty($botToken)) {
            $this->newLine();
            $this->error('[-] Error: TELEGRAM_BOT_TOKEN belum diisi di file .env');
            $this->warn('    Dapatkan token dari @BotFather dan masukkan ke TELEGRAM_BOT_TOKEN=.env');
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('<fg=yellow>1. Memeriksa identitas bot via getMe...</>');

        try {
            $res = Http::withoutVerifying()->timeout(5)->get("https://api.telegram.org/bot{$botToken}/getMe");
            $data = $res->json();

            if (!$res->successful() || empty($data['ok'])) {
                $this->error('[-] Gagal memverifikasi token bot Telegram!');
                $this->error('    Respon API: ' . ($data['description'] ?? $res->body()));
                return self::FAILURE;
            }

            $botUser = $data['result'];
            $this->info("[+] Bot Valid! Nama: {$botUser['first_name']} | Username: @{$botUser['username']} (ID: {$botUser['id']})");
        } catch (Throwable $e) {
            $this->error("[-] Gagal menghubungi API Telegram: {$e->getMessage()}");
            return self::FAILURE;
        }

        if (empty($chatId)) {
            $this->newLine();
            $this->warn('[!] Chat ID belum diatur. Gunakan --chat_id= atau --group=');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->line("<fg=yellow>2. Mengirim pesan uji coba ke: {$chatId} (Target: {$targetGroupName})...</>");

        try {
            $timeNow = now()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s') . ' WIB';
            $safeGroupName = htmlspecialchars($targetGroupName, ENT_QUOTES, 'UTF-8');
            $testMsg = "🤖 <b>PENGUJIAN KONEKSI BOT TELEGRAM</b>\n"
                     . "━━━━━━━━━━━━━━━━━━━━\n"
                     . "✅ Bot berhasil terhubung ke: <b>{$safeGroupName}</b>\n"
                     . "🕒 Waktu Uji: <code>{$timeNow}</code>\n"
                     . "🚀 Notifikasi tiket siap dialokasikan ke grup teknisi ini.";

            $payload = [
                'chat_id'    => $chatId,
                'text'       => $testMsg,
                'parse_mode' => 'HTML',
            ];

            if (!empty($topicId)) {
                $payload['message_thread_id'] = (int) $topicId;
            }

            $sendRes = Http::withoutVerifying()->timeout(6)->asJson()->post("https://api.telegram.org/bot{$botToken}/sendMessage", $payload);
            $sendData = $sendRes->json();

            if ($sendRes->successful() && !empty($sendData['ok'])) {
                $this->info("[+] Pesan uji coba BERHASIL terkirim ke target!");
                $this->newLine();
                $this->info('========================================================');
                $this->info('  SEMUA PENGUJIAN BOT TELEGRAM BERJALAN DENGAN SUKSES   ');
                $this->info('========================================================');
                return self::SUCCESS;
            } else {
                $this->error('[-] Gagal mengirim pesan ke grup Telegram!');
                $this->error('    Respon API: ' . ($sendData['description'] ?? $sendRes->body()));
                $this->newLine();
                $this->warn('Petunjuk Perbaikan:');
                $this->line('1. Pastikan bot sudah di-invite / dimasukkan ke dalam grup Telegram target.');
                $this->line('2. Pastikan bot diberi izin (permission) untuk mengirim pesan di grup.');
                $this->line('3. Jika grup berjenis supergroup, chat ID umumnya memiliki awalan -100.');
                return self::FAILURE;
            }
        } catch (Throwable $e) {
            $this->error("[-] Exception saat mengirim pesan: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
