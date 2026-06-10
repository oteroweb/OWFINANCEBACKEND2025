<?php
namespace Database\Seeders;

use App\Models\Entities\AiUserSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class AiUserSettingsSeeder extends Seeder
{
    public function run(): void
    {
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                AiUserSetting::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'advisor_name'           => 'Asesor IA',
                        'advisor_personality'    => 'friendly',
                        'voice_enabled'          => true,
                        'ocr_enabled'            => true,
                        'auto_ia_enabled'        => true,
                        'advisor_enabled'        => true,
                        'monthly_budget_alert'   => null,
                        'preferred_currency'     => 'USD',
                        'context_window_months'  => 3,
                    ]
                );
            }
        });
        $this->command->info('AiUserSettings seeded for all existing users.');
    }
}
