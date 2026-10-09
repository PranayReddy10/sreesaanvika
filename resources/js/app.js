/**
 * The shop's own behaviour.
 *
 * Alpine comes with Livewire, which the bag page uses, so nothing is bundled
 * here that is already on the page. Everything below degrades: with JavaScript
 * off, the shade buttons are gone but the forms still post and the shop still
 * sells.
 */

document.addEventListener('alpine:init', () => {
    /** The saree page: shade buttons, the gallery, and adding to the bag. */
    Alpine.data('saree', ({ shades, fallback, start }) => ({
        shades,
        chosen: start || (shades[0] ? shades[0].id : null),
        active: 0,
        qty: 1,
        busy: false,

        get shade() {
            return this.shades.find((s) => s.id === this.chosen) || null;
        },

        /*
         * Whatever shade is being looked at, in that shade's own photographs,
         * with the saree worn at the end of them.
         *
         * The page opens on a shade whether or not anybody picked one — the
         * first — so this is the gallery from the first moment, and the
         * picture, the price and the stock on screen are all about the same
         * shade. The fallback is for a saree with no photographs of this
         * shade and no picture of it worn: its own, rather than nothing.
         */
        get gallery() {
            const own = this.shade && this.shade.images.length ? this.shade.images : null;

            return own || fallback;
        },

        pick(id) {
            this.chosen = id;
            this.active = 0;

            // The shade in the address bar, so a shopper can send someone the
            // indigo one rather than "the blue one, third row".
            const shade = this.shades.find((s) => s.id === id);

            if (shade && window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.set('shade', shade.name);
                window.history.replaceState({}, '', url);
            }
        },

        /*
         * `pressed` is the button the shopper actually pressed, which the form
         * hands over as $event.submitter.
         *
         * Taking it from document.activeElement instead was why Buy it now put
         * the saree in the bag and left her there: pressing a button does not
         * focus it on a touchscreen, so nothing was ever read as the checkout
         * button. It had a second half too — FormData(form) leaves out the
         * pressed button's own name and value, so the shop was never told
         * either, and the address it sent back was the bag's.
         */
        async addToBag(form, pressed) {
            if (this.busy) return;

            this.busy = true;

            try {
                // The second argument is what puts then=checkout in the post.
                const body = new FormData(form, pressed);
                const checkout = pressed?.value === 'checkout';

                // Older browsers ignore that argument. Said again by hand, so
                // the shop is told on those too.
                if (checkout && ! body.has('then')) body.append('then', 'checkout');

                const response = await fetch(form.action, {
                    method: 'POST',
                    body,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) throw new Error(await response.text());

                const data = await response.json();

                window.dispatchEvent(new CustomEvent('bag-changed', { detail: { count: data.count } }));

                // Reported from here rather than from the button, so a failed
                // add is never counted as one.
                if (window.odTrack && window.odItem) {
                    window.odTrack('add_to_cart', {
                        value: window.odItem.price * this.qty,
                        items: [Object.assign({}, window.odItem, { quantity: this.qty })],
                    });
                }

                if (checkout) {
                    window.location = data.checkout;
                    return;
                }

                toast(data.message || 'Added to your bag', data.bag);
            } catch (e) {
                // Whatever went wrong, the shopper must not be left wondering
                // whether it worked: send them to the bag, where the truth is.
                form.submit();
                return;
            } finally {
                this.busy = false;
            }
        },
    }));
});

/**
 * The films.
 *
 * Three rules, and they are all about the person watching rather than the
 * shop. Nothing loads until it is nearly on screen, because a saree shop's
 * customer is on a phone paying for her data. Nothing plays while it is off
 * screen, for the same reason. Nothing autoplays at all for somebody who has
 * asked their machine to stop moving things.
 *
 * Sound is hers to turn on. Browsers refuse to start a film with sound, so
 * these begin muted — but the moment she asks, every other film goes quiet,
 * because two sarees talking at once is nobody's idea of a shop.
 */
function watchVideos() {
    const figures = document.querySelectorAll('[data-od-video]');

    if (!figures.length) return;

    const stillness = window.matchMedia('(prefers-reduced-motion: reduce)');

    const hush = (except) => {
        document.querySelectorAll('[data-od-video] video').forEach((other) => {
            if (other === except) {
                return;
            }

            other.muted = true;
            const figure = other.closest('[data-od-video]');
            figure?.querySelector('.od-video-muted')?.classList.remove('hidden');
            figure?.querySelector('.od-video-loud')?.classList.add('hidden');
            figure?.querySelector('.od-video-sound')?.setAttribute('aria-pressed', 'false');
        });
    };

    figures.forEach((figure) => {
        /*
         * Once each. Livewire fires livewire:navigated on the first page load
         * as well as on later ones, so without this every film is wired twice
         * — and one press of the sound button then unmutes and re-mutes it,
         * which looks exactly like a button that does not work.
         */
        if (figure.dataset.odWired === '1') return;
        figure.dataset.odWired = '1';

        const video = figure.querySelector('video');
        const play = figure.querySelector('.od-video-play');
        const sound = figure.querySelector('.od-video-sound');

        if (!video) return;

        const start = () => {
            // Chrome rejects this promise rather than throwing, and an
            // unhandled rejection in the console is the shop looking broken.
            const started = video.play();

            if (started && typeof started.catch === 'function') {
                started.catch(() => figure.classList.remove('is-playing'));
            }
        };

        /*
         * Shown only once there is a frame to show. Until then the film is
         * transparent and the still behind it is what the shopper sees, which
         * is the whole reason a film arriving slowly is not a black box.
         */
        const ready = () => figure.classList.add('is-ready');

        if (video.readyState >= 2) ready();
        video.addEventListener('loadeddata', ready);

        /*
         * Not playable at all. The video element does not fire this itself
         * when its <source> is what failed — it quietly gives up instead, with
         * no error on the element — so the source is listened to directly, and
         * error does not bubble, so it has to be bound to the source itself.
         *
         * Reached by a file that is no longer where the shop thinks it is, and
         * by a film in a format this browser cannot decode: an iPhone .mov is
         * usually HEVC, which plays on the phone it was filmed on and nowhere
         * else. Either way the shopper gets the still and no dead button.
         */
        const broken = () => {
            figure.classList.add('is-broken');
            figure.classList.remove('is-playing');
        };

        video.addEventListener('error', broken);
        figure.querySelector('source')?.addEventListener('error', broken);

        video.addEventListener('playing', () => figure.classList.add('is-playing'));
        video.addEventListener('pause', () => figure.classList.remove('is-playing'));

        play?.addEventListener('click', () => {
            // Pressed by a person, so this one may have sound if she wants it.
            video.preload = 'auto';
            start();
        });

        sound?.addEventListener('click', () => {
            const quiet = video.muted;

            if (quiet) hush(video);

            video.muted = !quiet;
            sound.setAttribute('aria-pressed', quiet ? 'true' : 'false');
            sound.setAttribute('aria-label', quiet ? 'Turn the sound off' : 'Turn the sound on');
            figure.querySelector('.od-video-muted')?.classList.toggle('hidden', quiet);
            figure.querySelector('.od-video-loud')?.classList.toggle('hidden', !quiet);

            if (video.paused) start();
        });

        if (stillness.matches || !('IntersectionObserver' in window)) {
            return;
        }

        const watcher = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    if (video.preload !== 'auto') video.preload = 'auto';
                    start();
                } else if (!video.paused) {
                    video.pause();
                    // Muted again on the way out, so scrolling back does not
                    // start talking at her unannounced.
                    video.muted = true;
                    figure.querySelector('.od-video-muted')?.classList.remove('hidden');
                    figure.querySelector('.od-video-loud')?.classList.add('hidden');
                    sound?.setAttribute('aria-pressed', 'false');
                }
            });
        }, { threshold: 0.4 });

        watcher.observe(figure);
    });
}

