<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramService
{
    /**
     * Send a general text message via Telegram.
     */
    public function sendMessage(string $message): bool
    {
        $chatIds = $this->resolveChatIds();

        if ($chatIds->isEmpty()) {
            Log::warning('No hay chat IDs de Telegram configurados (TELEGRAM_CHAT_IDS_BY_AREA/TELEGRAM_CHAT_IDS). No se envio el mensaje.');
            return false;
        }

        return $this->dispatchMessage($message, $chatIds);
    }

    /**
     * Send a text message to chat IDs configured for a specific area name plus _global.
     */
    public function sendMessageByArea(string $message, ?string $areaName): bool
    {
        $chatIds = $this->resolveChatIds($areaName);

        if ($chatIds->isEmpty()) {
            $safeArea = $areaName ?: 'N/A';
            Log::warning("No hay chat IDs de Telegram configurados para el area '{$safeArea}'. No se envio el mensaje.");
            return false;
        }

        return $this->dispatchMessage($message, $chatIds);
    }

    /**
     * Resolve chat IDs using TELEGRAM_CHAT_IDS_BY_AREA first, then legacy fallback.
     */
    private function resolveChatIds(?string $areaName = null): Collection
    {
        $areaMap = $this->getAreaChatMap();

        if (!empty($areaMap)) {
            $globalChatIds = $this->parseChatIds($areaMap['_global'] ?? null);

            if ($areaName !== null && trim($areaName) !== '') {
                $normalizedArea = $this->normalizeAreaKey($areaName);
                $areaChatIds = $this->parseChatIds($areaMap[$normalizedArea] ?? null);

                $chatIds = $areaChatIds
                    ->merge($globalChatIds)
                    ->unique()
                    ->values();

                if ($chatIds->isNotEmpty()) {
                    return $chatIds;
                }

                Log::notice("No hay configuracion de Telegram para area '{$normalizedArea}'. Se usara fallback legacy si existe.");
            }

            if ($globalChatIds->isNotEmpty()) {
                return $globalChatIds->unique()->values();
            }
        }

        return $this->getLegacyChatIds();
    }

    /**
     * Decode TELEGRAM_CHAT_IDS_BY_AREA as JSON.
     *
     * Example:
     * {"troquelado": ["123"], "ensamble": ["456"], "_global": ["789"]}
     */
    private function getAreaChatMap(): array
    {
        $raw = trim((string) env('TELEGRAM_CHAT_IDS_BY_AREA', ''));

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            Log::warning('TELEGRAM_CHAT_IDS_BY_AREA no contiene un JSON valido.');
            return [];
        }

        $normalized = [];

        foreach ($decoded as $key => $value) {
            if (!is_string($key) && !is_int($key)) {
                continue;
            }

            $rawKey = (string) $key;
            $normalizedKey = $rawKey === '_global'
                ? '_global'
                : $this->normalizeAreaKey($rawKey);

            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    /**
     * Backward compatibility for TELEGRAM_CHAT_IDS / TELEGRAM_CHAT_ID.
     */
    private function getLegacyChatIds(): Collection
    {
        $chatIds = $this->parseChatIds(env('TELEGRAM_CHAT_IDS', ''));

        if ($chatIds->isNotEmpty()) {
            return $chatIds->unique()->values();
        }

        return $this->parseChatIds(env('TELEGRAM_CHAT_ID', ''))->unique()->values();
    }

    /**
     * @param mixed $chatIds
     */
    private function parseChatIds($chatIds): Collection
    {
        if (is_array($chatIds)) {
            return collect($chatIds)
                ->map(fn ($id) => trim((string) $id))
                ->filter(fn (string $id) => $id !== '')
                ->values();
        }

        if (!is_string($chatIds)) {
            return collect();
        }

        return collect(explode(',', $chatIds))
            ->map(fn (string $id) => trim($id))
            ->filter(fn (string $id) => $id !== '')
            ->values();
    }

    private function normalizeAreaKey(string $areaName): string
    {
        return (string) Str::of($areaName)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }

    private function dispatchMessage(string $message, Collection $chatIds): bool
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');

        if (!$botToken) {
            Log::warning('TELEGRAM_BOT_TOKEN no esta configurado. No se envio el mensaje.');
            return false;
        }

        try {
            $apiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

            $allSuccessful = true;

            foreach ($chatIds->unique()->values() as $chatId) {
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
