<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Alarm;
use App\Models\AlarmEvent;
use App\Models\Building;
use App\Models\Equipment;
use App\Models\User;
use Carbon\Carbon;

class AlarmSeeder extends Seeder
{
    public function run(): void
    {
        $buildings = Building::all();
        $equipment = Equipment::all();
        $user = User::where('email','admin@nexus.com')->first();

        $alarmData = [
            ['code'=>'ALM-2026-001','severity'=>'critical','category'=>'HVAC','description'=>'Chiller Unit 01 - High Refrigerant Pressure','description_th'=>'ชิลเลอร์ 01 - แรงดันสารทำความเย็นสูง','recommended_action'=>'Immediately inspect refrigerant pressure and contact HVAC technician','status'=>'active'],
            ['code'=>'ALM-2026-002','severity'=>'critical','category'=>'Fire Alarm','description'=>'Fire Alarm Panel 03 - Smoke Detected Floor 3','description_th'=>'แผงแจ้งเตือนเพลิงไหม้ 03 - ตรวจพบควันชั้น 3','recommended_action'=>'Evacuate floor and contact fire department immediately','status'=>'active'],
            ['code'=>'ALM-2026-003','severity'=>'warning','category'=>'HVAC','description'=>'AHU-07 - Air Filter Pressure Drop High','description_th'=>'AHU-07 - แรงดันตกคร่อมตัวกรองสูง','recommended_action'=>'Schedule filter replacement within 48 hours','status'=>'acknowledged'],
            ['code'=>'ALM-2026-004','severity'=>'warning','category'=>'Power','description'=>'Main Distribution Board - Power Factor Low (0.72)','description_th'=>'ตู้ควบคุมไฟฟ้าหลัก - Power Factor ต่ำ (0.72)','recommended_action'=>'Check capacitor bank and correct power factor','status'=>'acknowledged'],
            ['code'=>'ALM-2026-005','severity'=>'warning','category'=>'Access Control','description'=>'Access Door 3F-01 - Communication Loss','description_th'=>'ประตูควบคุม 3F-01 - ขาดการสื่อสาร','recommended_action'=>'Check network connectivity and controller','status'=>'active'],
            ['code'=>'ALM-2026-006','severity'=>'info','category'=>'Maintenance','description'=>'Elevator 01 - Scheduled Maintenance Due','description_th'=>'ลิฟต์ 01 - ถึงกำหนดบำรุงรักษา','recommended_action'=>'Schedule maintenance with elevator technician','status'=>'resolved'],
            ['code'=>'ALM-2026-007','severity'=>'critical','category'=>'Security','description'=>'Security Camera NTA-01 - Video Loss','description_th'=>'กล้องรักษาความปลอดภัย NTA-01 - สูญเสียสัญญาณวิดีโอ','recommended_action'=>'Check camera power and network connection','status'=>'acknowledged'],
            ['code'=>'ALM-2026-008','severity'=>'warning','category'=>'HVAC','description'=>'Chiller-02 - Leaving Water Temperature High','description_th'=>'ชิลเลอร์-02 - อุณหภูมิน้ำออกสูงเกิน','recommended_action'=>'Check cooling tower operation and water flow','status'=>'active'],
            ['code'=>'ALM-2026-009','severity'=>'info','category'=>'Lighting','description'=>'Lighting Zone 2F-12 - Lamp Failure Detected','description_th'=>'โซนไฟ 2F-12 - ตรวจพบหลอดไฟเสีย','recommended_action'=>'Replace failed lamp within scheduled maintenance','status'=>'resolved'],
            ['code'=>'ALM-2026-010','severity'=>'warning','category'=>'Pumps','description'=>'Water Pump 03 - Motor Temperature High','description_th'=>'ปั๊มน้ำ 03 - อุณหภูมิมอเตอร์สูง','recommended_action'=>'Check pump cooling and reduce load','status'=>'active'],
            ['code'=>'ALM-2026-011','severity'=>'critical','category'=>'HVAC','description'=>'AHU-08 - Supply Fan Motor Fault','description_th'=>'AHU-08 - มอเตอร์พัดลมจ่ายอากาศเสีย','recommended_action'=>'Stop unit and inspect fan motor immediately','status'=>'active'],
            ['code'=>'ALM-2026-012','severity'=>'warning','category'=>'Sensors','description'=>'Temp Sensor T01 - Reading Out of Range (35°C)','description_th'=>'เซ็นเซอร์อุณหภูมิ T01 - ค่าเกินขอบเขต (35°C)','recommended_action'=>'Check sensor calibration and HVAC operation','status'=>'acknowledged'],
        ];

        foreach ($alarmData as $idx => $alarm) {
            $building = $buildings->random();
            $equip = $equipment->random();
            $triggeredAt = Carbon::now()->subHours(rand(1, 72));

            $a = Alarm::firstOrCreate(['code'=>$alarm['code']], array_merge($alarm, [
                'equipment_id' => $equip->id,
                'building_id' => $building->id,
                'assigned_to' => $user?->id,
                'triggered_at' => $triggeredAt,
                'acknowledged_at' => in_array($alarm['status'],['acknowledged','resolved']) ? $triggeredAt->copy()->addMinutes(rand(5,30)) : null,
                'resolved_at' => $alarm['status']==='resolved' ? $triggeredAt->copy()->addHours(rand(1,8)) : null,
            ]));

            AlarmEvent::create([
                'alarm_id' => $a->id,
                'event_type' => 'triggered',
                'performed_by' => null,
                'note' => 'Auto-detected by system',
            ]);
        }
    }
}
