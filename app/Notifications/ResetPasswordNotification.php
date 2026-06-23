<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

/**
 * Custom password-reset notification.
 *
 * OWF-062: the default Laravel notification builds the reset link from APP_URL
 * (the backend), but OwFinance owns the reset UI in the SPA at /reset-password.
 * This notification points the email button to {FRONTEND_URL}/reset-password with
 * the token + email as query params, which ResetPasswordPage.vue consumes.
 */
class ResetPasswordNotification extends BaseResetPassword
{
    use Queueable;

    /**
     * Build the SPA reset URL.
     */
    protected function spaResetUrl($notifiable): string
    {
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return $base
            . '/reset-password'
            . '?token=' . $this->token
            . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = $this->spaResetUrl($notifiable);

        $expire = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject(Lang::get('Restablece tu contraseña en OwFinance'))
            ->line(Lang::get('Recibiste este correo porque solicitaste restablecer la contraseña de tu cuenta.'))
            ->action(Lang::get('Restablecer contraseña'), $url)
            ->line(Lang::get('Este enlace expirará en :count minutos.', ['count' => $expire]))
            ->line(Lang::get('Si no solicitaste este cambio, puedes ignorar este correo y tu contraseña seguirá igual.'));
    }
}
