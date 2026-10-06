<?php

namespace Tests\Feature;

use App\Mail\EnquiryForShop;
use App\Models\Enquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Somebody writing in from the contact page.
 *
 * The page gave a telephone number and an address, which suits a shopper ready
 * to pick up the phone and nobody else.
 */
class EnquiryTest extends TestCase
{
    use RefreshDatabase;

    private function words(array $extra = []): array
    {
        return array_merge([
            'name'    => 'Sunita',
            'email'   => 'sunita@example.in',
            'phone'   => '+91 98765 43210',
            'message' => 'Is the blouse piece included with the indigo Kanjivaram?',
        ], $extra);
    }

    public function test_the_form_is_on_the_contact_page(): void
    {
        $this->get('/page/contact')
            ->assertOk()
            ->assertSee('Or write to us')
            ->assertSee(route('enquiry.store'), false);
    }

    public function test_a_question_is_kept_and_sent(): void
    {
        Mail::fake();

        User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        $this->post(route('enquiry.store'), $this->words())
            ->assertRedirect()
            ->assertSessionHas('enquiry');

        $this->assertDatabaseHas('enquiries', [
            'name'        => 'Sunita',
            'email'       => 'sunita@example.in',
            'answered_at' => null,
        ]);

        // Both: the email is what gets answered, the row is what stops it
        // being lost.
        Mail::assertQueued(EnquiryForShop::class, fn ($mail) => $mail->hasTo('owner@example.test'));
    }

    /** Answering is pressing Reply, so the email has to come from her. */
    public function test_the_shop_can_reply_to_the_email_it_receives(): void
    {
        Mail::fake();

        User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        $this->post(route('enquiry.store'), $this->words());

        Mail::assertQueued(EnquiryForShop::class, function (EnquiryForShop $mail) {
            return $mail->envelope()->replyTo[0]->address === 'sunita@example.in';
        });
    }

    public function test_an_order_number_is_carried_with_it(): void
    {
        Mail::fake();

        $this->post(route('enquiry.store'), $this->words(['order_number' => 'OJ-2026-00041']));

        $this->assertSame('OJ-2026-00041', Enquiry::firstOrFail()->order_number);
    }

    public function test_a_message_needs_an_address_and_something_to_answer(): void
    {
        Mail::fake();

        $this->post(route('enquiry.store'), $this->words(['email' => 'not-an-address', 'message' => 'Hi']))
            ->assertSessionHasErrors(['email', 'message']);

        $this->assertSame(0, Enquiry::count());
        Mail::assertNothingQueued();
    }

    public function test_something_filling_in_every_box_is_turned_away(): void
    {
        Mail::fake();

        $this->post(route('enquiry.store'), $this->words(['website' => 'http://buy-handbags.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, Enquiry::count());
    }

    public function test_the_same_address_cannot_send_a_hundred(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('enquiry.store'), $this->words(['message' => "A question, number {$i}, about a saree."]));
        }

        $this->post(route('enquiry.store'), $this->words())
            ->assertSessionHas('enquiry_error');

        $this->assertSame(5, Enquiry::count());
    }

    public function test_the_shop_can_mark_one_answered(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        $enquiry = Enquiry::create($this->words() + ['message' => 'A question about a saree.']);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Enquiries\Pages\ListEnquiries::class)
            ->callTableAction('answered', $enquiry);

        $this->assertNotNull($enquiry->fresh()->answered_at);
    }

    public function test_the_messages_screen_opens(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        Enquiry::create($this->words());

        $this->actingAs($admin)->get('/admin/enquiries')->assertOk();
    }
}
