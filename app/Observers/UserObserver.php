<?php
namespace App\Observers;

use App\Models\Entities\AiUserSetting;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        AiUserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'advisor_name'          => 'Asesor IA',
                'advisor_personality'   => 'friendly',
                'voice_enabled'         => true,
                'ocr_enabled'           => true,
                'auto_ia_enabled'       => true,
                'advisor_enabled'       => true,
                'preferred_currency'    => 'USD',
                'context_window_months' => 3,
            ]
        );
    }
}