/**
 * Instagram reels.
 *
 * A still until somebody taps it, then Instagram's own player takes over.
 * Nothing of theirs is fetched before that tap — their embed brings scripts
 * and cookies, and four of them loading as the front page opens would undo
 * what the shop promises about not calling on anybody else.
 *
 * Instagram will not let a website start one by itself, so the tap is real
 * and the page never pretends otherwise.
 */
function watchReels() {
    document.querySelectorAll('[data-od-reel]').forEach((figure) => {
        if (figure.dataset.odWired === '1') return;
        figure.dataset.odWired = '1';

        const play = figure.querySelector('.od-reel-play');

        play?.addEventListener('click', () => {
            const frame = document.createElement('iframe');

            frame.src = figure.dataset.embed;
            frame.title = figure.dataset.label || 'Instagram reel';
            frame.loading = 'lazy';
            frame.allow = 'autoplay; encrypted-media; picture-in-picture';
            frame.allowFullscreen = true;
            frame.scrolling = 'no';
            frame.className = 'absolute inset-0 w-full h-full border-0';

            figure.appendChild(frame);
            figure.classList.add('is-embedded');

            // The shop's own furniture goes once Instagram's is in: two play
            // badges and two captions on one film is nobody's idea of a shop.
            figure.querySelectorAll('.od-reel-poster, .od-reel-play, .od-reel-mark, .od-reel-caption')
                .forEach((part) => part.remove());
        });
    });
}

const wire = () => { watchVideos(); watchReels(); };

document.addEventListener('DOMContentLoaded', wire);
// Livewire swaps pieces of the page without reloading it.
document.addEventListener('livewire:navigated', wire);

/** A short message at the foot of the screen, with a way to act on it. */
function toast(message, href) {
    document.querySelector('.od-toast')?.remove();

    const el = document.createElement('div');
    el.className = 'od-toast';
    el.setAttribute('role', 'status');
    el.innerHTML = `<span></span>${href ? `<a href="${href}">View bag</a>` : ''}`;
    el.querySelector('span').textContent = message;

    document.body.appendChild(el);
    requestAnimationFrame(() => el.classList.add('is-in'));

    setTimeout(() => {
        el.classList.remove('is-in');
        setTimeout(() => el.remove(), 300);
    }, 4000);
}

window.odToast = toast;
