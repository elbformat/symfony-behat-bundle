<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Hook\BeforeScenario;
use Behat\Step\Then;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use Elbformat\SymfonyBehatBundle\Mailer\TestTransport;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class MailerContext implements Context
{
    protected ?Email $lastMail = null;
    protected ?DataPart $lastAttachment = null;

    public function __construct(
        protected KernelInterface $kernel,
        protected StringCompare $strComp,
        protected string $projectDir,
    ) {
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
            if ($subject !== $mail->getSubject()) {
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
        throw new \DomainException('Mail not found. Did you mean: '.$this->getMailsDump($mails));
    }

    #[Then('no e-mail is being sent')]
    #[Then('no e-mail is being sent to :recipient with subject :subject')]
    public function noEmailIsBeingSent(?string $recipient = null, ?string $subject = null): void
    {
        $mails = TestTransport::getMails();
        foreach ($mails as $mail) {
            if ($subject && $subject !== $mail->getSubject()) {
                continue;
            }
            foreach (array_keys($mail->getTo()) as $to) {
                if (!$recipient || $to === $recipient) {
                    // Match
                    throw new \DomainException('Mails found: '.$this->getMailsDump([$mail]));
                }
            }
        }
    }

    #[Then('the e-mail contains')]
    #[Then('the e-mail contains :text')]
    public function theEMailContains(string $text = null, PyStringNode $stringNode = null): void
    {
        $mailText = (string) ($this->getLastMail()->getHtmlBody() ?? $this->getLastMail()->getTextBody());
        $textToFind = $text ?? ($stringNode ? $stringNode->getRaw() : '');
        if (!$this->strComp->stringContains($mailText, $textToFind)) {
            throw new \DomainException('Text not found in: '.$mailText);
        }
    }

    #[Then('the e-mail does not contain')]
    #[Then('the e-mail does not contain :text')]
    public function theEMailDoesNotContain(string $text = null, PyStringNode $stringNode = null): void
    {
        $mailText = $this->getLastMail()->getHtmlBody() ?? $this->getLastMail()->getTextBody();
        $textToFind = $text ?? ($stringNode ? $stringNode->getRaw() : '');
        if ($this->strComp->stringContains((string) $mailText, $textToFind)) {
            throw new \DomainException('Text found!');
        }
    }

    #[Then('the e-mail is also being sent to :to')]
    public function theEMailIsAlsoBeingSentTo(string $to): void
    {
        $recipients = $this->getLastMail()->getTo();
        $tos = [];
        foreach ($recipients as $recipient) {
            if ($to === $recipient->getAddress()) {
                return;
            }
            $tos[] = $recipient->getAddress();
        }
        throw new \DomainException('Found recipients: '.implode(',', $tos));
    }

    #[Then('the e-mail is being sent from :from')]
    public function theEMailIsBeingSentFrom(string $from): void
    {
        $reallyFrom = $this->getLastMail()->getSender()?->getAddress() ?? '';
        if ($reallyFrom !== $from) {
            throw new \DomainException($reallyFrom);
        }
    }

    #[Then('the e-mail has an attachment :name')]
    public function theEMailHasAnAttachment(string $name): void
    {
        $attachments = $this->getLastMail()->getAttachments();
        foreach ($attachments as $attachment) {
            $filename = $attachment->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename');
            if ($name === $filename) {
                $this->lastAttachment = $attachment;

                return;
            }
        }
        throw new \DomainException(sprintf('No attachment with name %s found.', $name));
    }

    #[Then('the e-mail attachment equals fixture :fixture')]
    public function theEMailAttachmentEqualsFixture(string $fixture): void
    {
        if (!file_exists($this->projectDir.'/'.$fixture)) {
            throw new \DomainException('Fixture not found');
        }
        $fixtureHash = hash_file('md5', $this->projectDir.'/'.$fixture);

        if (null === $this->lastAttachment) {
            throw new \DomainException('Please check the attachment first with "the e-mail has an attachment"');
        }
        $attachmentHash = md5(base64_decode($this->lastAttachment->bodyToString()));

        if ($fixtureHash !== $attachmentHash) {
            throw new \DomainException(sprintf('Attachment with name %s does not match fixture.', $this->lastAttachment->getFilename() ?? ''));
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
            $mailText[] = sprintf("From: %s\n  To: %s\n  Subject: %s", implode(',', $froms), implode(',', $tos), $mail->getSubject() ?? '');
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
}
