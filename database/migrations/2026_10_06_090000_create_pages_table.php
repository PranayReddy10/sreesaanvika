<?php

use App\Support\Policies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The pages that are words rather than sarees.
 *
 * Their text used to live in Settings, under five boxes at the bottom of a
 * long form, and there was no way to write a seventh page at all — a shop
 * wanting a size guide, or a page about the weavers, had nowhere to put it.
 *
 * Six of these rows are made here rather than written by the shop, because
 * they already existed as pages and the shop's own wording must survive the
 * move. Where nothing was written, the standing wording is put in — an empty
 * returns page is not a neutral default, it is a payment gateway refusing the
 * merchant account.
 */
return new class extends Migration {
    /** slug => [title, the old settings key, the standing wording] */
    private const EXISTING = [
        'story'    => ['Our story', 'story', 'story', 'Why there are a dozen sarees here and not a thousand.'],
        'contact'  => ['Contact', null, null, 'Telephone, email and WhatsApp — a person answers.'],
        'shipping' => ['Delivery', 'shipping_policy', 'shipping', 'What delivery costs, how long it takes and how to follow it.'],
        'returns'  => ['Returns & refunds', 'returns', 'returns', 'Seven days to change your mind, and how to go about it.'],
        'terms'    => ['Terms', 'terms', 'terms', 'The terms you agree to when you buy from us.'],
        'privacy'  => ['Privacy', 'privacy', 'privacy', 'What we collect, why, and how to have it deleted.'],
    ];

    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            // The line under the heading, and what a search result shows.
            $table->string('blurb')->nullable();
            $table->text('body')->nullable();

            // A page the shop added can be put away while it is being written.
            // The six below cannot: the footer and the checkout link to them,
            // and Razorpay will not approve a shop without the legal four.
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_fixed')->default(false);
            $table->boolean('in_footer')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $written = Schema::hasTable('settings')
            ? DB::table('settings')->pluck('value', 'key')
            : collect();

        $now = now();
        $position = 0;

        foreach (self::EXISTING as $slug => [$title, $key, $standard, $blurb]) {
            $body = $key ? trim((string) ($written[$key] ?? '')) : '';

            if ($body === '' && $standard) {
                $body = Policies::$standard();
            }

            DB::table('pages')->insert([
                'slug'       => $slug,
                'title'      => $title,
                'blurb'      => $blurb,
                'body'       => $body ?: null,
                'is_visible' => true,
                'is_fixed'   => true,
                // Delivery, Returns and Contact are the three the Help column
                // has always shown; the rest are linked from elsewhere.
                'in_footer'  => in_array($slug, ['shipping', 'returns', 'contact'], true),
                'position'   => $position += 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // The text now lives in the page, and two copies of a returns policy
        // is one too many.
        if (Schema::hasTable('settings')) {
            DB::table('settings')
                ->whereIn('key', ['story', 'returns', 'shipping_policy', 'terms', 'privacy'])
                ->delete();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
