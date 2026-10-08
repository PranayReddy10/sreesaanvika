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

    /*
        Cropping a photograph on a tablet held upright.

        Filament lays the crop window out side by side from 1024px and stacks
        it below that — image on top, controls underneath. Stacked, the image
        will not shrink (nothing lets it) and the controls are left with about
        sixty pixels, so the boxes are cut off and Cancel and Save sit below
        the window's own edge: on an iPad in portrait there is no way to
        finish a crop. Filament does exactly this for its crop-only editor;
        here it is for every narrow screen.
    */
    @media (max-width: 1023px) {
        .fi-fo-file-upload-editor-image-ctn {
            /* A flex item will not go below its content unless told. */
            min-height: 0;
        }

        .fi-fo-file-upload-editor-control-panel {
            height: auto;
            flex: none;
            /* Enough for the controls, never more than half the screen, so
               the photograph being cropped is still worth looking at. */
            max-height: 52dvh;
            overflow: hidden;
        }

        /* And whatever happens above it, the two buttons stay on screen. */
        .fi-fo-file-upload-editor-control-panel-footer {
            position: sticky;
            bottom: 0;
            z-index: 1;
            background-color: inherit;
            border-top: 1px solid var(--ojasvi-line);
        }
    }

    /*
        And on a screen that is wider than it is tall, put the controls beside
        the photograph rather than under it — Filament only does that from
        1024px, which leaves a tablet in landscape, or a half-width window,
        cropping through a letterbox a hundred pixels high.
    */
    @media (min-width: 700px) and (max-width: 1023px) and (orientation: landscape) {
        .fi-fo-file-upload-editor-window {
            flex-direction: row;
        }

        .fi-fo-file-upload-editor-control-panel {
            height: 100%;
            max-height: none;
            max-width: 20rem;
            overflow-y: auto;
        }
    }

    @media (prefers-color-scheme: dark) {
        /* The shop has one appearance and this is it. Filament's dark mode is
           not switched on for this panel, and if a browser asks for it anyway
           nothing here should follow. */
        :root { color-scheme: light; }
    }
</style>
