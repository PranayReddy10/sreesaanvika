# Feature guide — source

`../Sree-Saanvika-Feature-Guide.pdf` is rendered from `deck.html` in this
folder. `../Sree-Saanvika-Feature-Guide.md` is the same content as plain
Markdown, for anyone who would rather read or edit text.

## Rebuilding the PDF

Open `deck.html` in Chrome and print to PDF — A4, background graphics on,
margins none. Or from the command line, with Playwright installed:

```js
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage();

    await page.goto('file://' + process.cwd() + '/deck.html', { waitUntil: 'networkidle' });
    await page.pdf({
        path: 'Sree-Saanvika-Feature-Guide.pdf',
        format: 'A4',
        printBackground: true,
        margin: { top: '0', right: '0', bottom: '0', left: '0' },
    });

    await browser.close();
}());
```

Each `<section class="page">` is one A4 sheet. If you add content and a section
grows past 1123px tall it will spill onto a second sheet — the page heights are
worth checking after an edit.

## Fonts

The PDF is set in Gloock (display), Outfit (text) and Lora (italics), bundled
in `fonts/` under the SIL Open Font License; each licence is beside its font.
These stand in for the site's Playfair Display, Jost and Cormorant Garamond,
which are loaded from Google Fonts on the live site.
