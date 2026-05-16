<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Building;
use App\Models\Equipment;
use App\Models\User;
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $towerA = Building::where('code','NTA')->first();
        $towerB = Building::where('code','NTB')->first();
        $user = User::where('email','admin@nexus.com')->first();

        $schedules = [
            ['name'=>'Tower A HVAC Morning Start','building_id'=>$towerA?->id,'category'=>'HVAC','start_date'=>Carbon::today()->startOfMonth(),'turn_on_time'=>'07:00:00','turn_off_time'=>'19:00:00','repeat_days'=>['mon','tue','wed','thu','fri'],'recurrence'=>'weekly','priority'=>1,'status'=>'active'],
            ['name'=>'Tower A Lighting Business Hours','building_id'=>$towerA?->id,'category'=>'Lighting','start_date'=>Carbon::today()->startOfMonth(),'turn_on_time'=>'06:30:00','turn_off_time'=>'20:00:00','repeat_days'=>['mon','tue','wed','thu','fri'],'recurrence'=>'weekly','priority'=>2,'status'=>'active'],
            ['name'=>'Tower B HVAC Schedule','building_id'=>$towerB?->id,'category'=>'HVAC','start_date'=>Carbon::today()->startOfMonth(),'turn_on_time'=>'08:00:00','turn_off_time'=>'18:00:00','repeat_days'=>['mon','tue','wed','thu','fri'],'recurrence'=>'weekly','priority'=>1,'status'=>'active'],
            ['name'=>'Weekend Minimal Operation','building_id'=>$towerA?->id,'category'=>'HVAC','start_date'=>Carbon::today()->startOfMonth(),'turn_on_time'=>'10:00:00','turn_off_time'=>'16:00:00','repeat_days'=>['sat','sun'],'recurrence'=>'weekly','priority'=>3,'status'=>'active'],
            ['name'=>'Access Control Business Hours','building_id'=>$towerA?->id,'category'=>'Access Control','start_date'=>Carbon::today()->startOfMonth(),'turn_on_time'=>'06:00:00','turn_off_time'=>'22:00:00','repeat_days'=>['mon','tue','wed','thu','fri','sat'],'recurrence'=>'weekly','priority'=>1,'status'=>'active'],
            ['name'=>'Monthly HVAC Maintenance','building_id'=>$towerA?->id,'category'=>'Maintenance','start_date'=>Carbon::today()->startOfMonth(),'turn_on_time'=>'00:00:00','turn_off_time'=>'06:00:00','repeat_days'=>['sun'],'recurrence'=>'monthly','priority'=>5,'status'=>'active'],
            ['name'=>'Night Security Lighting','building_id'=>$towerA?->id,'category'=>'Lighting','start_date'=>Carbon::today()->startOfMonth(),'turn_on_time'=>'20:00:00','turn_off_time'=>'07:00:00','repeat_days'=>['mon','tue','wed','thu','fri','sat','sun'],'recurrence'=>'daily','priority'=>2,'status'=>'active'],
            ['name'=>'Lobby HVAC All Days','building_id'=>$towerA?->id,'category'=>'HVAC','start_date'=>Carbon::today()->startOfMonth(),'turn_on_time'=>'05:30:00','turn_off_time'=>'23:00:00','repeat_days'=>['mon','tue','wed','thu','fri','sat','sun'],'recurrence'=>'daily','priority'=>2,'status'=>'active'],
        ];

        foreach ($schedules as $sch) {
            $schedule = Schedule::create(array_merge($sch, ['created_by'=>$user?->id,'holiday_exception'=>false,'timezone'=>'Asia/Bangkok']));
            // Attach random equipment
            $eqIds = Equipment::inRandomOrder()->limit(rand(2,4))->pluck('id')->toArray();
            $schedule->equipment()->sync($eqIds);
        }
    }
}
