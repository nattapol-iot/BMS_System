<?php

namespace Tests\Feature;

use App\Models\EnergyMeter;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class IoTApiTest extends TestCase
{
    use DatabaseTransactions;

    protected string $token = 'test-iot-token-1234567890';

    protected function setUp(): void
    {
        parent::setUp();

        // The VerifyApiToken middleware reads from env('IOT_API_TOKEN').
        putenv('IOT_API_TOKEN=' . $this->token);
        $_ENV['IOT_API_TOKEN']    = $this->token;
        $_SERVER['IOT_API_TOKEN'] = $this->token;
        config(['app.iot_api_token' => $this->token]);
    }

    public function test_post_equipment_status_with_valid_token_updates_equipment(): void
    {
        $equipment = Equipment::first();
        $this->assertNotNull($equipment, 'Expected at least one seeded equipment row.');

        $originalStatus = $equipment->status;
        $originalHealth = $equipment->health_score;

        $response = $this->withHeaders(['X-API-Token' => $this->token])
            ->postJson('/api/iot/equipment/' . $equipment->code . '/status', [
                'status'       => 'maintenance',
                'health_score' => 72,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('equipment', [
            'id'           => $equipment->id,
            'status'       => 'maintenance',
            'health_score' => 72,
        ]);

        // Revert so we don't pollute seeded data even if transactions are bypassed.
        Equipment::where('id', $equipment->id)->update([
            'status'       => $originalStatus,
            'health_score' => $originalHealth,
        ]);
    }

    public function test_post_equipment_status_without_token_returns_401(): void
    {
        $equipment = Equipment::first();
        $this->assertNotNull($equipment);

        $response = $this->postJson('/api/iot/equipment/' . $equipment->code . '/status', [
            'status'       => 'active',
            'health_score' => 100,
        ]);

        $response->assertStatus(401);
    }

    public function test_post_meter_reading_creates_energy_log(): void
    {
        $meter = EnergyMeter::first();
        $this->assertNotNull($meter, 'Expected at least one seeded energy meter.');

        $before = \DB::table('energy_logs')->where('meter_id', $meter->id)->count();

        $response = $this->withHeaders(['X-API-Token' => $this->token])
            ->postJson('/api/iot/meter/' . rawurlencode($meter->name) . '/reading', [
                'value'       => 123.4567,
                'peak_demand' => 45.6789,
            ]);

        $response->assertStatus(200);

        $after = \DB::table('energy_logs')->where('meter_id', $meter->id)->count();
        $this->assertSame($before + 1, $after, 'Expected a new energy_logs row.');

        $this->assertDatabaseHas('energy_logs', [
            'meter_id' => $meter->id,
            'value'    => 123.4567,
        ]);
    }

    public function test_dashboard_live_returns_expected_json_keys(): void
    {
        $response = $this->withHeaders(['X-API-Token' => $this->token])
            ->getJson('/api/dashboard/live');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'active_alarms',
            'critical_alarms',
            'today_energy',
            'system_health',
        ]);
    }
}
