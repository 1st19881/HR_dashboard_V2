# HR Dashboard — DESIGN THEME

เอกสารระบบออกแบบ (Design System) สำหรับ HR Dashboard — **Premium Light Theme**

---

## 🎨 Color Palette

### CSS Custom Properties (`:root`)

| Variable | Hex / Value | ใช้ที่ไหน |
|---|---|---|
| `--bg-body` | `#f4f7fa` | พื้นหลังทั้งหน้า |
| `--bg-sidebar` | `#ffffff` | พื้นหลัง Sidebar |
| `--bg-card` | `#ffffff` | พื้นหลัง KPI / Chart / Table card |
| `--bg-card-hover` | `#f8fafc` | Hover state ของ Card |
| `--bg-glass` | `rgba(255,255,255,0.8)` | Glassmorphism elements |
| `--border-color` | `#e6edf5` | ขอบ Card, Sidebar, Topbar |
| `--border-glow` | `rgba(37,99,235,0.15)` | Focus glow border |
| `--text-primary` | `#1e293b` | ข้อความหลัก, หัวข้อ |
| `--text-secondary` | `#475569` | ข้อความรอง, ตาราง |
| `--text-muted` | `#94a3b8` | Label, คำอธิบาย |
| `--accent` | `#2563eb` | สีหลัก (Primary blue) |
| `--accent-light` | `#3b82f6` | สีหลัก (Light blue) |
| `--accent-glow` | `rgba(37,99,235,0.08)` | Active menu, Focus glow |
| `--green` | `#10b981` | สำเร็จ, Age chart |
| `--red` | `#ef4444` | Function chart, ลบ |
| `--orange` | `#f59e0b` | เตือน, SUB Myanmar |
| `--teal` | `#0d9488` | SUB Cambodia |
| `--purple` | `#8b5cf6` | Female icon, PWC |
| `--blue` | `#2563eb` | Trend, Turnover |
| `--radius` | `14px` | Card border-radius |
| `--radius-sm` | `10px` | Button, input border-radius |
| `--shadow-card` | `0 10px 15px -3px rgba(0,0,0,0.04)...` | Card shadow |
| `--shadow-premium` | `0 20px 25px -5px rgba(0,0,0,0.05)...` | Hover shadow |
| `--transition` | `all 0.3s cubic-bezier(.4,0,.2,1)` | Transition ทุก Component |

### Chart Color Palette (JavaScript PALETTE)

```js
const PALETTE = {
    blue:   { stop1: '#2563eb', stop2: '#60a5fa', solid: '#2563eb' },
    orange: { stop1: '#f59e0b', stop2: '#fbbf24', solid: '#f59e0b' },
    purple: { stop1: '#8b5cf6', stop2: '#a78bfa', solid: '#8b5cf6' },
    red:    { stop1: '#ef4444', stop2: '#fca5a5', solid: '#ef4444' },
    teal:   { stop1: '#0d9488', stop2: '#5eead4', solid: '#0d9488' },
    green:  { stop1: '#10b981', stop2: '#34d399', solid: '#10b981' },
    gray:   '#94a3b8'
};
```

### Chart Color Map (Employee Type — Donut)

| Category | Color | Hex |
|---|---|---|
| PERM | Blue | `#2563eb` |
| PWC | Light Blue | `#60a5fa` |
| SUB Thai | Red | `#ef4444` |
| SUB Myanmar | Orange | `#f59e0b` |
| SUB Cambodia | Green | `#10b981` |
| OTHER | Gray | `#94a3b8` |

### Chart Color Map (Plant — Pie)

| Plant | Color | Hex |
|---|---|---|
| SLAB | Blue | `#3b82f6` |
| SAB | Purple | `#8b5cf6` |
| SAAB | Orange | `#f59e0b` |
| SRAB | Red | `#ef4444` |
| SRDC | Green | `#10b981` |
| SATC | Cyan | `#06b6d4` |
| SDC | Yellow | `#eab308` |

---

## 🔤 Typography

| Element | Font | Size | Weight |
|---|---|---|---|
| **Base** | Inter (Google Fonts) | 14px | 400 |
| **KPI Value** | Inter | 1.6rem (Mobile: 1.1rem) | 800 |
| **KPI Title** | Inter | 0.68rem | 600 |
| **Table Header** | Inter | 0.74rem | 700 |
| **Table Cell** | Inter | 0.8rem | 400 |
| **Chart Title** | Inter | 0.88rem | 600 |
| **Month Button** | Inter | 0.8rem (Mobile: 0.73rem) | 600 |
| **Page Title** | Inter | 1.15rem | 700 |
| **Sidebar Link** | Inter | 0.85rem | 500 (Active: 700) |
| **Form Label** | Inter | 0.72rem | 600 |

