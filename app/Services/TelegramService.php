<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /**
     * Send a general text message via Telegram.
     */
    public function sendMessage(string $message): bool
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatIds = collect(explode(',', (string) env('TELEGRAM_CHAT_IDS', '')))
            ->map(fn (string $id) => trim($id))
            ->filter()
            ->values();

        // Backward compatibility for legacy single chat configuration.
        if ($chatIds->isEmpty() && env('TELEGRAM_CHAT_ID')) {
            $chatIds = collect([trim((string) env('TELEGRAM_CHAT_ID'))]);
        }

        if (!$botToken || $chatIds->isEmpty()) {
            Log::warning('TELEGRAM_BOT_TOKEN o TELEGRAM_CHAT_IDS no están configurados. No se envió el mensaje.');
            return false;
        }

        try {
            $apiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

            $allSuccessful = true;

            foreach ($chatIds as $chatId) {
                $response = Http::post($apiUrl, [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                ]);

                if ($response->successful()) {
                    Log::info("Telegram message sent successfully to Chat ID {$chatId}");
                    continue;
                }

                $allSuccessful = false;
                Log::error("Failed to send Telegram message to Chat ID {$chatId}: " . $response->body());
            }

            return $allSuccessful;

        } catch (\Exception $e) {
            Log::error("Exception sending Telegram message: " . $e->getMessage());
            return false;
        }
    }
}
