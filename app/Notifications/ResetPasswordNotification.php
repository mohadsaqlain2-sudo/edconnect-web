<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Branded password reset email (FR-3).
 *
 * It extends the framework notification so the token, expiry and reset URL
 * logic stay exactly the same - only the look of the email changes.
 */
class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * Build the reset email with our own Markdown template.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your EduConnect password')
            ->markdown('emails.reset-password', [
                'url' => $this->resetUrl($notifiable),
                'name' => $notifiable->name,
                'expires' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]);
    }
}