---

## 📐 Layout Architecture

### Sidebar + Main Content Pattern

```
┌──────────┬──────────────────────────────────┐
│          │  Top Bar (sticky)                 │
│ Sidebar  ├──────────────────────────────────┤
│  260px   │                                   │
│          │  Dashboard Container              │
│ (collaps │    ┌─────────┬─────────────────┐  │
│  → 80px) │    │ Filters │ Dropdown Filters│  │
│          │    ├─────────┴─────────────────┤  │
│          │    │ KPI Cards (8 cards)       │  │
│          │    ├───────────────────────────┤  │
│          │    │ Charts / DataTable        │  │
│          │    └───────────────────────────┘  │
└──────────┴──────────────────────────────────┘
```

### Sidebar States
- **Desktop (≥1200px)**: Fixed sidebar 260px, collapsible → 80px
- **Notebook (≤1440px)**: Sidebar 240px
- **Tablet/Mobile (≤1199px)**: Sidebar overlay slide-in + Backdrop blur

---

## 📐 Spacing & Border Radius

| Component | Padding | Border Radius |
|---|---|---|
| `.dashboard-container` | 20px 24px (XL: 24px 28px, Mobile: 10px) | — |
| `.sidebar` | — | — |
| `.topbar` | 14px 24px | — |
| `.filter-card` | 14px 18px (Mobile: 12px) | 14px |
| `.kpi-card` | 18px 20px (Mobile: 14px) | 14px |
| `.chart-card` | 20px (Mobile: 14px) | 14px |
| `.data-table-container` | 0 | 14px |
| `.month-btn` | 8px 16px (Mobile: 5px 10px) | 10px |
| `.form-select` | Bootstrap default | 10px |

---

## 📱 Responsive Breakpoints

| Breakpoint | Screen Width | Sidebar | KPI Layout | Chart Adjustments |
|---|---|---|---|---|
| **XL Desktop** | ≥ 1400px | 260px fixed | Auto `col-lg` | Padding ใหญ่ขึ้น |
| **Notebook** | ≤ 1440px | 240px fixed | Auto | KPI 1.4rem |
| **Tablet** | ≤ 1199px | Overlay slide-in | Auto | margin-left: 0 |
| **Mobile** | ≤ 767px | Overlay | 2 col `col-6` | Chart padding ลด |
| **Small Mobile** | ≤ 575px | Overlay | 2 col `col-6` | Chart height ลด |

### Bootstrap Grid Classes ที่ใช้

```html
<!-- KPI Cards -->
<div class="col-6 col-sm-4 col-md-2 col-lg">

<!-- Filter Dropdowns -->
<div class="col-6 col-sm-4 col-xl-4 col-xxl">

<!-- Charts (Overview) -->
<div class="col-12 col-xl-5">   <!-- Donut -->
<div class="col-12 col-xl-7">   <!-- Bar -->

<!-- Month + Dropdown Filter Split -->
<div class="col-xxl-4 col-xl-5 col-lg-12">  <!-- Month -->
<div class="col-xxl-8 col-xl-7 col-lg-12">  <!-- Dropdowns -->
```

---

## 🃏 Component Specs

### Sidebar

```css
.sidebar {
    position: fixed; width: 260px;
    background: var(--bg-sidebar);
    border-right: 1px solid var(--border-color);
    z-index: 1040;
    transition: transform 0.3s ease, width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.sidebar-link.active {
    background: var(--accent-glow);
    color: var(--accent);
    border-right: 3px solid var(--accent);
}
```

### Top Bar (Glassmorphism)

```css
.topbar {
    position: sticky; top: 0; z-index: 1030;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border-color);
}
```

### KPI Card (Hover Animation)

```css
.kpi-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 18px 20px;
    box-shadow: var(--shadow-card);
    backdrop-filter: blur(8px);
    transition: var(--transition);
}
.kpi-card::before {   /* Gradient top bar on hover */
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--accent), #3b82f6);
    opacity: 0;
}
.kpi-card:hover {
    transform: translateY(-4px);
    border-color: var(--accent);
    box-shadow: var(--shadow-premium);
}
.kpi-card:hover::before { opacity: 1; }
```

### Chart Card

```css
.chart-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 20px;
    box-shadow: var(--shadow-card);
    backdrop-filter: blur(8px);
}
```

### Month Button (Gradient Active)

```css
.month-btn {
    border: 1px solid var(--border-color);
    background: #fff;
    border-radius: 10px;
    font-weight: 600;
}
.month-btn.active {
    background: linear-gradient(135deg, var(--accent), #3b82f6);
    color: #fff;
    box-shadow: 0 8px 16px rgba(37,99,235,0.25);
}
```

