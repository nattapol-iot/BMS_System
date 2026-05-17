<?php

namespace Tests\Feature;

use App\Core\Permissions\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('email', 'admin@nexus.com')->firstOrFail();

        // The viewer role is seeded by RoleSeeder/PermissionSeeder, but no viewer
        // user exists by default — create one inside the transaction.
        $viewerRole = Role::where('name', 'viewer')->firstOrFail();
        $this->viewer = User::firstOrCreate(
            ['email' => 'viewer@nexus.com'],
            [
                'name'     => 'Viewer User',
                'password' => Hash::make('viewer1234'),
                'role_id'  => $viewerRole->id,
                'status'   => 'active',
            ]
        );
        // Always make sure the viewer is on the viewer role for this test run.
        if ($this->viewer->role_id !== $viewerRole->id) {
            $this->viewer->update(['role_id' => $viewerRole->id]);
        }
    }

    public function test_admin_can_access_users_settings_logs(): void
    {
        $this->actingAs($this->admin);

        $this->get('/users')->assertStatus(200);
        $this->get('/settings')->assertStatus(200);
        $this->get('/logs')->assertStatus(200);
    }

    public function test_viewer_is_forbidden_from_users_settings_logs(): void
    {
        $this->actingAs($this->viewer);

        $this->get('/users')->assertStatus(403);
        $this->get('/settings')->assertStatus(403);
        $this->get('/logs')->assertStatus(403);
    }

    public function test_viewer_can_access_dashboard_buildings_equipment_alarms(): void
    {
        $this->actingAs($this->viewer);

        $this->get('/dashboard')->assertStatus(200);
        $this->get('/buildings')->assertStatus(200);
        $this->get('/equipment')->assertStatus(200);
        $this->get('/alarms')->assertStatus(200);
    }

    public function test_viewer_cannot_access_buildings_create(): void
    {
        $this->actingAs($this->viewer);

        $this->get('/buildings/create')->assertStatus(403);
    }
}
