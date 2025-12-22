<?php

namespace SavyApps\LaravelStudio\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use SavyApps\LaravelStudio\Services\EmailTemplateService;

class TemplatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected string $templateKey,
        protected array $data = []
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        try {
            $service = app(EmailTemplateService::class);

            // Merge notifiable data (user) with provided data
            $data = array_merge($this->data, [
                'user' => $notifiable,
                'notifiable' => $notifiable,
            ]);

            $rendered = $service->render($this->templateKey, $data);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->html($rendered['html']);

        } catch (\Exception $e) {
            Log::error('Failed to send templated notification', [
                'template_key' => $this->templateKey,
                'notifiable_id' => $notifiable->id ?? null,
                'error' => $e->getMessage(),
            ]);

            // Fallback to a simple message
            return (new MailMessage)
                ->subject('Notification from ' . config('app.name'))
                ->line('We were unable to render the email template.')
                ->line('Please contact support if you need assistance.');
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'template_key' => $this->templateKey,
            'data' => $this->data,
        ];
    }
}
