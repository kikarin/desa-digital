<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $code;
    protected int $expiresInMinutes;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $code, int $expiresInMinutes = 10)
    {
        $this->code            = $code;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode OTP Login Akun ' . config('app.name'))
            ->greeting('Halo ' . ($notifiable->name ?? 'Pengguna') . ',')
            ->line('Berikut adalah kode OTP untuk masuk ke akun Anda:')
            ->line('**' . $this->code . '**')
            ->line('Kode ini berlaku selama ' . $this->expiresInMinutes . ' menit sejak email ini dikirim.')
            ->line('Jangan berikan kode ini kepada siapa pun.')
            ->line('Jika Anda tidak merasa melakukan proses login, Anda dapat mengabaikan email ini.')
            ->salutation('Salam hangat, ' . config('app.name'));
    }
}

