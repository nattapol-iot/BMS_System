<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Core\Permissions\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name'=>'admin','display_name'=>'Administrator','description'=>'Full system access'],
            ['name'=>'manager','display_name'=>'Building Manager','description'=>'Manage buildings and equipment'],
            ['name'=>'operator','display_name'=>'Operator','description'=>'Monitor and control equipment'],
            ['name'=>'viewer','display_name'=>'Viewer','description'=>'Read-only access'],
        ];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name'=>$role['name']], $role);
        }
    }
}
