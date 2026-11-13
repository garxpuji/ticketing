<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramService
{
    protected ?string $botToken;
    protected ?string $chatId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId   = config('services.telegram.chat_id');
    }

    /**
     * Check if Telegram bot token is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->botToken);
    }

    /**
     * Send a raw HTML text message to Telegram group / chat / forum topic.
     *
     * @param string $text Message formatted in HTML
     * @param string|null $targetChatId Target Telegram Chat ID (defaults to global config)
     * @param string|null $targetTopicId Optional Topic / Thread ID for forum supergroups
     */
    public function sendMessage(string $text, ?string $targetChatId = null, ?string $targetTopicId = null): bool
    {
        $chatId = $targetChatId ?: $this->chatId;

        if (empty($this->botToken) || empty($chatId)) {
            Log::info("TelegramService: Bot token or chat ID is empty. Skipping notification.");
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $verifySsl = (bool) config('services.telegram.verify_ssl', true);

            $payload = [
                'chat_id'                  => $chatId,
                'text'                     => $text,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => false,
            ];

            if (!empty($targetTopicId)) {
                $payload['message_thread_id'] = (int) $targetTopicId;
            }

            $client = $verifySsl ? Http::timeout(6) : Http::withoutVerifying()->timeout(6);
            $response = $client->asJson()->post($url, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning("TelegramService: Failed to send message to chat {$chatId} (HTTP {$response->status()}): " . $response->body());
            return false;
        } catch (Throwable $e) {
            $sanitizedError = $this->botToken ? str_replace($this->botToken, '***MASKED***', $e->getMessage()) : $e->getMessage();
            Log::warning("TelegramService: Exception while sending message to chat {$chatId}: {$sanitizedError}");
            return false;
        }
    }

    /**
     * Send a test verification message to specific group / topic.
     *
     * @return array{success: bool, message: string}
     */
    public function sendTestMessage(string $chatId, ?string $topicId = null, string $groupName = 'Grup'): array
    {
        if (empty($this->botToken)) {
            return [
                'success' => false,
                'message' => 'TELEGRAM_BOT_TOKEN belum dikonfigurasi di file .env',
            ];
        }

        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $verifySsl = (bool) config('services.telegram.verify_ssl', true);

            $safeGroupName = htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8');
            $timeNow = now()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s') . ' WIB';

            $payload = [
                'chat_id'                  => $chatId,
                'text'                     => "🤖 <b>TES KONEKSI BOT TELEGRAM</b>\n━━━━━━━━━━━━━━━━━━━━\n✅ Bot berhasil terhubung ke <b>{$safeGroupName}</b>.\n🕒 Waktu: <code>{$timeNow}</code>\n<i>Notifikasi tiket untuk tim ini akan otomatis masuk ke sini.</i>",
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
            ];

            if (!empty($topicId)) {
                $payload['message_thread_id'] = (int) $topicId;
            }

            $client = $verifySsl ? Http::timeout(8) : Http::withoutVerifying()->timeout(8);
            $response = $client->asJson()->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => "Pesan uji coba berhasil terkirim ke Telegram ({$groupName})!",
                ];
            }

            $data = $response->json();
            $description = $data['description'] ?? $response->body();
            return [
                'success' => false,
                'message' => "Gagal kirim (HTTP {$response->status()}): {$description}",
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve target Telegram destinations for a ticket based on its group and assigned technician.
     *
     * @return array<int, array{chat_id: string, topic_id: ?string, source: string}>
     */
    public function resolveDestinations(Ticket $ticket): array
    {
        $destinations = [];

        // 1. Direct group assigned to ticket
        $ticketGroup = $ticket->relationLoaded('technicianGroup')
            ? $ticket->technicianGroup
            : $ticket->technicianGroup()->first();

        if ($ticketGroup && $ticketGroup->is_active && !empty($ticketGroup->telegram_chat_id)) {
            $destinations[] = [
                'chat_id'  => (string) $ticketGroup->telegram_chat_id,
                'topic_id' => $ticketGroup->telegram_topic_id ? (string) $ticketGroup->telegram_topic_id : null,
                'source'   => "Grup: {$ticketGroup->name} ({$ticketGroup->code})",
            ];
        }

        // 2. Assigned technician's group (if different)
        $tech = $ticket->relationLoaded('assignedTechnician')
            ? $ticket->assignedTechnician
            : $ticket->assignedTechnician()->first();

        if ($tech && $tech->technician_group_id) {
            $techGroup = $tech->relationLoaded('technicianGroup')
                ? $tech->technicianGroup
                : $tech->technicianGroup()->first();

            if ($techGroup && $techGroup->is_active && !empty($techGroup->telegram_chat_id)) {
                $alreadyAdded = false;
                foreach ($destinations as $dest) {
                    if (
                        $dest['chat_id'] === (string) $techGroup->telegram_chat_id &&
                        $dest['topic_id'] === ($techGroup->telegram_topic_id ? (string) $techGroup->telegram_topic_id : null)
                    ) {
                        $alreadyAdded = true;
                        break;
                    }
                }

                if (!$alreadyAdded) {
                    $destinations[] = [
                        'chat_id'  => (string) $techGroup->telegram_chat_id,
                        'topic_id' => $techGroup->telegram_topic_id ? (string) $techGroup->telegram_topic_id : null,
                        'source'   => "Teknisi: {$tech->name} ({$techGroup->name})",
                    ];
                }
            }
        }

        // 3. Fallback to global central chat_id if no specific group destinations configured
        if (empty($destinations) && !empty($this->chatId)) {
            $destinations[] = [
                'chat_id'  => (string) $this->chatId,
                'topic_id' => null,
                'source'   => 'Global Central Fallback',
            ];
        }

        return $destinations;
    }

    /**
     * Build and send formatted notification for ticket lifecycle events to all resolved group destinations.
     *
     * @param Ticket $ticket
     * @param string $eventType 'ticket_created' | 'technician_assigned' | 'status_updated'
     * @param string|null $remark
     * @param User|null $actor
     * @return bool
     */
    public function sendTicketNotification(Ticket $ticket, string $eventType, ?string $remark = null, ?User $actor = null): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        // Ensure relations loaded
        $ticket->loadMissing(['billingInstance', 'assignedTechnician.technicianGroup', 'technicianGroup']);

        // Header and Icon based on event & status
        $status = strtolower($ticket->status instanceof TicketStatus ? $ticket->status->value : (string) $ticket->status);
        switch ($eventType) {
            case 'ticket_created':
                $headerTitle = "🆕 <b>TIKET GANGGUAN BARU</b>";
                break;
            case 'technician_assigned':
                $headerTitle = "👷 <b>PENUGASAN TEKNISI TIKET</b>";
                break;
            case 'status_updated':
            default:
                if ($status === 'close' || $status === 'done') {
                    $headerTitle = "✅ <b>TIKET SELESAI (CLOSED)</b>";
                } elseif ($status === 'process') {
                    $headerTitle = "⚙️ <b>TIKET SEDANG DIPROSES</b>";
                } else {
                    $headerTitle = "🛠️ <b>UPDATE STATUS TIKET</b>";
                }
                break;
        }

        // Badge Status
        $badgeStatus = match ($status) {
            'process'        => '🔵 <b>SEDANG DIPROSES</b>',
            'close', 'done'  => '🟢 <b>SELESAI (CLOSE)</b>',
            default          => '🟡 <b>PENDING (MENUNGGU)</b>',
        };

        // Sanitasi teks aman HTML
        $escNumber   = htmlspecialchars($ticket->ticket_number, ENT_QUOTES, 'UTF-8');
        $escTenant   = htmlspecialchars($ticket->billingInstance?->name ?? 'Central System', ENT_QUOTES, 'UTF-8');
        $escName     = htmlspecialchars($ticket->customer_name, ENT_QUOTES, 'UTF-8');
        $escServices = htmlspecialchars($ticket->no_services ?? '-', ENT_QUOTES, 'UTF-8');
        $escPhone    = htmlspecialchars($ticket->customer_phone ?? '-', ENT_QUOTES, 'UTF-8');
        $escAddress  = htmlspecialchars($ticket->customer_address ?? '-', ENT_QUOTES, 'UTF-8');
        $escCategory = htmlspecialchars($ticket->category_name ?? 'Gangguan Umum', ENT_QUOTES, 'UTF-8');
        $escProblem  = htmlspecialchars($ticket->problem_description ?? '-', ENT_QUOTES, 'UTF-8');

        // Teknisi & Tim
        $techName  = $ticket->assignedTechnician?->name;
        $escTech   = $techName ? htmlspecialchars($techName, ENT_QUOTES, 'UTF-8') : '<i>Belum ditugaskan</i>';

        $group = $ticket->technicianGroup ?? $ticket->assignedTechnician?->technicianGroup;
        $escGroup = $group ? htmlspecialchars("{$group->name} ({$group->code})", ENT_QUOTES, 'UTF-8') : null;

        // Aktor / Pengupdate
        $actorName = $actor?->name ?? $ticket->created_by_name ?? 'System';
        $actorRole = $actor?->role ? ucfirst($actor->role) : ($ticket->created_by_role ? ucfirst($ticket->created_by_role) : 'Admin');
        $escActor  = htmlspecialchars("{$actorName} ({$actorRole})", ENT_QUOTES, 'UTF-8');

        $timeNow = now()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s') . ' WIB';

        // Susun template pesan
        $lines = [
            $headerTitle,
            "━━━━━━━━━━━━━━━━━━━━",
            "🏢 <b>Instansi:</b> {$escTenant}",
            "📌 <b>No Tiket:</b> <code>#{$escNumber}</code>",
            "👤 <b>Pelanggan:</b> {$escName} ({$escServices})",
            "📞 <b>No Telp:</b> {$escPhone}",
            "📍 <b>Alamat:</b> {$escAddress}",
            "⚠️ <b>Topik Gangguan:</b> {$escCategory}",
            "📋 <b>Keluhan:</b> {$escProblem}",
            "",
            "📊 <b>Status:</b> {$badgeStatus}",
            "👷 <b>Teknisi:</b> {$escTech}",
        ];

        if ($escGroup) {
            $lines[] = "👥 <b>Tim:</b> {$escGroup}";
        }

        if (!empty($remark)) {
            $escRemark = htmlspecialchars($remark, ENT_QUOTES, 'UTF-8');
            $lines[] = "📝 <b>Catatan / Tindakan:</b>\n<i>{$escRemark}</i>";
        }

        $lines[] = "";
        $lines[] = "👤 <b>Oleh:</b> {$escActor}";
        $lines[] = "🕒 <b>Waktu:</b> {$timeNow}";

        // Tautan peta jika koordinat tersedia
        if (!empty($ticket->latitude) && !empty($ticket->longitude)) {
            $lat = trim($ticket->latitude);
            $lng = trim($ticket->longitude);
            $mapsUrl = "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}";
            $lines[] = "━━━━━━━━━━━━━━━━━━━━";
            $lines[] = "🗺️ <a href=\"{$mapsUrl}\">Buka Rute Google Maps Pelanggan</a>";
        }

        $message = implode("\n", $lines);

        $destinations = $this->resolveDestinations($ticket);
        if (empty($destinations)) {
            Log::info("TelegramService: Tidak ada target chat destination untuk tiket #{$ticket->ticket_number}.");
            return false;
        }

        $success = false;
        foreach ($destinations as $dest) {
            $ok = $this->sendMessage($message, $dest['chat_id'], $dest['topic_id'] ?? null);
            if ($ok) {
                $success = true;
            }
        }

        return $success;
    }
}
