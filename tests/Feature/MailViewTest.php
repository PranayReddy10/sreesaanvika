<?php

namespace Tests\Feature;

use App\Mail\EnquiryForShop;
use App\Mail\NewOrderForShop;
use App\Mail\OrderPaid;
use App\Mail\OrderPlaced;
use App\Mail\OrderShipped;
use App\Models\Enquiry;
use App\Models\Order;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Tests\TestCase;

/**
 * Every email the shop sends, rendered.
 *
 * Nothing about an order is allowed to break because email did, so a send that
 * throws is caught and written to the log — which is right, and which also
 * means a template that cannot render at all is invisible. That is not a
 * theory: `@if` written hard against the word before it is not a directive,
 * Blade left it as text and compiled its `@endif` alone, and every order
 * confirmation for weeks was a line in a log file nobody reads.
 *
 * So each one is built and rendered here, with a real order, which is the only
 * thing that catches it.
 */
class MailViewTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);

        $this->order = Order::with('items')->whereHas('shipment')->firstOrFail();
    }

    public static function emails(): array
    {
        return [
            'to the customer, placed'  => ['placed'],
            'to the customer, paid'    => ['paid'],
            'to the customer, shipped' => ['shipped'],
            'to the shop, new order'   => ['shop'],
            'to the shop, an enquiry'  => ['enquiry'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('emails')]
    public function test_it_renders(string $which): void
    {
        $mailable = match ($which) {
            'placed'  => new OrderPlaced($this->order),
            'paid'    => new OrderPaid($this->order),
            'shipped' => new OrderShipped($this->order),
            'shop'    => new NewOrderForShop($this->order),
            'enquiry' => new EnquiryForShop(Enquiry::create([
                'name' => 'Lakshmi', 'email' => 'lakshmi@example.in',
                'phone' => '9000000000', 'order_number' => 'OJ-2026-00001',
                'message' => 'Does the Kanjivaram come with a blouse piece?',
            ])),
        };

        $html = $this->render($mailable);

        $this->assertNotSame('', trim($html));

        // A directive left as text is the fault this test exists for, and it
        // shows up in the finished email rather than as an exception.
        $this->assertStringNotContainsString('@if', $html);
        $this->assertStringNotContainsString('@endif', $html);
        $this->assertStringNotContainsString('@foreach', $html);
    }

    public function test_the_order_emails_say_what_was_ordered(): void
    {
        $html = $this->render(new OrderPlaced($this->order));

        $this->assertStringContainsString($this->order->number, $html);
        $this->assertStringContainsString($this->order->items->first()->name, $html);
    }

    private function render(Mailable $mailable): string
    {
        // Rendered the way the mailer renders it, so a view that will not
        // compile fails here instead of being logged and forgotten.
        return $mailable->render();
    }
}
