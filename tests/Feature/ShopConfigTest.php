<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Providers\ShopConfigProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * The settings a shopkeeper can change without opening a file.
 *
 * Where the photographs are kept and how the shop sends email lived only in
 * .env, which on shared hosting means a file manager, a text editor and a line
 * typed without a typo — and getting one wrong means a shop that silently
 * stops sending order confirmations.
 */
class ShopConfigTest extends TestCase
{
    use RefreshDatabase;

    private function apply(): void
    {
        (new ShopConfigProvider($this->app))->boot();
    }

    /* ----------------------------------------------------- the secrets */

    public function test_a_password_is_not_written_down_in_plain_text(): void
    {
        Setting::put('mail_password', 'hunter2', 'secret', 'email');

        $row = Setting::where('key', 'mail_password')->firstOrFail();

        $this->assertNotSame('hunter2', $row->value, 'stored as typed');
        $this->assertStringNotContainsString('hunter2', $row->value);
        $this->assertSame('hunter2', Crypt::decryptString($row->value));

        // And the shop reads it back without anybody thinking about it.
        $this->assertSame('hunter2', Setting::get('mail_password'));
    }

    /**
     * A key that has changed must not take the admin down with it.
     *
     * The one way a stored secret stops decrypting is APP_KEY being replaced,
     * and a shop in that state should find a blank box and mail that does not
     * send — not every page of the admin throwing.
     */
    public function test_a_secret_that_will_not_decrypt_reads_as_empty(): void
    {
        Setting::query()->create([
            'key' => 'mail_password', 'value' => 'not-encrypted-at-all', 'type' => 'secret', 'group' => 'email',
        ]);

        $this->assertSame('', Setting::get('mail_password'));
    }

    public function test_the_admin_never_sends_a_saved_password_back_to_the_browser(): void
    {
        Setting::put('mail_password', 'hunter2', 'secret', 'email');
        Setting::put('spaces_secret', 'a-spaces-secret', 'secret', 'storage');

        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        $page = $this->actingAs($admin)->get('/admin/shop-settings')->assertOk();

        $page->assertDontSee('hunter2')->assertDontSee('a-spaces-secret');
    }

    public function test_saving_with_the_password_box_empty_keeps_the_one_saved(): void
    {
        Setting::put('mail_password', 'hunter2', 'secret', 'email');

        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\ShopSettings::class)
            ->fillForm(['shop_name' => 'OJASVI', 'mail_host' => 'smtp.hostinger.com'])
            ->call('save');

        $this->assertSame('hunter2', Setting::get('mail_password'), 'an empty box must not mean forget it');
    }

    /* ------------------------------------------------------- the email */

    public function test_the_shop_can_be_told_how_to_send_email(): void
    {
        Setting::put('mail_host', 'smtp.hostinger.com', 'string', 'email');
        Setting::put('mail_username', 'care@ojasvidrapes.in', 'string', 'email');
        Setting::put('mail_password', 'hunter2', 'secret', 'email');
        Setting::put('mail_from', 'care@ojasvidrapes.in', 'string', 'email');
        Setting::put('mail_from_name', 'OJASVI', 'string', 'email');

        $this->apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.hostinger.com', config('mail.mailers.smtp.host'));
        $this->assertSame('hunter2', config('mail.mailers.smtp.password'));
        $this->assertSame('care@ojasvidrapes.in', config('mail.from.address'));
        $this->assertSame('OJASVI', config('mail.from.name'));

        // 465 unless told otherwise, which is the one that goes with SSL.
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
    }

    /**
     * TLS means 587, and a plain connection that is upgraded.
     *
     * The scheme matters as much as the port: `smtps` is encrypted from the
     * first byte, which is 465's business. A server listening on 587 expects
     * to be spoken to in the clear and then asked to upgrade, and hangs up on
     * a handshake instead — so this is the difference between a shop that
     * sends order confirmations and one that does not.
     */
    public function test_tls_brings_the_other_usual_port_with_it(): void
    {
        Setting::put('mail_host', 'smtp.example.in', 'string', 'email');
        Setting::put('mail_encryption', 'tls', 'string', 'email');

        $this->apply();

        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
    }

    /** No encryption at all, for a mail server on the same box. */
    public function test_a_plain_connection_asks_for_no_encryption(): void
    {
        Setting::put('mail_host', 'localhost', 'string', 'email');
        Setting::put('mail_encryption', 'none', 'string', 'email');
        Setting::put('mail_port', '25', 'int', 'email');

        $this->apply();

        $this->assertSame(25, config('mail.mailers.smtp.port'));
        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
        $this->assertNull(config('mail.mailers.smtp.encryption'));
    }

    public function test_an_empty_host_leaves_the_env_alone(): void
    {
        $before = config('mail.mailers.smtp.host');

        $this->apply();

        $this->assertSame($before, config('mail.mailers.smtp.host'));
    }

    /* ----------------------------------------------------- the storage */

    public function test_the_photographs_can_be_moved_to_spaces_from_the_admin(): void
    {
        Setting::put('storage_driver', 'spaces', 'string', 'storage');
        Setting::put('spaces_key', 'a-key', 'string', 'storage');
        Setting::put('spaces_secret', 'a-secret', 'secret', 'storage');
        Setting::put('spaces_bucket', 'ojasvi', 'string', 'storage');
        Setting::put('spaces_url', 'https://cdn.ojasvidrapes.in', 'string', 'storage');

        $this->apply();

        $this->assertSame('s3', config('filesystems.disks.public.driver'));
        $this->assertSame('ojasvi', config('filesystems.disks.public.bucket'));
        $this->assertSame('a-secret', config('filesystems.disks.public.secret'));
        $this->assertSame('https://blr1.digitaloceanspaces.com', config('filesystems.disks.public.endpoint'));
        $this->assertSame('https://cdn.ojasvidrapes.in', config('filesystems.disks.public.url'));
    }

    /**
     * Half-filled is not configured.
     *
     * Switching the disk over without credentials would make every photograph
     * on the shop disappear at once, which is a worse outcome than the switch
     * appearing not to work.
     */
    public function test_switching_to_spaces_without_the_keys_changes_nothing(): void
    {
        Setting::put('storage_driver', 'spaces', 'string', 'storage');
        Setting::put('spaces_bucket', 'ojasvi', 'string', 'storage');

        $before = config('filesystems.disks.public.driver');

        $this->apply();

        $this->assertSame($before, config('filesystems.disks.public.driver'));
    }

    public function test_leaving_it_on_this_server_changes_nothing(): void
    {
        $before = config('filesystems.disks.public');

        $this->apply();

        $this->assertSame($before, config('filesystems.disks.public'));
    }
}
