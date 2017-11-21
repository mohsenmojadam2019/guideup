<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends Notification
{

     /**
     * The user name for presentation.
     *
     * @var string
     */

    public $username;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token, $username)
    {
	$this->token = $token;
        $this->username = $username;
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

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Resetar senha Guide Up')
	    ->greeting(utf8_encode('Olá '.$this->username.','))
            ->line(utf8_encode('Você está recebendo esse email porque nós recebemos uma solicitação de redefinição de senha para essa conta.'))
            ->action('Alterar senha', url('password/reset/'.$this->token))
            ->line(utf8_encode('Caso não tenha sido você quem solicitou a redefinição da senha fique tranquilo e apenas ignore esse email.'));
    }
}
