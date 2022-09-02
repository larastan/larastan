<?php

declare(strict_types=1);

namespace App;

use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;

class Mail extends Mailable
{
    public function __construct(
        private string $test,
    ) {
    }

    public function build(): self
    {
        $message = new MailMessage();
        $message->line('Test line '.$this->test);

        return $this
            ->subject('Test '.$this->test)
            ->markdown($message->markdown, $message->data());
    }
}
