# Themes

Each theme is a complete set of Blade views + assets for one industrial
product look-and-feel.

| Theme           | Status                | For module(s)             |
|-----------------|-----------------------|----------------------------|
| `nexus-bms/`    | Existing (to copy)    | BMS, Energy (default)      |
| `nexus-scada/`  | Stub                  | SCADA                      |
| `nexus-energy/` | Stub                  | Energy (overrides nexus-bms) |
| `nexus-wms/`    | Stub                  | WMS                        |
| `nexus-iiot/`   | Stub                  | IIoT                       |

## How themes resolve

`App\Core\Theme\ThemeManager` registers a custom view finder that prepends:

1. `resources/views/themes/{active}/...`
2. `resources/views/...` (legacy fallback — keeps Phase 2 backward-compat)

So `view('dashboard.index')` automatically picks the active theme's version
if present, otherwise falls back to the legacy path.

## Theme manifest

Each theme has a `theme.json` at its root:

```json
{
    "name": "Nexus BMS",
    "slug": "nexus-bms",
    "version": "1.0.0",
    "default_for_modules": ["bms"],
    "colors": {
        "primary": "#1d4ed8",
        "navy":    "#0d1b34",
        "cyan":    "#06b6d4"
    },
    "fonts": {
        "sans": "Inter",
        "thai": "Prompt"
    },
    "assets": {
        "css": "public/themes/nexus-bms/css/nexus.css",
        "js":  "public/themes/nexus-bms/js/nexus.js"
    }
}
```

**Currently empty** — `nexus-bms/` populated in Phase 2 (copy of current
views), others populated as their modules come online.

See `docs/SYSTEM_ARCHITECTURE.md` → "Theme system".
