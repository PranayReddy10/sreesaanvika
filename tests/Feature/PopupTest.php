<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The one message a shop may put over the front of itself.
 *
 * Three rules, all of them about not being the thing people close without
 * reading: it waits until she has seen the shop, it remembers being closed,
 * and it can be got out of three ways.
 */
class PopupTest extends TestCase
{
    use RefreshDatabase;

    private function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::put($key, (string) $value, is_bool($value) ? 'bool' : 'string', 'popup');
        }
    }

    public function test_there_is_no_popup_unless_the_shop_asks_for_one(): void
    {
        $this->assertFalse(Shop::popup()['on']);

        $this->get('/')->assertOk()->assertDontSee('aria-modal="true"', false);
    }

    /** A popup with nothing in it is an interruption showing a blank square. */
    public function test_switching_it_on_with_nothing_written_shows_nothing(): void
    {
        $this->set(['popup_on' => '1']);

        $this->assertFalse(Shop::popup()['on']);

        $this->get('/')->assertOk()->assertDontSee('aria-modal="true"', false);
    }

    public function test_it_appears_when_there_is_something_to_say(): void
    {
        $this->set([
            'popup_on'      => '1',
            'popup_heading' => 'Ten new weaves, this Friday',
            'popup_text'    => 'A few pieces at a time.',
            'popup_label'   => 'See them first',
            'popup_url'     => '/sarees?sort=new',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ten new weaves, this Friday')
            ->assertSee('See them first')
            ->assertSee('aria-modal="true"', false);
    }

    /** It is on every page, not only the front one. */
    public function test_it_is_wherever_she_arrives(): void
    {
        $this->set(['popup_on' => '1', 'popup_heading' => 'Ten new weaves']);

        foreach (['/', '/sarees', '/page/contact'] as $where) {
            $this->get($where)->assertOk()->assertSee('Ten new weaves');
        }
    }

    public function test_the_shop_chooses_the_wait_and_how_long_it_stays_shut(): void
    {
        $this->set([
            'popup_on'      => '1',
            'popup_heading' => 'Ten new weaves',
            'popup_after'   => '12',
            'popup_again'   => '7',
        ]);

        $popup = Shop::popup();

        $this->assertSame(12, $popup['after']);
        $this->assertSame(7, $popup['again']);

        $this->get('/')->assertOk()->assertSee('12 * 1000', false)->assertSee('7 * 86400000', false);
    }

    /** Nonsense in the boxes must not mean "appear instantly, for ever". */
    public function test_an_empty_or_silly_wait_falls_back_to_something_sensible(): void
    {
        $this->set(['popup_on' => '1', 'popup_heading' => 'Ten new weaves', 'popup_after' => '', 'popup_again' => '0']);

        $popup = Shop::popup();

        $this->assertSame(6, $popup['after']);
        $this->assertGreaterThanOrEqual(1, $popup['again']);
    }

    public function test_it_can_ask_for_an_email_and_that_goes_on_the_same_list(): void
    {
        $this->set(['popup_on' => '1', 'popup_heading' => 'First look', 'popup_ask' => '1']);

        $this->get('/')
            ->assertOk()
            ->assertSee(route('newsletter'), false)
            ->assertSee('popup-email', false);
    }
}