### DataTable

```css
.table th {
    background: #f8fafc;
    border-bottom: 2px solid var(--border-color);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.table-hover>tbody>tr:hover>* {
    color: var(--accent);
    font-weight: 500;
}
```

---

## 📊 Chart Specifications

### 1. Headcount Trend — `trendChart`
- **Type**: Line (Area Gradient fill)
- **Height**: 300px (wrap: `#trendChart-wrap`)
- **Fill**: Blue gradient `rgba(37,99,235,0)` → `rgba(37,99,235,0.1)`
- **Line**: `#2563eb`, width 3, tension 0.4
- **Points**: White fill + Blue border, radius 4
- **Datalabels**: Blue `#2563eb`, bold, top aligned

### 2. Headcount By Employee Type — `typeChart`
- **Type**: Doughnut
- **Height**: 360px (wrap: `#typeChart-wrap`)
- **cutout**: 75%, borderRadius 8, spacing 5
- **Custom Plugins**: `calloutLines` (L-shape เส้นชี้), `centerText` (Total Headcount ตรงกลาง)
- **Legend**: Right (Desktop) / Bottom (≤1500px)

### 3. Headcount By Plant — `plantChart`
- **Type**: Pie
- **Height**: 280px (wrap: `#plantChart-wrap`)
- **Custom Plugin**: `pieCalloutLines` (L-shape เส้นชี้)
- **Datalabels**: แสดง % (ซ่อนถ้า < 3%), anchor end
- **Legend**: Right, usePointStyle

### 4. Headcount By Function — `functionChart`
- **Type**: Bar (Vertical)
- **Height**: 280px (wrap: `#functionChart-wrap`)
- **Gradient**: Red `#ef4444` → `#fca5a5`
- **borderRadius**: 5, maxBarThickness 35
- **Datalabels**: Red, top aligned, แสดง `%`

### 5. Headcount By Age — `ageChart`
- **Type**: Bar (Vertical)
- **Height**: 280px (wrap: `#ageChart-wrap`)
- **Gradient**: Green `#10b981` → `#34d399`
- **borderRadius**: 5, maxBarThickness 40
- **Datalabels**: Green `#4ade80`, top aligned, แสดง `%`

### 6. Turnover Rate — `turnoverChart`
- **Type**: Bar (Vertical)
- **Height**: 220px (wrap: `#turnoverChart-wrap`)
- **Gradient**: Blue `#2563eb` → `#60a5fa`
- **borderRadius**: 5, maxBarThickness 45
- **Datalabels**: Blue `#2563eb`, top aligned, แสดง `%`
- **Interactive**: คลิกได้ → เปิด Modal drill-down

### Chart.js Global Defaults

```js
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#64748b';
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(255,255,255,0.95)';
Chart.defaults.plugins.tooltip.borderColor = '#e2e8f0';
Chart.defaults.plugins.tooltip.borderWidth = 1;
Chart.defaults.plugins.tooltip.padding = 12;
Chart.defaults.plugins.tooltip.cornerRadius = 10;
Chart.defaults.plugins.tooltip.titleColor = '#1e293b';
Chart.defaults.plugins.tooltip.bodyColor = '#475569';
```

---

## ✨ Animations

### Fade-In Up (Entry Animation)

```css
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
.kpi-card, .chart-card, .filter-card, .data-table-container {
    animation: fadeInUp 0.5s ease both;
}
/* Staggered delay by column position */
.row > [class*="col-"]:nth-child(1) > * { animation-delay: 0.05s; }
.row > [class*="col-"]:nth-child(2) > * { animation-delay: 0.1s; }
/* ... up to 7th child at 0.35s */
```

### Micro-interactions
- **KPI Card hover**: `translateY(-4px)` + blue top gradient bar appears
- **Sidebar link hover**: Background `#f8fafc` + color accent
- **Month button hover**: Background `#f8fafc` + border accent
- **Table row hover**: Text color → accent blue
- **Button hover**: `translateY(-2px)` + enhanced shadow

---

## 🖨️ Print Media Query

```css
@media print {
    .sidebar, .topbar, .filter-card { display: none !important; }
    .main-content { margin-left: 0; }
    .dashboard-container { padding: 0; max-width: 100%; }
    body { background: #fff; color: #333; }
    .chart-card, .kpi-card { box-shadow: none; border: 1px solid #ccc; }
}
```

---

## 🌐 Custom Scrollbar

```css
::-webkit-scrollbar { width: 8px; height: 8px; }
::-webkit-scrollbar-track { background: #f1f5f9; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; border: 2px solid #f1f5f9; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
```

---

*บันทึกล่าสุด: 12 พฤษภาคม 2026*
