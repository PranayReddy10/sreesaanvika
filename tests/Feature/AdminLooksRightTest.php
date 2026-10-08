<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin's own stylesheet, which is served inline.
 *
 * It cannot be a published stylesheet: that needs a composer step, and this
 * shop deploys with git pull. So it rides in on a render hook, and the thing
 * that can go wrong is the hook — one missing registration and the admin
 * silently loses its paper, and with it the rules that keep the crop window
 * usable on a tablet.
 *
 * Whether the rules are *right* is a question for a browser, and they were
 * measured in one: on an iPad held upright, Cancel and Save sat below the
 * crop window's own edge and there was no way to finish a crop.
 */
class AdminLooksRightTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_carries_its_own_stylesheet(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        $page = $this->actingAs($admin)->get('/admin')->assertOk();

        // The paper.
        $page->assertSee('--ojasvi-paper', false);

        // And the crop window on a narrow screen: the control panel kept to a
        // size that leaves its buttons on the glass.
        $page->assertSee('fi-fo-file-upload-editor-control-panel', false);
        $page->assertSee('fi-fo-file-upload-editor-control-panel-footer', false);
    }
}
