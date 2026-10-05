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
