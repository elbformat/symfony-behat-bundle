<?php

namespace Context;

use Behat\Gherkin\Node\PyStringNode;
use Elbformat\SymfonyBehatBundle\Context\MailerContext;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use Elbformat\SymfonyBehatBundle\Mailer\TestTransport;
use Elbformat\SymfonyBehatBundle\Tests\Context\ExpectNotToPerformAssertionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailerContextTest extends TestCase
{
    use ExpectNotToPerformAssertionTrait;

    protected ?KernelInterface $kernel = null;
    protected ?MailerContext $mailerContext = null;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->mailerContext = new MailerContext($this->kernel, new StringCompare(), __DIR__.'/../..');
        $this->mailerContext->reset();
    }

    public function testAnEmailIsBeingSentToWithSubject(): void
    {
        $this->send();
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Lorem Ipsum');
        $this->expectNotToPerformAssertions();
    }

    public function testAnEmailIsBeingSentToWithSubjectFail(): void
    {
        $this->expectExceptionMessage('Mail not found');
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Lorem Ipsum');
    }

    public function testAnEmailIsBeingSentToWithSubjectFailRecipient(): void
    {
        $this->expectExceptionMessage('Mail not found');
        $this->send();
        $this->mailerContext->anEmailIsBeingSentToWithSubject('test@format-h.com', 'Lorem Ipsum');
    }

    public function testAnEmailIsBeingSentToWithSubjectFailSubject(): void
    {
        $this->expectExceptionMessage('Mail not found');
        $this->expectExceptionMessage('Did you mean');
        $this->expectExceptionMessage('From: sender@format-h.com');
        $this->expectExceptionMessage('To: recipient@format-h.com');
        $this->expectExceptionMessage('Subject: Lorem Ipsum');
        $this->send();
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Dolor sit');
    }

    public function testNoEmailIsBeingSent(): void
    {
        $this->mailerContext->noEmailIsBeingSent();
        $this->expectNotToPerformAssertions();
    }

    public function testNoEmailIsBeingSentSubject(): void
    {
        $this->send();
        $this->mailerContext->noEmailIsBeingSent('Dolor sit');
        $this->expectNotToPerformAssertions();
    }

    public function testNoEmailIsBeingSentRecipient(): void
    {
        $this->send();
        $this->mailerContext->noEmailIsBeingSent(recipient: 'test@format-h.com');
        $this->expectNotToPerformAssertions();
    }

    public function testNoEmailIsBeingSentFail(): void
    {
        $this->send();
        $this->expectExceptionMessage('Mails found');
        $this->expectExceptionMessage('From: sender@format-h.com');
        $this->expectExceptionMessage('To: recipient@format-h.com');
        $this->expectExceptionMessage('Subject: Lorem Ipsum');
        $this->mailerContext->noEmailIsBeingSent();
    }

    /** @dataProvider theEMailContainsProvider */
    public function testTheEMailContains(?string $html, ?string $plain, string|PyStringNode $expected): void
    {
        $this->send(html: $html, plainText: $plain);
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Lorem Ipsum');
        $this->mailerContext->theEMailContains($expected);
        $this->expectNotToPerformAssertions();
    }

    public function theEMailContainsProvider(): array
    {
        return [
            ['Lorem Ipsum', null, 'rem Ips'],
            ['Lorem Ipsum', null, new PyStringNode(['rem Ips'], 0)],
            [null, 'Lorem Ipsum', 'rem Ips'],
            [null, 'Lorem Ipsum', new PyStringNode(['rem Ips'], 0)],
            ['Lorem Ipsum', 'Lorem Ipsum', 'rem Ips'],
            ['Lorem Ipsum', 'Lorem Ipsum', new PyStringNode(['rem Ips'], 0)],
        ];
    }

    /** @dataProvider theEMailContainsFailProvider */
    public function testTheEMailContainsFail(?string $html, ?string $plain, string|PyStringNode $expected): void
    {
        $this->send(html: $html, plainText: $plain);
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Lorem Ipsum');
        $this->expectExceptionMessage('Text not found');
        $this->mailerContext->theEMailContains($expected);
    }

    public function theEMailContainsFailProvider(): array
    {
        return [
            ['Lorem Ipsum', 'Lorem Ipsum', 'Dolor sit'],
            ['Lorem Ipsum', 'Lorem Ipsum', new PyStringNode(['Dolor sit'], 0)],
        ];
    }

    /** @dataProvider theEMailContainsFailProvider */
    public function testTheEMailDoesNotContain(?string $html, ?string $plain, string|PyStringNode $expected): void
    {
        $this->send(html: $html, plainText: $plain);
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Lorem Ipsum');
        $this->mailerContext->theEMailDoesNotContain($expected);
        $this->expectNotToPerformAssertions();
    }

    /** @dataProvider theEMailContainsProvider */
    public function testTheEMailDoesNotContainFail(?string $html, ?string $plain, string|PyStringNode $expected): void
    {
        $this->send(html: $html, plainText: $plain);
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Lorem Ipsum');
        $this->expectExceptionMessage('Text found!');
        $this->mailerContext->theEMailDoesNotContain($expected);
    }

    public function testTheEMailIsAlsoBeingSentTo(): void
    {
        $this->send(to: ['recipient@format-h.com', 'secondrecipient@format-h.com']);
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Lorem Ipsum');
        $this->mailerContext->theEMailIsAlsoBeingSentTo('secondrecipient@format-h.com');
        $this->expectNotToPerformAssertions();
    }

    public function testTheEMailIsAlsoBeingSentToFail(): void
    {
        $this->send();
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Lorem Ipsum');
        $this->expectExceptionMessage('Found recipients');
        $this->expectExceptionMessage('recipient@format-h.com');
        $this->mailerContext->theEMailIsAlsoBeingSentTo('secondrecipient@format-h.com');
    }

    public function testTheEMailIsBeingSentFrom(): void
    {
        $this->send();
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Lorem Ipsum');
        $this->mailerContext->theEMailIsBeingSentFrom('sender@format-h.com');
        $this->expectNotToPerformAssertions();
    }

    public function testTheEMailIsBeingSentFromFail(): void
    {
        $this->send();
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Lorem Ipsum');
        $this->expectExceptionMessage('sender@format-h.com');
        $this->mailerContext->theEMailIsBeingSentFrom('recipient@format-h.com');
    }

    public function testTheEMailHasAnAttachment(): void
    {
        $this->send(attachment: __DIR__.'/../fixtures/1px.jpg');
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Lorem Ipsum');
        $this->mailerContext->theEMailHasAnAttachment('1px.jpg');
        $this->expectNotToPerformAssertions();
    }

    public function testTheEMailHasAnAttachmentFail(): void
    {
        $this->send();
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Lorem Ipsum');
        $this->expectExceptionMessage('No attachment with name 1px.jpg found.');
        $this->mailerContext->theEMailHasAnAttachment('1px.jpg');
    }

    public function testTheEMailAttachmentEqualsFixture(): void
    {
        $this->send(attachment: __DIR__.'/../fixtures/1px.jpg');
        $this->mailerContext->anEmailIsBeingSentToWithSubject('recipient@format-h.com', 'Lorem Ipsum');
        $this->mailerContext->theEMailHasAnAttachment('1px.jpg');
        $this->mailerContext->theEMailAttachmentEqualsFixture('tests/fixtures/1px.jpg');
        $this->expectNotToPerformAssertions();
    }

    protected function send(
        string $from = 'sender@format-h.com',
        string|array $to = 'recipient@format-h.com',
        string $subject = 'Lorem Ipsum',
        ?string $html = 'Lorem Ipsum',
        ?string $plainText = null,
        ?string $attachment = null,
    ): void {
        if (is_string($to)) {
            $to = [$to];
        }
        $transport = new TestTransport();
        $message = new Email();
        $envelope = new Envelope(new Address($from), [new Address($to[0])]);
        $message->subject($subject);
        $message->html($html);
        $message->text($plainText);
        $message->from(new Address($from));
        $message->sender(new Address($from));
        foreach ($to as $t) {
            $message->addTo($t);
        }
        if (null !== $attachment) {
            $message->attach(file_get_contents($attachment), basename($attachment));
        }
        $transport->send($message, $envelope);
    }

}
