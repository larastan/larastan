<?php

namespace FacadeFakeTypes;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use function PHPStan\Testing\assertType;

function queue(): void
{
    assertType('null', Queue::assertPushed('FooJob'));
    assertType('null', Queue::assertNothingPushed());
    assertType('Illuminate\Support\Collection', Queue::pushed('FooJob'));
    assertType('bool', Queue::hasPushed('FooJob'));
}

function others(object $notifiable): void
{
    assertType('null', Bus::assertDispatched('FooJob'));
    assertType('null', Mail::assertSent('FooMailable'));
    assertType('null', Notification::assertSentTo($notifiable, 'FooNotification'));
}
