<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Hook\BeforeScenario;
use Behat\Step\Then;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use Elbformat\SymfonyBehatBundle\Mailer\Attachment;
use Elbformat\SymfonyBehatBundle\Mailer\TestTransport;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;

class MailerContext implements Context
{
    protected Email|null $lastMail = null;

    protected Attachment|null $lastAttachment = null;

    /** @var Email[]|null */
    protected ?array $mails = null;

    protected KernelInterface $kernel;

    protected string $mailPath;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /* Purge the spool folder between each scenario. */
    #[BeforeScenario]
    public function reset(): void
    {
        TestTransport::reset();
    }

    #[Then('an e-mail is being sent to :recipient with subject :subject')]
    public function anEmailIsBeingSentToWithSubject(string $recipient, string $subject): void
    {
        $mails = TestTransport::getMails();
        foreach ($mails as $mail) {
            if ($subject !== $mail->getEnvelope()->getSubject()) {
                continue;
            }
            foreach ($mail->getTo() as $to) {
                if ($to->getAddress() === $recipient) {
                    // Match
                    $this->lastMail = $mail;

                    return;
                }
            }
        }
        throw new \DomainException('Did you mean: '.$this->getMailsDump($mails));
    }

    #[Then('no e-mail is being sent')]
    #[Then('no e-mail is being sent to :recipient with subject :subject')]
    public function noEmailIsBeingSent(?string $recipient = null, ?string $subject = null): void
    {
        $mails = $this->getMails();
        foreach ($mails as $mail) {
            if ($subject && $subject !== $mail->getSubject()) {
                continue;
            }
            foreach (array_keys($mail->getTo()) as $to) {
                if (!$recipient || $to === $recipient) {
                    // Match
                    throw new \DomainException('Mails found: '.$this->getMailsDump($mails));
                }
            }
        }
    }

    #[Then('the e-mail contains')]
    #[Then('the e-mail contains :text')]
    public function theEMailContains(string $text = null, PyStringNode $stringNode = null): void
    {
        $mailText = $this->getLastMail()->getHtmlBody();
        $textToFind = $text ?? ($stringNode ? $stringNode->getRaw() : '');
        $strcomp = new StringCompare();
        if (!$strcomp->stringContains($mailText, $textToFind)) {
            throw new \DomainException($mailText);
        }
    }

    #[Then('the e-mail does not contain')]
    #[Then('the e-mail does not contain :text')]
    public function theEMailDoesNotContain(string $text = null, PyStringNode $stringNode = null): void
    {
        $mailText = $this->getLastMail()->getHtmlBody();
        $textToFind = $text ?? ($stringNode ? $stringNode->getRaw() : '');
        $strcomp = new StringCompare();
        if ($strcomp->stringContains($mailText, $textToFind)) {
            throw new \DomainException('Text found!');
        }
    }

    #[Then('the e-mail is also being sent to :to')]
    public function theEMailIsAlsoBeingSentTo(string $to): void
    {
        $recipients = $this->getLastMail()->getTo();
        foreach ($recipients as $recipient) {
            if ($to === $recipient->getAddress()) {
                return;
            }
        }
        throw new \DomainException(implode(',', $recipients));
    }

    #[Then('the e-mail is being sent from :from')]
    public function theEMailIsBeingSentFrom(string $from): void
    {
        /** @var array|string $realyFrom */
        $realyFrom = $this->getLastMail()->getSender()->getAddress();
        if ($realyFrom !== $from) {
            throw new \DomainException((string) $realyFrom);
        }
    }

    #[Then('the e-mail has an attachment :name')]
    public function theEMailHasAnAttachment(string $name): void
    {
        $attachments = $this->getLastMail()->getAttachments();
        foreach ($attachments as $attachment) {
            $filename = $attachment->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename');
            if ($name === $filename) {
                $this->lastAttachment = new Attachment($filename, $attachment->getBody());

                return;
            }
        }
        throw new \DomainException(sprintf('No attachment with name %s found.', $name));
    }

    #[Then('the e-mail attachment equals fixture :fixture')]
    public function theEMailAttachmentEqualsFixture(string $fixture): void
    {
        if (!file_exists($fixture)) {
            throw new \DomainException('Fixture not found');
        }

        $algo = 'md5';
        $attachmentPath = $this->mailPath.'/'.$this->lastAttachment->getFilename();
        file_put_contents($attachmentPath, $this->lastAttachment->getBody());

        if (hash_file($algo, $attachmentPath) !== hash_file($algo, $fixture)) {
            unlink($attachmentPath);
            throw new \DomainException(sprintf('Attachment with name %s does not match fixture.', $this->lastAttachment->getFilename()));
        }
    }

    /** @param Email[] $mails */
    protected function getMailsDump(array $mails): string
    {
        if (!\count($mails)) {
            return '-no mails sent-';
        }
        $mailText = [''];
        foreach ($mails as $mail) {
            $froms = [];
            foreach ($mail->getFrom() as $from) {
                $froms[] = $from->getAddress();
            }
            $tos = [];
            foreach ($mail->getTo() as $to) {
                $tos[] = $to->getAddress();
            }
            $mailText[] = sprintf("From: %s\n  To: %s\n  Subject: %s", implode(',', $froms), implode(',', $tos), $mail->getSubject());
        }

        return implode("\n  ---\n  ", $mailText);
    }

    protected function getLastMail(): Email
    {
        if (null === $this->lastMail) {
            throw new \DomainException('Please identify mail by recipient and subject first');
        }

        return $this->lastMail;
    }

    /** @return Email[] */
    protected function getMails(): array
    {
        if (null !== $this->mails) {
            return $this->mails;
        }
        $finder = new Finder();
        $finder->files()->in($this->mailPath);
        $mails = [];
        foreach ($finder as $file) {
            /** @var SentMessage $sentMessage */
            $sentMessage = unserialize($file->getContents());
            if (!$sentMessage->getOriginalMessage() instanceof Email) {
                continue;
            }
            $mails[] = $sentMessage->getOriginalMessage();
        }
        $this->mails = $mails;

        return $mails;
    }
}