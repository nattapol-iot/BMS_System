<?php
namespace App\Services;

use App\Models\Alarm;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function onCriticalAlarm(Alarm $alarm): void
    {
        $alarm->loadMissing(['equipment', 'building']);

        $message = sprintf(
            "[NEXUS BMS] %s alarm\n%s\nEquipment: %s (%s)\nBuilding: %s\nTime: %s",
            strtoupper($alarm->severity),
            $alarm->description ?? '-',
            $alarm->equipment?->name ?? '-',
            $alarm->equipment?->code ?? '-',
            $alarm->building?->name ?? '-',
            optional($alarm->triggered_at)->format('Y-m-d H:i:s')
        );

        $this->sendLine($message);
        $this->sendEmail($alarm, $message);
    }

    public function sendLine(string $message): bool
    {
        $enabled = SystemSetting::get('alarm_line_enabled', '0');
        $token = SystemSetting::get('alarm_line_token', '');

        if ($enabled !== '1' || !$token) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->withToken($token)
                ->post('https://notify-api.line.me/api/notify', [
                    'message' => "\n" . $message,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('LINE Notify failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendEmail(Alarm $alarm, string $body): bool
    {
        $enabled = SystemSetting::get('alarm_email_enabled', '0');
        $to = SystemSetting::get('alarm_email_recipient', '') ?: config('mail.from.address');

        if ($enabled !== '1' || !$to) {
            return false;
        }

        try {
            Mail::raw($body, function ($m) use ($to, $alarm) {
                $m->to($to)->subject('[Nexus BMS] ' . strtoupper($alarm->severity) . ' alarm: ' . ($alarm->code ?? $alarm->id));
            });
            return true;
        } catch (\Throwable $e) {
            Log::warning('Alarm email failed: ' . $e->getMessage());
            return false;
        }
    }
}
