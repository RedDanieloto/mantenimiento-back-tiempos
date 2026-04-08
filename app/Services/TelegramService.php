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
        $chatId = env('TELEGRAM_CHAT_ID');

        if (!$botToken || !$chatId) {
            Log::warning('TELEGRAM_BOT_TOKEN o TELEGRAM_CHAT_ID no están configurados. No se envió el mensaje.');
            return false;
        }

        try {
            $apiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

            $response = Http::post($apiUrl, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ]);

            if ($response->successful()) {
                Log::info("Telegram message sent successfully to Chat ID {$chatId}");
                return true;
            }

            Log::error("Failed to send Telegram message: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("Exception sending Telegram message: " . $e->getMessage());
            return false;
        }
    }
}
