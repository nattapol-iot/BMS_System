<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Core\Settings\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key'=>'app_name','value'=>'Nexus BMS Platform','group'=>'general'],
            ['key'=>'company_name','value'=>'Nexus Corporation','group'=>'general'],
            ['key'=>'company_address','value'=>'88 Sukhumvit Road, Bangkok 10110','group'=>'general'],
            ['key'=>'company_phone','value'=>'+66 2-000-0000','group'=>'general'],
            ['key'=>'company_email','value'=>'support@nexus.com','group'=>'general'],
            ['key'=>'timezone','value'=>'Asia/Bangkok','group'=>'general'],
            ['key'=>'date_format','value'=>'d/m/Y','group'=>'display'],
            ['key'=>'time_format','value'=>'H:i','group'=>'display'],
            ['key'=>'currency','value'=>'THB','group'=>'display'],
            ['key'=>'currency_symbol','value'=>'฿','group'=>'display'],
            ['key'=>'electricity_rate','value'=>'4.50','group'=>'energy'],
            ['key'=>'water_rate','value'=>'25.00','group'=>'energy'],
            ['key'=>'solar_feedin_rate','value'=>'2.20','group'=>'energy'],
            ['key'=>'energy_unit','value'=>'kWh','group'=>'units'],
            ['key'=>'water_unit','value'=>'m3','group'=>'units'],
            ['key'=>'temperature_unit','value'=>'°C','group'=>'units'],
            ['key'=>'email_notifications','value'=>'1','group'=>'notifications'],
            ['key'=>'alarm_email','value'=>'alarm@nexus.com','group'=>'notifications'],
            ['key'=>'auto_acknowledge_hours','value'=>'24','group'=>'alarms'],
            ['key'=>'backup_enabled','value'=>'1','group'=>'backup'],
            ['key'=>'backup_frequency','value'=>'daily','group'=>'backup'],
            ['key'=>'session_timeout','value'=>'120','group'=>'security'],
            ['key'=>'max_login_attempts','value'=>'5','group'=>'security'],
            ['key'=>'theme','value'=>'light','group'=>'display'],
            ['key'=>'default_locale','value'=>'th','group'=>'general'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(['key'=>$setting['key']], array_merge($setting,['type'=>'string']));
        }
    }
}
