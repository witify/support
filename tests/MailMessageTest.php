<?php

namespace Witify\Support\Tests;

use Witify\Support\Host;
use Witify\Support\Mail\MailMessage;

class MailMessageTest extends TestCase
{
    protected function tearDown(): void
    {
        Host::forgetResolvers();

        parent::tearDown();
    }

    public function test_it_greets_the_recipient_and_signs_with_the_company_name(): void
    {
        $mail = new MailMessage($this->user('Ada'));

        $this->assertSame('Good day Ada,', $mail->greeting);
        $this->assertSame('Regards,<br>Client Example', (string) $mail->salutation);
    }

    public function test_the_company_name_comes_from_the_host_resolver_before_the_config(): void
    {
        config()->set('support.company_name', 'Config <Co>');

        $this->assertSame('Regards,<br>Config &lt;Co&gt;', (string) (new MailMessage($this->user()))->salutation);

        Host::resolveCompanyNameUsing(fn (): string => 'Settings Co');

        $this->assertSame('Regards,<br>Settings Co', (string) (new MailMessage($this->user()))->salutation);
    }

    public function test_a_recipient_without_first_name_gets_no_greeting(): void
    {
        $mail = new MailMessage((object) ['email' => 'someone@example.test']);

        $this->assertEmpty($mail->greeting);
    }
}
