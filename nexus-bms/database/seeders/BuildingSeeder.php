<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;

class BuildingSeeder extends Seeder
{
    public function run(): void
    {
        $buildings = [
            ['code'=>'NTA','name'=>'Nexus Tower A','name_th'=>'เนกซัส ทาวเวอร์ เอ','address'=>'88 Sukhumvit Rd','city'=>'Bangkok','floors_count'=>12,'total_area'=>45000,'year_built'=>2018,'status'=>'active','occupancy_count'=>850,'occupancy_capacity'=>1200],
            ['code'=>'NTB','name'=>'Nexus Tower B','name_th'=>'เนกซัส ทาวเวอร์ บี','address'=>'90 Sukhumvit Rd','city'=>'Bangkok','floors_count'=>10,'total_area'=>38000,'year_built'=>2019,'status'=>'active','occupancy_count'=>620,'occupancy_capacity'=>1000],
            ['code'=>'INC','name'=>'Innovation Center','name_th'=>'นวัตกรรม เซ็นเตอร์','address'=>'500 Rama 9 Rd','city'=>'Bangkok','floors_count'=>5,'total_area'=>18000,'year_built'=>2021,'status'=>'active','occupancy_count'=>320,'occupancy_capacity'=>500],
            ['code'=>'HQB','name'=>'HQ Building','name_th'=>'อาคารสำนักงานใหญ่','address'=>'200 Sathorn Rd','city'=>'Bangkok','floors_count'=>15,'total_area'=>62000,'year_built'=>2015,'status'=>'active','occupancy_count'=>1100,'occupancy_capacity'=>1500],
            ['code'=>'WHA','name'=>'Warehouse Admin','name_th'=>'คลังสินค้า แอดมิน','address'=>'45 Industrial Rd','city'=>'Samut Prakan','floors_count'=>3,'total_area'=>25000,'year_built'=>2020,'status'=>'active','occupancy_count'=>80,'occupancy_capacity'=>150],
            ['code'=>'RDC','name'=>'R&D Center','name_th'=>'ศูนย์วิจัยและพัฒนา','address'=>'100 Science Park','city'=>'Pathumthani','floors_count'=>6,'total_area'=>22000,'year_built'=>2022,'status'=>'active','occupancy_count'=>180,'occupancy_capacity'=>250],
        ];

        foreach ($buildings as $bData) {
            $building = Building::firstOrCreate(['code'=>$bData['code']], $bData);
            $this->createFloors($building);
        }
    }

    private function createFloors(Building $building): void
    {
        $roomTypes = ['office','meeting','server','lobby','common','toilet'];
        for ($f = 1; $f <= $building->floors_count; $f++) {
            $floor = Floor::firstOrCreate(
                ['building_id'=>$building->id,'floor_number'=>$f],
                ['name'=>"Floor {$f}",'name_th'=>"ชั้น {$f}",'area'=>rand(2000,4000)]
            );
            // Create sample rooms
            $roomNames = ['Open Office','Meeting Room A','Meeting Room B','Server Room','Reception','Storage'];
            foreach (array_slice($roomNames, 0, rand(3, 6)) as $rname) {
                Room::firstOrCreate(
                    ['floor_id'=>$floor->id,'name'=>$rname],
                    ['type'=>$roomTypes[array_search($rname, $roomNames) % count($roomTypes)],'area'=>rand(50,300)]
                );
            }
        }
    }
}
