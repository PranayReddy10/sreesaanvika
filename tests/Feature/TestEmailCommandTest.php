<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Mail\Transport\ArrayTransport;
use Tests\TestCase;

/**
 * The command that answers "can this shop send email at all?".
 *
 * It exists because the real answer otherwise arrives as a customer who never
 * got a confirmation. So: one message, sent and not queued, and a plain account
 * of what the mail server said when it refuses.
 */
class TestEmailCommandTest extends TestCase
{
    /*
     * Held in memory rather than faked: Mail::fake() records nothing for a raw
     * message, so a test written against it passes whether the command sends
     * anything or not.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.default' => 'array']);

        Queue::fake();
    }

    private function transport(): ArrayTransport
    {
        return Mail::mailer('array')->getSymfonyTransport();
    }

    public function test_it_sends_one_message_there_and_then(): void
    {
        $this->artisan('ojasvi:test-email', ['to' => 'shop@example.test'])
            ->expectsOutputToContain('Gone.')
            ->assertSuccessful();

        $sent = $this->transport()->messages();

        $this->assertCount(1, $sent);
        $this->assertStringContainsString('shop@example.test', $sent->first()->getOriginalMessage()->getTo()[0]->toString());

        // Not queued: a queued message would report success here and fail half
        // an hour later in a log nobody opens, which is the whole point of the
        // command. With the queue faked, anything queued would never have
        // reached the transport above and nothing would be sent.
        Queue::assertNothingPushed();
    }

    public function test_it_refuses_something_that_is_not_an_address(): void
    {
        $this->artisan('ojasvi:test-email', ['to' => 'care at ojasvidrapes'])
            ->assertFailed();

        $this->assertCount(0, $this->transport()->messages());
    }
}
