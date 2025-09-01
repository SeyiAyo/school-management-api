<?php

namespace App\Notifications;

use App\Services\OtpService;
use App\Services\ResendEmailService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomVerifyEmail extends Notification
{

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // Generate OTP for email verification
        $otpService = app(OtpService::class);
        $otp = $otpService->generateOtp($notifiable, $notifiable->getEmailForVerification());
        
        // Use ResendEmailService to send the OTP email
        $resendService = app(ResendEmailService::class);
        
        $result = $resendService->sendEmailVerification(
            $notifiable->getEmailForVerification(),
            $otp->otp_code,
            $notifiable->name
        );

        if ($result === false) {
            throw new \Exception('Failed to send OTP email verification');
        }

        // Return a dummy MailMessage since we're handling the email sending ourselves
        return (new MailMessage)
            ->subject('Email Verification - OTP Sent')
            ->line('OTP verification code has been sent via ResendEmailService');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }
}
