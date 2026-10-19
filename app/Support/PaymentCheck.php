<?php

namespace App\Support;

use App\Services\Payments\Cashfree;
use App\Services\Payments\Gateways;
use Illuminate\Support\Facades\Http;

/**
 * Do these keys actually work?
 *
 * A mistyped payment key is the most expensive typing mistake a shop can
 * make, because nothing looks wrong until a shopper has filled in their
 * address, chosen to pay, and been shown an error on the last page of the
 * sale. This asks the gateway a question that costs nothing — about an order
 * that does not exist — purely to see whether it answers "who are you?" or
 * "never heard of it". The second answer is the good one.
 *
 * Nothing here can take money, move money or change anything at the gateway.
 */
final class PaymentCheck
{
    private function __construct(
        public readonly bool $ok,
        public readonly string $summary,
        public readonly ?string $detail = null,
        public readonly ?string $advice = null,
    ) {}

    public static function run(): self
    {
        $gateway = app(Gateways::class)->active();

        if (! $gateway->configured()) {
            return new self(
                ok: false,
                summary: $gateway->label().' has not been given both its keys yet.',
                advice: 'Fill in both boxes and save again. Until then, checkout offers cash on delivery only.',
            );
        }

        return match ($gateway->name()) {
            'cashfree' => self::cashfree(),
            default    => self::razorpay(),
        };
    }

    private static function cashfree(): self
    {
        $cashfree = app(Cashfree::class);
        $live = $cashfree->sandbox() ? 'test' : 'live';

        try {
            // An order id nothing could have been bought under.
            $response = $cashfree->http()->get($cashfree->base().'/orders/ojasvi-key-check-0');
        } catch (\Throwable $e) {
            return new self(
                ok: false,
                summary: 'Cashfree could not be reached from this server.',
                detail: $e->getMessage(),
                advice: 'Shared hosting sometimes blocks outgoing connections. Ask the host to allow api.cashfree.com.',
            );
        }

        // 404 is the right answer: it found no such order, which it could only
        // say after accepting who was asking.
        if ($response->status() === 404) {
            return new self(
                ok: true,
                summary: 'Cashfree accepted the '.$live.' keys.',
            );
        }

        if (in_array($response->status(), [401, 403], true)) {
            return new self(
                ok: false,
                summary: 'Cashfree would not accept these keys.',
                detail: (string) $response->json('message'),
                advice: $cashfree->sandbox()
                    ? 'These are being used against Cashfree\'s test site. Live keys will not work there — switch "Which keys these are" to live.'
                    : 'Live keys are used here. If these came from the test environment, switch "Which keys these are" to test.',
            );
        }

        if ($response->successful()) {
            return new self(ok: true, summary: 'Cashfree accepted the '.$live.' keys.');
        }

        return new self(
            ok: false,
            summary: 'Cashfree answered '.$response->status().'.',
            detail: (string) $response->json('message'),
        );
    }

    private static function razorpay(): self
    {
        try {
            $response = Http::withBasicAuth(
                (string) config('services.razorpay.key'),
                (string) config('services.razorpay.secret'),
            )->timeout(20)->get('https://api.razorpay.com/v1/payments', ['count' => 1]);
        } catch (\Throwable $e) {
            return new self(
                ok: false,
                summary: 'Razorpay could not be reached from this server.',
                detail: $e->getMessage(),
                advice: 'Shared hosting sometimes blocks outgoing connections. Ask the host to allow api.razorpay.com.',
            );
        }

        if ($response->successful()) {
            return new self(ok: true, summary: 'Razorpay accepted the keys.');
        }

        if (in_array($response->status(), [401, 403], true)) {
            return new self(
                ok: false,
                summary: 'Razorpay would not accept these keys.',
                detail: (string) $response->json('error.description'),
                advice: 'The key id and the secret have to be a pair, and a test key id will not work with a live secret.',
            );
        }

        return new self(
            ok: false,
            summary: 'Razorpay answered '.$response->status().'.',
            detail: (string) $response->json('error.description'),
        );
    }
}
