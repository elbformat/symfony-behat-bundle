<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Mailer;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\RawMessage;

class TestTransport implements TransportInterface
{
    /** @var Email[] */
    public static array $mails = [];

    public static function reset(): void
    {
        self::$mails = [];
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        $email = MessageConverter::toEmail($message);
        self::$mails[] = $email;

        $envelope = null !== $envelope ? clone $envelope : Envelope::create($message);
        return new SentMessage($message, $envelope);
    }

    public static function getMails(): array
    {
        return self::$mails;
    }

    public function __toString(): string
    {
        return 'test://test';
    }
}
