# MailerContext
Checks if mails were sent. 
For this we use a Testmailer, that is configured via DSN `test://test` inside `.env.test`.

## Then
### `an e-mail is being sent to :recipient with subject <subject>`
Check if email was sent.
### `no e-mail is being sent`
No mails sent overall.
### `no e-mail is being sent to :recipient with subject <subject>`
Check if this special mail was not sent. 
### `the e-mail contains <text>`
Check contents of the mail.
### `the e-mail does not contain <text>`
Check contents not in the mail.
### `the e-mail is also being sent to <to>`
Check additional cc recipients.
### `the e-mail is being sent from <from>`
Check mail sender.
### `the e-mail has an attachment <name>`
Check if attachment is present.
### `the e-mail attachment equals fixture <fixture>`
Check if attachment sames the fixture (file relative to project root).
