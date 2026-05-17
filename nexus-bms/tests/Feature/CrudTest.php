<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Core\Permissions\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@nexus.com')->firstOrFail();
        $this->actingAs($this->admin);
    }

    public function test_admin_can_create_building(): void
    {
        $code = 'TEST-' . strtoupper(substr(uniqid(), -6));

        $payload = [
            'code'         => $code,
            'name'         => 'Test Building',
            'name_th'      => 'อาคารทดสอบ',
            'address'      => '123 Test Street',
            'city'         => 'Bangkok',
            'floors_count' => 5,
            'total_area'   => 1200.50,
            'status'       => 'active',
        ];

        $response = $this->post('/buildings', $payload);

        $response->assertStatus(302);
        $response->assertRedirect('/buildings');
        $this->assertDatabaseHas('buildings', ['code' => $code, 'name' => 'Test Building']);

        // Cleanup (DatabaseTransactions will roll back too, but keep explicit).
        Building::where('code', $code)->delete();
        $this->assertDatabaseMissing('buildings', ['code' => $code]);
    }

    public function test_admin_can_edit_existing_building(): void
    {
        $building = Building::first();
        $this->assertNotNull($building, 'Expected at least one seeded building.');

        $original = $building->only(['name', 'name_th', 'address', 'city', 'floors_count', 'total_area', 'status']);

        $newName = 'Edited Building ' . uniqid();

        $response = $this->put('/buildings/' . $building->id, [
            'name'         => $newName,
            'name_th'      => $original['name_th'] ?? null,
            'address'      => $original['address'] ?? null,
            'city'         => $original['city'] ?? null,
            'floors_count' => $original['floors_count'] ?? 1,
            'total_area'   => $original['total_area'] ?? null,
            'status'       => $original['status'] ?? 'active',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('buildings', ['id' => $building->id, 'name' => $newName]);

        // Revert
        Building::where('id', $building->id)->update(['name' => $original['name']]);
        $this->assertDatabaseHas('buildings', ['id' => $building->id, 'name' => $original['name']]);
    }

    public function test_admin_can_create_equipment(): void
    {
        $building = Building::where('status', 'active')->first();
        $category = EquipmentCategory::first();
        $this->assertNotNull($building);
        $this->assertNotNull($category);

        $code = 'EQ-TEST-' . strtoupper(substr(uniqid(), -6));

        $payload = [
            'code'         => $code,
            'name'         => 'Test Equipment',
            'manufacturer' => 'TestCo',
            'model_number' => 'TM-100',
            'building_id'  => $building->id,
            'category_id'  => $category->id,
            'status'       => 'active',
            'health_score' => 95,
            'notes'        => 'Created by feature test',
        ];

        $response = $this->post('/equipment', $payload);

        $response->assertStatus(302);
        $response->assertRedirect('/equipment');
        $this->assertDatabaseHas('equipment', ['code' => $code, 'name' => 'Test Equipment']);

        // Cleanup
        Equipment::where('code', $code)->delete();
        $this->assertDatabaseMissing('equipment', ['code' => $code]);
    }

    public function test_admin_can_create_user(): void
    {
        $role = Role::where('name', 'operator')->first();
        $this->assertNotNull($role);

        $email = 'crudtest+' . uniqid() . '@nexus.com';

        $payload = [
            'name'                  => 'Crud Test User',
            'email'                 => $email,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role_id'               => $role->id,
            'department'            => 'QA',
            'phone'                 => '02-111-1111',
            'status'                => 'active',
        ];

        $response = $this->post('/users', $payload);

        $response->assertStatus(302);
        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['email' => $email, 'name' => 'Crud Test User']);

        // Cleanup
        User::where('email', $email)->delete();
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }
}
