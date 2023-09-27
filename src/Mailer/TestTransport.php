<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Mailer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

class TestTransport implements TransportInterface
{
    public function __construct(private readonly string $mailPath)
    {
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        $envelope = null !== $envelope ? clone $envelope : Envelope::create($message);
        $sentMessage = new SentMessage($message, $envelope);
        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->mailPath.'/'.$sentMessage->getMessageId(), serialize($sentMessage));

        return $sentMessage;
    }

    public function __toString(): string
    {
        return 'test://'.$this->mailPath;
    }
}
