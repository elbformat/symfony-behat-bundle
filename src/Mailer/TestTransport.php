<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Mailer;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\RawMessage;

class TestTransport implements TransportInterface
{
    /** @var Email[] */
    public static array $mails = [];
    protected static bool $shouldFail = false;

    public static function reset(): void
    {
        self::$mails = [];
        self::$shouldFail = false;
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        if (self::$shouldFail) {
            throw new TransportException('Failed to send message');
        }
        if (!$message instanceof Message) {
            throw new \RuntimeException(sprintf('Mailer can only send messages, not %s', get_class($message)));
        }
        $email = MessageConverter::toEmail($message);
        self::$mails[] = $email;

        $envelope = null !== $envelope ? clone $envelope : Envelope::create($message);

        /** @psalm-suppress InternalMethod */
        return new SentMessage($message, $envelope);
    }

    /** @return Email[] */
    public static function getMails(): array
    {
        return self::$mails;
    }

    public static function shouldFail(): void
    {
        self::$shouldFail = true;
    }

    public function __toString(): string
    {
        return 'test://test';
    }
}
