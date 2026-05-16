<?php
namespace App\Console\Commands;

use App\Models\Equipment;
use App\Models\EnergyMeter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SimulateIotCommand extends Command
{
    protected $signature = 'iot:simulate
        {--url= : Target Nexus URL (defaults to APP_URL or http://127.0.0.1:8000)}
        {--token= : IoT API token (defaults to IOT_API_TOKEN env)}
        {--interval=10 : Seconds between cycles}
        {--count=0 : Number of cycles to run (0 = forever, until Ctrl+C)}
        {--equipment=* : Restrict to these equipment codes (default: all active)}
        {--meters=* : Restrict to these meter names (default: all)}
        {--offline= : One-shot: push offline status to this equipment code and exit}
        {--burst=1 : Number of readings to send per cycle per device}
        {--dry-run : Print what would be sent, do not POST}';

    protected $description = 'Simulate IoT devices pushing readings to the Nexus REST API';

    public function handle(): int
    {
        $url = rtrim($this->option('url') ?: env('APP_URL', 'http://127.0.0.1:8000'), '/');
        $token = $this->option('token') ?: env('IOT_API_TOKEN', '');
        if (!$token) {
            $this->error('IOT_API_TOKEN is not set. Pass --token=... or set it in .env');
            return self::FAILURE;
        }

        // One-shot offline trigger
        if ($code = $this->option('offline')) {
            return $this->oneShotOffline($url, $token, $code);
        }

        // Pick devices
        $equipmentCodes = $this->option('equipment')
            ?: Equipment::where('status', '!=', 'offline')->pluck('code')->all();
        $meterNames = $this->option('meters')
            ?: EnergyMeter::pluck('name')->all();

        if (empty($equipmentCodes) && empty($meterNames)) {
            $this->error('No equipment or meters found in DB. Seed first: php artisan db:seed');
            return self::FAILURE;
        }

        $interval = max(1, (int) $this->option('interval'));
        $count = (int) $this->option('count');
        $burst = max(1, (int) $this->option('burst'));
        $dry = (bool) $this->option('dry-run');

        $this->info(sprintf(
            "Target: %s\nEquipment: %d device(s)\nMeters: %d meter(s)\nInterval: %ds  Burst: %d  Count: %s%s",
            $url, count($equipmentCodes), count($meterNames), $interval, $burst,
            $count > 0 ? $count : 'forever',
            $dry ? '  [DRY RUN]' : ''
        ));
        $this->line('Press Ctrl+C to stop.');
        $this->newLine();

        $cycle = 0;
        while ($count === 0 || $cycle < $count) {
            $cycle++;
            $this->line("─── Cycle {$cycle} @ " . now()->format('H:i:s') . " ───");

            foreach ($meterNames as $name) {
                for ($i = 0; $i < $burst; $i++) {
                    $reading = $this->simulateMeterReading($name);
                    $endpoint = "/api/iot/meter/" . rawurlencode($name) . "/reading";
                    $this->push($url, $token, $endpoint, $reading, $dry);
                }
            }

            foreach ($equipmentCodes as $code) {
                $status = $this->simulateEquipmentStatus();
                $endpoint = "/api/iot/equipment/{$code}/status";
                $this->push($url, $token, $endpoint, $status, $dry);
            }

            if ($count !== 0 && $cycle >= $count) break;
            sleep($interval);
        }

        $this->info("Done. {$cycle} cycle(s).");
        return self::SUCCESS;
    }

    private function oneShotOffline(string $url, string $token, string $code): int
    {
        $this->warn("Triggering OFFLINE event for equipment: {$code}");
        $endpoint = "/api/iot/equipment/{$code}/status";
        $ok = $this->push($url, $token, $endpoint, ['status' => 'offline', 'health_score' => 0], false);
        if ($ok) {
            $this->info("Done. Check /alarms — a critical alarm should have been auto-created.");
            return self::SUCCESS;
        }
        return self::FAILURE;
    }

    private function simulateMeterReading(string $name): array
    {
        // Realistic 24h curve — peak at 14:00, trough at 03:00
        $hour = (float) now()->hour + (now()->minute / 60.0);
        $factor = 0.55 + 0.40 * sin((($hour - 6) / 24) * 2 * M_PI);
        $isWater = stripos($name, 'water') !== false;
        $base = $isWater ? 8.0 : 120.0;            // m³/h vs kWh/h baseline
        $value = round($base * $factor * mt_rand(85, 115) / 100, 2);

        $body = ['value' => $value];
        if (!$isWater) {
            $body['peak_demand'] = round($value * 0.7, 2);
            $body['power_factor'] = round(mt_rand(920, 990) / 1000, 3);
            $body['cost'] = round($value * 4.5, 2);
        }
        return $body;
    }

    private function simulateEquipmentStatus(): array
    {
        $r = mt_rand(1, 1000);
        if ($r <= 950) {
            return ['status' => 'active', 'health_score' => mt_rand(80, 100)];
        } elseif ($r <= 985) {
            return ['status' => 'maintenance', 'health_score' => mt_rand(40, 75)];
        } elseif ($r <= 995) {
            return ['status' => 'inactive', 'health_score' => mt_rand(50, 95)];
        } else {
            return ['status' => 'offline', 'health_score' => mt_rand(0, 30)];
        }
    }

    private function push(string $url, string $token, string $endpoint, array $body, bool $dry): bool
    {
        $full = $url . $endpoint;
        if ($dry) {
            $this->line(sprintf('  [DRY] %s  %s', $endpoint, json_encode($body)));
            return true;
        }
        try {
            $response = Http::withHeaders([
                'X-API-Token' => $token,
                'Accept' => 'application/json',
            ])->timeout(10)->post($full, $body);

            $marker = $response->successful() ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $this->line(sprintf(
                '  %s %s  %s  → %d',
                $marker,
                str_pad($response->status(), 3),
                $endpoint,
                strlen($response->body())
            ));
            if (!$response->successful()) {
                $this->line('     ' . substr($response->body(), 0, 200));
            }
            return $response->successful();
        } catch (\Throwable $e) {
            $this->line('  <fg=red>✗</> ERR  ' . $endpoint . '  ' . $e->getMessage());
            return false;
        }
    }
}
