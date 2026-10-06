{{--
    The admin, on the same paper as the shop.

    Only the surfaces: Filament's own palette does the rest, and every colour
    here is one of the storefront's own tokens, so the two cannot drift apart
    without somebody noticing.
--}}
<style>
    :root {
        --ojasvi-paper: #f1e9dc;
        --ojasvi-paper-2: #faf6ef;
        --ojasvi-line: rgba(138, 108, 80, 0.22);
    }

    /* The page behind everything. */
    .fi-body,
    .fi-layout {
        background-color: var(--ojasvi-paper);
    }

    /* The sidebar and the top bar: a shade paler, so the shape of the screen
       still reads at a glance. */
    .fi-sidebar,
    .fi-topbar > nav,
    .fi-topbar {
        background-color: var(--ojasvi-paper-2);
        border-color: var(--ojasvi-line);
    }

    /* Anything holding content stays white, which is what makes a table of
       sarees legible on cream rather than muddy. */
    .fi-section-content-ctn,
    .fi-ta-ctn,
    .fi-wi-stats-overview-stat,
    .fi-modal-window,
    .fi-dropdown-panel {
        background-color: #ffffff;
    }

    .fi-sidebar-nav-item-active-label,
    .fi-sidebar-item-active .fi-sidebar-item-label {
        font-weight: 600;
    }

    /* Filament draws its own hairlines from the grey ramp; these are the
       shop's, so a card in the admin is edged like a card on the site. */
    .fi-section,
    .fi-ta-ctn,
    .fi-wi-stats-overview-stat {
        border-color: var(--ojasvi-line);
    }

    /* The login page, which is a page of its own and otherwise stays white. */
    .fi-simple-layout {
        background-color: var(--ojasvi-paper);
    }

    @media (prefers-color-scheme: dark) {
        /* The shop has one appearance and this is it. Filament's dark mode is
           not switched on for this panel, and if a browser asks for it anyway
           nothing here should follow. */
        :root { color-scheme: light; }
    }
</style>
