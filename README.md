# sreesaanvika.in — WordPress theme

This repository holds **Sree Saanvika**, a dark-luxe WordPress + WooCommerce
theme for an Indian women's fashion store — handloom sarees, temple jewellery
and festive dresses.

## Getting the ZIP

A ready-to-import build lives at **[`sreesaanvika.zip`](sreesaanvika.zip)** in
the repository root. In WordPress:

**Appearance → Themes → Add New → Upload Theme →** pick `sreesaanvika.zip` →
**Install Now → Activate**, then follow the setup steps in
[`sreesaanvika/README.md`](sreesaanvika/README.md).

## Rebuilding after a change

```bash
./build.sh          # writes sreesaanvika.zip in the repo root
./build.sh dist/    # or into a directory of your choice
```

`build.sh` runs `php -l` over every PHP file first, so a syntax error stops
the build instead of shipping.

## Layout

| Path | What it is |
| --- | --- |
| `sreesaanvika/` | The theme source — this folder is what gets zipped |
| `sreesaanvika/README.md` | Install guide, feature list and Customizer reference |
| `build.sh` | Packages the theme into an importable ZIP |
| `sreesaanvika.zip` | The current build |

Requires WordPress 6.0+, PHP 7.4+ and WooCommerce 7.0+.
