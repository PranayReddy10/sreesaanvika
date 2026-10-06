<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Send one email, and say what happened.
 *
 * Mail on a shop fails in the worst possible way: silently, at the moment
 * somebody places an order, into a queue nobody is watching. This sends one
 * immediately — not queued — and prints what the mail server actually said, so
 * the settings can be got right before a customer is the one testing them.
 */
class TestEmail extends Command
{
    protected $signature = 'ojasvi:test-email {to : The address to send it to}
                            {--timeout=15 : Seconds to wait for the mail server}';

    protected $description = 'Send one email, and say whether it worked';

    public function handle(): int
    {
        $to = (string) $this->argument('to');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('That is not an email address.');

            return self::FAILURE;
        }

        /*
         * A wrong host or a blocked port does not answer "no" — it does not
         * answer at all, and PHP's default is to wait a minute for that. Fifteen
         * seconds is long enough for a mail server that is going to reply, and
         * short enough that somebody sitting at a terminal learns something.
         */
        config(['mail.mailers.smtp.timeout' => (int) $this->option('timeout') ?: 15]);
        Mail::purge('smtp');

        $host = config('mail.mailers.smtp.host');
        $from = config('mail.from.address');

        $this->line('');
        $this->line('  Sending through  <options=bold>'.($host ?: '(nothing configured)').'</>');
        $this->line('  as               <options=bold>'.($from ?: '(no from address)').'</>');
        $this->line('  to               <options=bold>'.$to.'</>');
        $this->line('');

        try {
            // Sent rather than queued: a queued one would say "fine" and fail
            // half an hour later in a log nobody opens.
            Mail::raw(
                "This is a test from your shop.\n\n"
                ."If you are reading it, order confirmations will reach your customers too.",
                fn ($message) => $message->to($to)->subject('Your shop can send email'),
            );
        } catch (\Throwable $e) {
            $this->error('  It did not go.');
            $this->line('');
            $this->line('  '.$e->getMessage());
            $this->line('');
            $this->line('  Nine times in ten: the password, or the wrong port for the');
            $this->line('  security chosen — 465 with SSL, 587 with TLS. Nothing at all');
            $this->line('  from the server usually means the port is blocked.');

            return self::FAILURE;
        }

        $this->info('  Gone. Look in that inbox, and in its spam folder.');

        return self::SUCCESS;
    }
}
