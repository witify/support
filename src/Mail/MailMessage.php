<?php

namespace Witify\Support\Mail;

use Illuminate\Notifications\Messages\MailMessage as BaseMailMessage;
use Illuminate\Support\HtmlString;
use Witify\Support\Host;

class MailMessage extends BaseMailMessage
{
    public function __construct(mixed $notifiable)
    {
        if ($notifiable->first_name ?? null) {
            $this->greeting(
                __('support::mail.greeting', [
                    'user' => $notifiable->first_name,
                ])
            );
        }

        $this->salutation(new HtmlString(__('support::mail.regards') . ',<br>' . e((string) Host::companyName())));
    }
}
