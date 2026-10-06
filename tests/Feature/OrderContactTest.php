<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Reaching the parcel and reaching the customer, from the order page.
 *
 * Both of these used to be text somebody read off the screen and typed
 * somewhere else: the tracking number into the courier's site, the phone
 * number into WhatsApp. The addresses are worked out from what the order
 * already holds, so these tests are about the two ways that goes wrong —
 * a link built for a courier nobody knows, and a phone number written the
 * way Indian customers actually write them.
 */
class OrderContactTest extends TestCase
{
    use RefreshDatabase;

    public static function couriers(): array
    {
        return [
            'Delhivery'  => ['delhivery', 'https://www.delhivery.com/track/package/18862578005'],
            'Blue Dart'  => ['Blue Dart', 'https://www.bluedart.com/web/guest/trackdartresult?trackFor=0&trackNo=18862578005'],
            'DTDC'       => ['DTDC', 'https://www.dtdc.in/tracking/shipment-tracking.asp?strCnno=18862578005'],
            'Xpressbees' => ['XpressBees', 'https://www.xpressbees.com/shipment/tracking?awbNo=18862578005'],
            'Ekart'      => ['Ekart Logistics', 'https://ekartlogistics.com/shipmenttrack/18862578005'],
            'Shadowfax'  => ['shadowfax', 'https://tracker.shadowfax.in/#/tracking/18862578005'],
        ];
    }

    /**
     * However the shop wrote the courier's name down.
     *
     * Couriers are typed in by hand, so "Blue Dart", "blue-dart" and "bluedart"
     * are all the same courier and all have to find the same page.
     */
    #[DataProvider('couriers')]
    public function test_a_tracking_number_becomes_a_link_to_the_courier(string $courier, string $expected): void
    {
        $shipment = new Shipment(['courier' => $courier, 'awb' => '18862578005']);

        $this->assertSame($expected, $shipment->trackingUrl());
    }

    /**
     * India Post has no per-number address, so the number is not in the link.
     */
    public function test_india_post_goes_to_its_tracking_page(): void
    {
        $url = (new Shipment(['courier' => 'India Post', 'awb' => 'EK123456789IN']))->trackingUrl();

        $this->assertStringContainsString('indiapost.gov.in', (string) $url);
    }

    /**
     * No link rather than a wrong one.
     *
     * The number stays copyable on the page either way, which is the point: a
     * link to a courier's home page with the number lost is worse than none.
     */
    public function test_an_unknown_courier_and_a_missing_number_produce_no_link(): void
    {
        $this->assertNull((new Shipment(['courier' => 'Ravi Travels', 'awb' => '18862578005']))->trackingUrl());
        $this->assertNull((new Shipment(['courier' => 'delhivery', 'awb' => '']))->trackingUrl());
        $this->assertNull((new Shipment(['courier' => 'delhivery', 'awb' => null]))->trackingUrl());
    }

    public static function phoneNumbers(): array
    {
        return [
            'ten digits'           => ['9000000004', 'https://wa.me/919000000004'],
            'spaces and dashes'    => ['90000 000-04', 'https://wa.me/919000000004'],
            'leading zero'         => ['09000000004', 'https://wa.me/919000000004'],
            'country code already' => ['919000000004', 'https://wa.me/919000000004'],
            'written with +91'     => ['+91 90000 00004', 'https://wa.me/919000000004'],
            'too short'            => ['90000', null],
            'nothing at all'       => ['', null],
            'not a number'         => ['call the shop', null],
        ];
    }

    #[DataProvider('phoneNumbers')]
    public function test_a_phone_number_becomes_a_whatsapp_address(string $phone, ?string $expected): void
    {
        $url = (new Order(['phone' => $phone, 'number' => 'OJ-2026-0001']))->whatsappUrl();

        if ($expected === null) {
            $this->assertNull($url);

            return;
        }

        $this->assertStringStartsWith($expected.'?text=', (string) $url);
    }

    /**
     * The message is written for the shop, not left blank.
     */
    public function test_the_message_names_the_shop_and_the_order(): void
    {
        $url = (new Order(['phone' => '9000000004', 'number' => 'OJ-2026-0042']))->whatsappUrl();

        $text = rawurldecode(parse_url((string) $url, PHP_URL_QUERY) ?? '');

        $this->assertStringContainsString('OJ-2026-0042', $text);
        $this->assertStringContainsString(\App\Support\Shop::name(), $text);
    }

    /**
     * And both of them are on the page, as links somebody can click.
     *
     * Everything above is the model on its own; this is the only test that says
     * the order page actually carries them.
     */
    public function test_the_order_page_links_to_the_courier_and_to_whatsapp(): void
    {
        $this->seed(DemoSeeder::class);

        $this->actingAs(User::create([
            'name' => 'OJASVI',
            'email' => 'admin@example.test',
            'password' => 'secret-for-tests',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]));

        $order = Order::whereHas('shipment')->first();
        $this->assertNotNull($order, 'needs a posted order to follow');

        $order->update(['phone' => '9000000004']);
        $order->shipment->update(['courier' => 'delhivery', 'awb' => '18862578005']);

        $this->get(\App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $order]))
            ->assertOk()
            ->assertSee('https://www.delhivery.com/track/package/18862578005', false)
            ->assertSee('https://wa.me/919000000004', false);
    }
}
