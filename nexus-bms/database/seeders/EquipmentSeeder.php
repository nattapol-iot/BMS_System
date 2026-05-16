<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipment;
use App\Models\Building;
use App\Models\Floor;
use App\Models\EquipmentCategory;
use Carbon\Carbon;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $buildings = Building::all();
        $categories = EquipmentCategory::all()->keyBy('name');

        $equipmentList = [
            ['code'=>'AHU-07','name'=>'Air Handling Unit 07','building_code'=>'NTA','floor'=>7,'category'=>'HVAC','manufacturer'=>'Daikin','model_number'=>'AHU-7000','status'=>'active','health_score'=>92,'runtime_hours'=>8760,'protocol'=>'BACnet'],
            ['code'=>'AHU-08','name'=>'Air Handling Unit 08','building_code'=>'NTA','floor'=>8,'category'=>'HVAC','manufacturer'=>'Daikin','model_number'=>'AHU-7000','status'=>'active','health_score'=>88,'runtime_hours'=>7200,'protocol'=>'BACnet'],
            ['code'=>'CHILLER-01','name'=>'Chiller Unit 01','building_code'=>'NTA','floor'=>1,'category'=>'Chillers','manufacturer'=>'Carrier','model_number'=>'30XA-300','status'=>'active','health_score'=>95,'runtime_hours'=>12500,'protocol'=>'Modbus'],
            ['code'=>'CHILLER-02','name'=>'Chiller Unit 02','building_code'=>'NTB','floor'=>1,'category'=>'Chillers','manufacturer'=>'Carrier','model_number'=>'30XA-200','status'=>'maintenance','health_score'=>65,'runtime_hours'=>11000,'protocol'=>'Modbus'],
            ['code'=>'LGT-2F-12','name'=>'Lighting Zone 2F-12','building_code'=>'NTA','floor'=>2,'category'=>'Lighting','manufacturer'=>'Philips','model_number'=>'CorePro LED','status'=>'active','health_score'=>98,'runtime_hours'=>5200,'protocol'=>'MQTT'],
            ['code'=>'LGT-3F-01','name'=>'Lighting Zone 3F-01','building_code'=>'NTA','floor'=>3,'category'=>'Lighting','manufacturer'=>'Philips','model_number'=>'CorePro LED','status'=>'active','health_score'=>97,'runtime_hours'=>5100,'protocol'=>'MQTT'],
            ['code'=>'FAP-03','name'=>'Fire Alarm Panel 03','building_code'=>'NTA','floor'=>3,'category'=>'Fire Alarm','manufacturer'=>'Notifier','model_number'=>'NFS-3030','status'=>'active','health_score'=>100,'runtime_hours'=>17520,'protocol'=>'BACnet'],
            ['code'=>'FAP-04','name'=>'Fire Alarm Panel 04','building_code'=>'NTB','floor'=>4,'category'=>'Fire Alarm','manufacturer'=>'Notifier','model_number'=>'NFS-3030','status'=>'active','health_score'=>99,'runtime_hours'=>15000,'protocol'=>'BACnet'],
            ['code'=>'ACD-2F-12','name'=>'Access Door 2F-12','building_code'=>'NTA','floor'=>2,'category'=>'Access Control','manufacturer'=>'HID','model_number'=>'VertX EVO','status'=>'active','health_score'=>96,'runtime_hours'=>17520,'protocol'=>'API'],
            ['code'=>'ACD-3F-01','name'=>'Access Door 3F-01','building_code'=>'NTA','floor'=>3,'category'=>'Access Control','manufacturer'=>'HID','model_number'=>'VertX EVO','status'=>'offline','health_score'=>45,'runtime_hours'=>16000,'protocol'=>'API'],
            ['code'=>'ELV-01','name'=>'Elevator 01','building_code'=>'NTA','floor'=>1,'category'=>'Elevators','manufacturer'=>'Kone','model_number'=>'MonoSpace 500','status'=>'active','health_score'=>90,'runtime_hours'=>9000,'protocol'=>'Modbus'],
            ['code'=>'ELV-02','name'=>'Elevator 02','building_code'=>'NTA','floor'=>1,'category'=>'Elevators','manufacturer'=>'Kone','model_number'=>'MonoSpace 500','status'=>'active','health_score'=>88,'runtime_hours'=>8500,'protocol'=>'Modbus'],
            ['code'=>'PUMP-03','name'=>'Water Pump 03','building_code'=>'NTB','floor'=>1,'category'=>'Pumps','manufacturer'=>'Grundfos','model_number'=>'CR 45-8','status'=>'active','health_score'=>85,'runtime_hours'=>6500,'protocol'=>'Modbus'],
            ['code'=>'SENS-T01','name'=>'Temperature Sensor T01','building_code'=>'NTA','floor'=>4,'category'=>'Sensors','manufacturer'=>'Honeywell','model_number'=>'T6575','status'=>'active','health_score'=>100,'runtime_hours'=>17520,'protocol'=>'MQTT'],
            ['code'=>'SENS-H01','name'=>'Humidity Sensor H01','building_code'=>'NTA','floor'=>5,'category'=>'Sensors','manufacturer'=>'Honeywell','model_number'=>'HIH-5030','status'=>'active','health_score'=>99,'runtime_hours'=>17520,'protocol'=>'MQTT'],
            ['code'=>'PWR-MDB-01','name'=>'Main Distribution Board 01','building_code'=>'HQB','floor'=>1,'category'=>'Power','manufacturer'=>'Schneider','model_number'=>'Prisma G','status'=>'active','health_score'=>94,'runtime_hours'=>19000,'protocol'=>'Modbus'],
            ['code'=>'CAM-NTA-01','name'=>'Security Camera NTA-01','building_code'=>'NTA','floor'=>1,'category'=>'Security','manufacturer'=>'Hikvision','model_number'=>'DS-2CD2T47G2','status'=>'active','health_score'=>97,'runtime_hours'=>17000,'protocol'=>'API'],
            ['code'=>'AHU-INC-01','name'=>'AHU Innovation Center 01','building_code'=>'INC','floor'=>2,'category'=>'HVAC','manufacturer'=>'York','model_number'=>'YVFA','status'=>'active','health_score'=>91,'runtime_hours'=>5000,'protocol'=>'BACnet'],
        ];

        foreach ($equipmentList as $eq) {
            $building = Building::where('code', $eq['building_code'])->first();
            $floor = $building ? Floor::where('building_id', $building->id)->where('floor_number', $eq['floor'])->first() : null;
            $category = $categories[$eq['category']] ?? null;

            if ($building && $category) {
                Equipment::firstOrCreate(['code'=>$eq['code']], [
                    'name' => $eq['name'],
                    'building_id' => $building->id,
                    'floor_id' => $floor?->id,
                    'category_id' => $category->id,
                    'manufacturer' => $eq['manufacturer'],
                    'model_number' => $eq['model_number'],
                    'status' => $eq['status'],
                    'health_score' => $eq['health_score'],
                    'runtime_hours' => $eq['runtime_hours'],
                    'protocol' => $eq['protocol'],
                    'installation_date' => Carbon::now()->subYears(rand(1,5)),
                    'warranty_expiry' => Carbon::now()->addYears(rand(1,3)),
                    'last_communication' => Carbon::now()->subMinutes(rand(1,30)),
                ]);
            }
        }
    }
}
