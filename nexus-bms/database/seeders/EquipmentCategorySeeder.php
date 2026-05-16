<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EquipmentCategory;

class EquipmentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name'=>'HVAC','name_th'=>'ระบบปรับอากาศ','icon'=>'fa-wind','color'=>'#3b82f6'],
            ['name'=>'Lighting','name_th'=>'ระบบแสงสว่าง','icon'=>'fa-lightbulb','color'=>'#f59e0b'],
            ['name'=>'Fire Alarm','name_th'=>'ระบบแจ้งเตือนเพลิงไหม้','icon'=>'fa-fire','color'=>'#ef4444'],
            ['name'=>'Security','name_th'=>'ระบบรักษาความปลอดภัย','icon'=>'fa-shield-halved','color'=>'#8b5cf6'],
            ['name'=>'Access Control','name_th'=>'ระบบควบคุมการเข้าออก','icon'=>'fa-door-open','color'=>'#22c55e'],
            ['name'=>'Pumps','name_th'=>'ระบบสูบน้ำ','icon'=>'fa-droplet','color'=>'#06b6d4'],
            ['name'=>'Elevators','name_th'=>'ลิฟต์','icon'=>'fa-elevator','color'=>'#64748b'],
            ['name'=>'Chillers','name_th'=>'เครื่องทำความเย็น','icon'=>'fa-snowflake','color'=>'#0ea5e9'],
            ['name'=>'Sensors','name_th'=>'เซ็นเซอร์','icon'=>'fa-microchip','color'=>'#a855f7'],
            ['name'=>'Power','name_th'=>'ระบบไฟฟ้า','icon'=>'fa-bolt','color'=>'#eab308'],
        ];
        foreach ($categories as $cat) {
            EquipmentCategory::firstOrCreate(['name'=>$cat['name']], $cat);
        }
    }
}
