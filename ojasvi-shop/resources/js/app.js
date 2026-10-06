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

        async addToBag(form) {
            if (this.busy) return;

            this.busy = true;

            try {
                const body = new FormData(form);
                const checkout = document.activeElement?.value === 'checkout';

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

document.addEventListener('DOMContentLoaded', watchVideos);
// Livewire swaps pieces of the page without reloading it.
document.addEventListener('livewire:navigated', watchVideos);

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
