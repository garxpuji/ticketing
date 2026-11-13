<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTicketTelegramNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $timeout = 20;

    /**
     * Exponential backoff in seconds between retries.
     *
     * @var array<int, int>
     */
    public array $backoff = [5, 15, 30];

    public function __construct(
        public Ticket $ticket,
        public string $event,
        public ?string $remark = null,
        public ?User $actor = null
    ) {}

    public function handle(TelegramService $telegramService): void
    {
        $telegramService->sendTicketNotification(
            $this->ticket,
            $this->event,
            $this->remark ?? '',
            $this->actor
        );
    }

    /**
     * Handle job failure after all retries are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("SendTicketTelegramNotificationJob failed for Ticket #{$this->ticket->ticket_number}: " . ($exception?->getMessage() ?? 'Unknown error'));
    }
}
