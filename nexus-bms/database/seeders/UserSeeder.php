<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Core\Permissions\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name','admin')->first();
        $managerRole = Role::where('name','manager')->first();
        $operatorRole = Role::where('name','operator')->first();

        User::firstOrCreate(['email'=>'admin@nexus.com'], [
            'name'=>'Admin User',
            'password'=>Hash::make('admin1234'),
            'role_id'=>$adminRole?->id,
            'department'=>'IT & Systems',
            'phone'=>'02-000-0001',
            'status'=>'active',
            'locale'=>'th',
        ]);

        User::firstOrCreate(['email'=>'manager@nexus.com'], [
            'name'=>'Building Manager',
            'password'=>Hash::make('manager1234'),
            'role_id'=>$managerRole?->id,
            'department'=>'Facility Management',
            'phone'=>'02-000-0002',
            'status'=>'active',
            'locale'=>'th',
        ]);

        User::firstOrCreate(['email'=>'operator@nexus.com'], [
            'name'=>'System Operator',
            'password'=>Hash::make('operator1234'),
            'role_id'=>$operatorRole?->id,
            'department'=>'Operations',
            'phone'=>'02-000-0003',
            'status'=>'active',
            'locale'=>'en',
        ]);
    }
}
