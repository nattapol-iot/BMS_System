# app/Widgets

Reusable UI components, each implemented once and consumed by all modules.

Each widget:

- Extends `App\Widgets\BaseWidget`
- Registers itself with `WidgetRegistry` via a Service Provider
- Is rendered in Blade via `<x-widget type="kpi-card" :title="..." />`

## Planned widgets (Phase 6)

| Widget                   | Use                                                     |
|--------------------------|---------------------------------------------------------|
| `KpiCardWidget`          | Number + label + trend % stat card                      |
| `TrendChartWidget`       | ApexCharts area/line wrapper                            |
| `GaugeWidget`            | Circular gauge for any 0–100 value                      |
| `AlarmTableWidget`       | Scrollable alarm list with severity badges              |
| `DeviceStatusWidget`     | Grid of equipment with status dots                      |
| `ProcessMimicWidget`     | SVG mimic (floor plan, P&ID, conveyor) with hot-spots   |

## Module-specific widgets

Modules can register their own widgets that extend `BaseWidget`. Examples:

- `App\Modules\BMS\Widgets\FloorPlanMimicWidget` — extends `ProcessMimicWidget`
- `App\Modules\Energy\Widgets\SolarVsLoadWidget` — specialized chart
- `App\Modules\WMS\Widgets\PickingHeatmapWidget` — warehouse-specific

These are registered in the module's ServiceProvider, not here.

**Currently empty** — content arrives during Phase 6 of the refactor.

See `docs/MODULE_STRUCTURE.md` → "Widget engine" for the contract.
