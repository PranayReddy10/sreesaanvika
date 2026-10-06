# ojasvidrapes.in — WordPress theme

This repository holds **OJASVI**, a dark-luxe WordPress + WooCommerce
theme for an Indian women's fashion store — handloom sarees, temple jewellery
and festive dresses.

## Getting the ZIP

A ready-to-import build lives at **[`ojasvidrapes.zip`](ojasvidrapes.zip)** in
the repository root. In WordPress:

**Appearance → Themes → Add New → Upload Theme →** pick `ojasvidrapes.zip` →
**Install Now → Activate**, then follow the setup steps in
[`ojasvidrapes/README.md`](ojasvidrapes/README.md).

## Rebuilding after a change

```bash
./build.sh          # writes ojasvidrapes.zip in the repo root
./build.sh dist/    # or into a directory of your choice
```

`build.sh` runs `php -l` over every PHP file first, so a syntax error stops
the build instead of shipping.

## Layout

| Path | What it is |
| --- | --- |
| `ojasvidrapes/` | The theme source — this folder is what gets zipped |
| `ojasvidrapes/README.md` | Install guide, feature list and Customizer reference |
| `build.sh` | Packages the theme into an importable ZIP |
| `ojasvidrapes.zip` | The current build |

Requires WordPress 6.0+, PHP 7.4+ and WooCommerce 7.0+.
