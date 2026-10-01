<?php

namespace FrameworkDocblockTypes;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Mail\Attachment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

function migrate(Migrator $migrator, ?string $connection): int
{
    $migrator->usingConnection(null, static fn () => 42);

    return $migrator->usingConnection($connection, static fn () => 42);
}

function notificationId(Notification $notification): string
{
    return $notification->id ?? 'pending';
}

/** @param resource $stream */
function prepareMail(MailMessage $mail, $stream): void
{
    $mail->view = null;
    $mail->from = ['sender@example.com'];
    $mail->from = [['sender@example.com' => 'Sender'], null, 'tracking' => 42];
    $mail->replyTo = array_filter($mail->replyTo, static fn (array $address): bool => $address[0] !== 'internal@example.com');
    $mail->replyTo = ['support' => [['support@example.com' => 'Support']]];
    $mail->attachments = array_filter($mail->attachments, static fn (array $attachment): bool => $attachment['file'] !== 'internal.pdf');
    $mail->attachments['invoice'] = ['file' => Attachment::fromData(static fn (): string => 'invoice', 'invoice.txt'), 'options' => [], 'tracking' => 42];
    $mail->rawAttachments['stream'] = ['data' => $stream, 'name' => 'invoice.txt', 'options' => [], 'tracking' => 42];
    $mail->rawAttachments = array_filter($mail->rawAttachments, static fn (array $attachment): bool => $attachment['name'] !== 'internal.txt');
}
