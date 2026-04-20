# 🎨 AUDITORÍA DE DISEÑO UI/UX + RESPONSIVE
**Mapita v1.2.0** - Plan de mejoras ordenadas y ejecutables

---

## 📊 ESTADO ACTUAL RESUMIDO

| Aspecto | Estado | Severidad |
|---------|--------|-----------|
| Paleta de colores | Inconsistente (4+ azules diferentes) | 🔴 Alta |
| Sistema de espaciado | Sin grid (8px, 10px, 12px, 15px, 18px) | 🔴 Alta |
| Responsive Mobile | Sidebar fijo, no adaptable | 🔴 Alta |
| Border radius | Inconsistente (5px, 6px, 8px, 12px, 16px) | 🟡 Media |
| Sombras | Sin jerarquía (2px a 60px) | 🟡 Media |
| Tipografía | Pesos inconsistentes, sin jerarquía clara | 🟡 Media |
| Contraste WCAG | Algunas combinaciones bajo estándar | 🟠 Baja |

---

## 🎯 FASE 1: SISTEMA DE VARIABLES CSS (PRIORIDAD MÁXIMA)

### 1.1 Crear archivo: `/css/variables-luxury.css`

**Objetivo:** Centralizar todos los colores, espaciados, y medidas.

```
CONTENIDO PARA COPIAR-PEGAR EN /css/variables-luxury.css
═══════════════════════════════════════════════════════════════

:root {
  /* ─── PALETA LUJO (40% Oro, 20% Plata, 15% Rojo, 15% Azul, 10% Negro) ─── */
  
  /* PRIMARY: ORO (Prestigio, exclusividad) */
  --color-gold-primary: #D4AF37;      /* Dorado puro */
  --color-gold-light: #E8C547;        /* Dorado claro */
  --color-gold-dark: #B8860B;         /* Dorado oscuro */
  
  /* SECONDARY: PLATA (Tecnología, balance) */
  --color-silver-primary: #C0C0C0;    /* Plata */
  --color-silver-light: #E8E8E8;      /* Plata clara */
  --color-silver-dark: #A8A8A8;       /* Plata oscura */
  
  /* ACCENT: ROJO (Acción, urgencia) */
  --color-red-primary: #E63946;       /* Rojo acción */
  --color-red-light: #F07080;         /* Rojo claro */
  --color-red-dark: #C0202D;          /* Rojo oscuro */
  
  /* SUPPORT: AZUL PROFUNDO (Confianza, profesionalismo) */
  --color-blue-primary: #1B3B6F;      /* Azul profundo */
  --color-blue-light: #2E5FA3;        /* Azul claro */
  --color-blue-dark: #0F1F3D;         /* Azul oscuro */
  
  /* BASE: NEGRO (Elegancia, sobriedad) */
  --color-black-primary: #0F0F0F;     /* Negro base */
  --color-black-light: #1A1A1A;       /* Negro claro */
  --color-white: #FFFFFF;             /* Blanco */
  
  /* NEUTRALES PREMIUM */
  --color-gray-50: #FAFAFA;           /* Muy claro */
  --color-gray-100: #F5F5F5;
  --color-gray-200: #EEEEEE;
  --color-gray-300: #E0E0E0;
  --color-gray-400: #BDBDBD;
  --color-gray-500: #9E9E9E;
  --color-gray-600: #757575;
  --color-gray-700: #616161;
  --color-gray-800: #424242;
  --color-gray-900: #212121;
  
  /* STATUS COLORS */
  --color-success: #2ECC71;           /* Verde éxito */
  --color-warning: #F39C12;           /* Naranja aviso */
  --color-danger: #E63946;            /* Rojo peligro */
  --color-info: #3498DB;              /* Azul información */
  
  /* ─── ROLES DE COLOR ─── */
  --primary: var(--color-blue-primary);
  --primary-light: var(--color-blue-light);
  --primary-dark: var(--color-blue-dark);
  
  --secondary: var(--color-gold-primary);
  --secondary-light: var(--color-gold-light);
  --secondary-dark: var(--color-gold-dark);
  
  --accent: var(--color-red-primary);
  --accent-light: var(--color-red-light);
  --accent-dark: var(--color-red-dark);
  
  --text-primary: var(--color-gray-900);
  --text-secondary: var(--color-gray-600);
  --text-tertiary: var(--color-gray-500);
  --text-inverse: var(--color-white);
  
  --bg-primary: var(--color-black-primary);
  --bg-secondary: var(--color-black-light);
  --bg-tertiary: var(--color-gray-100);
  --bg-overlay: rgba(15, 15, 15, 0.85);
  
  /* ─── ESPACIADO (Sistema 8px) ─── */
  --space-xs: 4px;
  --space-sm: 8px;
  --space-md: 16px;
  --space-lg: 24px;
  --space-xl: 32px;
  --space-2xl: 48px;
  --space-3xl: 64px;
  
  /* ─── TIPOGRAFÍA ─── */
  --font-family-base: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
  --font-family-mono: 'Courier New', monospace;
  
  --font-size-xs: 12px;
  --font-size-sm: 14px;
  --font-size-md: 16px;
  --font-size-lg: 18px;
  --font-size-xl: 20px;
  --font-size-2xl: 24px;
  --font-size-3xl: 32px;
  --font-size-4xl: 40px;
  
  --font-weight-light: 300;
  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600;
  --font-weight-bold: 700;
  --font-weight-black: 900;
  
  --line-height-tight: 1.2;
  --line-height-normal: 1.5;
  --line-height-relaxed: 1.75;
  
  /* ─── BORDERS & RADIUS ─── */
  --border-radius-xs: 2px;
  --border-radius-sm: 4px;
  --border-radius-md: 8px;
  --border-radius-lg: 12px;
  --border-radius-xl: 16px;
  --border-radius-full: 9999px;
  
  --border-width-thin: 1px;
  --border-width-normal: 2px;
  --border-width-thick: 3px;
  
  /* ─── SOMBRAS ─── */
  --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
  --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.15);
  --shadow-lg: 0 8px 16px rgba(0, 0, 0, 0.2);
  --shadow-xl: 0 12px 24px rgba(0, 0, 0, 0.25);
  --shadow-2xl: 0 16px 32px rgba(0, 0, 0, 0.3);
  --shadow-gold: 0 4px 16px rgba(212, 175, 55, 0.15);
  --shadow-gold-lg: 0 8px 32px rgba(212, 175, 55, 0.2);
  
  /* ─── TRANSICIONES ─── */
  --transition-fast: 0.1s ease-in-out;
  --transition-base: 0.2s ease-in-out;
  --transition-slow: 0.3s ease-in-out;
  --transition-slower: 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  
  /* ─── BREAKPOINTS ─── */
  --breakpoint-xs: 320px;
  --breakpoint-sm: 640px;
  --breakpoint-md: 768px;
  --breakpoint-lg: 1024px;
  --breakpoint-xl: 1280px;
  --breakpoint-2xl: 1536px;
  
  /* ─── OTRAS MEDIDAS ─── */
  --max-content-width: 1200px;
  --navbar-height: 60px;
  --sidebar-width: 300px;
  --sidebar-width-mobile: 260px;
  
  /* ─── Z-INDEX SYSTEM ─── */
  --z-dropdown: 100;
  --z-sticky: 200;
  --z-fixed: 300;
  --z-modal-backdrop: 900;
  --z-modal: 1000;
  --z-tooltip: 1100;
  --z-notification: 1200;
}

/* Media Query para oscuridad (opcional) */
@media (prefers-color-scheme: dark) {
  :root {
    --text-primary: var(--color-gray-100);
    --text-secondary: var(--color-gray-400);
    --bg-tertiary: var(--color-gray-900);
  }
}
```

**✅ Acción:** Crea este archivo y enlázalo en todos los `<head>` ANTES que otros CSS

---

## 📱 FASE 2: MEJORAS RESPONSIVE (PRIORIDAD MÁXIMA)

### 2.1 En `views/business/map.php` - FIX SIDEBAR MOBILE

**LÍNEAS A CAMBIAR: 39-52**

```
BÚSQUEDA ACTUAL (LÍNEAS 39-52):
═══════════════════════════════════════════════════════════════
        /* ── Sidebar ────────────────────────────────── */
        #sidebar {
            width: 300px; padding: 15px; background: var(--light-gray); overflow-y: auto;
            border-right: 1px solid var(--medium-gray); transition: left 0.3s ease; z-index: 10;
        }
        #togglePanel {
            display: none; position: absolute; top: 10px; left: 10px;
            padding: 10px 14px; z-index: 1000; background: var(--primary); color: white;
            border: none; border-radius: 6px; font-size: 1em; cursor: pointer; font-weight: 600;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            transition: all 0.2s ease;
        }
        #togglePanel:hover { background: var(--primary-dark); transform: translateY(-1px); }
        #map { flex: 1; height: 100%; }

REEMPLAZAR POR:
═══════════════════════════════════════════════════════════════
        /* ── Sidebar (RESPONSIVE) ────────────────────────────── */
        #sidebar {
            width: var(--sidebar-width);
            padding: var(--space-md);
            background: var(--bg-tertiary);
            overflow-y: auto;
            border-right: var(--border-width-thin) solid var(--color-gray-300);
            transition: transform var(--transition-base), left var(--transition-base);
            z-index: var(--z-fixed);
        }
        
        #togglePanel {
            display: none;
            position: fixed;
            top: var(--space-md);
            left: var(--space-md);
            padding: var(--space-sm) var(--space-md);
            z-index: var(--z-sticky);
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius-md);
            font-size: var(--font-size-md);
            cursor: pointer;
            font-weight: var(--font-weight-semibold);
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-base);
        }
        
        #togglePanel:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
        
        #map {
            flex: 1;
            height: 100%;
        }
        
        /* ── RESPONSIVE: Tablet (768px) ──────────────────────────── */
        @media (max-width: 768px) {
            #sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: var(--sidebar-width-mobile);
                height: 100vh;
                transform: translateX(-100%);
                border-right: none;
                box-shadow: var(--shadow-xl);
            }
            
            #sidebar.open {
                transform: translateX(0);
            }
            
            #togglePanel {
                display: block;
            }
        }
        
        /* ── RESPONSIVE: Mobile (<640px) ──────────────────────────── */
        @media (max-width: 640px) {
            body {
                flex-direction: column;
            }
            
            #sidebar {
                width: 100vw;
                width: 100dvw;  /* Dynamic viewport height */
            }
            
            #map {
                order: -1;  /* Mapa primero visualmente */
            }
            
            #togglePanel {
                top: auto;
                bottom: var(--space-md);
                left: var(--space-md);
                right: auto;
                padding: var(--space-sm) var(--space-lg);
            }
        }
```

---

### 2.2 En `views/business/map.php` - FIX FLOTANTE SELECTOR

**LÍNEAS A CAMBIAR: 54-78**

```
BÚSQUEDA ACTUAL:
═══════════════════════════════════════════════════════════════
        /* ── Floating selector (REDISEÑADO) ─────────────────────── */
        #ver-selector {
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            ...
        }

CAMBIO CLAVE (agregamos responsividad):
═══════════════════════════════════════════════════════════════
Después de la línea 78 (después del @keyframes slideDown), AGREGAR:

        /* ── RESPONSIVE: Tablet ──────────────────────────── */
        @media (max-width: 768px) {
            #ver-selector {
                top: var(--navbar-height);
                width: 90%;
                left: 5%;
                transform: translateX(0);
                gap: 0;
            }
            
            #ver-selector button {
                padding: var(--space-sm) var(--space-md);
                font-size: var(--font-size-sm);
            }
        }
        
        /* ── RESPONSIVE: Mobile ──────────────────────────── */
        @media (max-width: 640px) {
            #ver-selector {
                flex-direction: column;
                width: calc(100% - 2 * var(--space-md));
                top: var(--space-md);
                left: var(--space-md);
                bottom: auto;
            }
            
            #ver-selector button {
                width: 100%;
                padding: var(--space-md);
            }
            
            #ver-selector .drag-handle {
                display: none;
            }
        }
```

---

## 🎨 FASE 3: ESTANDARIZAR COMPONENTES

### 3.1 BOTONES - Crear `/css/components-buttons.css`

```
CONTENIDO PARA NUEVO ARCHIVO: /css/components-buttons.css
═══════════════════════════════════════════════════════════════

/* ─── BUTTON BASE STYLES ─── */
.btn,
button,
input[type="button"],
input[type="submit"],
input[type="reset"],
a.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  border: none;
  border-radius: var(--border-radius-md);
  font-family: var(--font-family-base);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  line-height: var(--line-height-tight);
  cursor: pointer;
  text-decoration: none;
  transition: all var(--transition-base);
  outline: none;
  position: relative;
  overflow: hidden;
}

/* ─── PRIMARY BUTTON (Azul profundo) ─── */
.btn-primary,
.btn.primary,
button:not([class]) {
  background: var(--primary);
  color: var(--text-inverse);
  box-shadow: var(--shadow-sm);
}

.btn-primary:hover,
.btn.primary:hover {
  background: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.btn-primary:active,
.btn.primary:active {
  transform: translateY(0);
  box-shadow: var(--shadow-sm);
}

.btn-primary:focus,
.btn.primary:focus {
  box-shadow: 0 0 0 3px rgba(27, 59, 111, 0.1);
}

/* ─── SECONDARY BUTTON (Dorado - Acento) ─── */
.btn-secondary,
.btn.secondary {
  background: transparent;
  color: var(--secondary);
  border: var(--border-width-normal) solid var(--secondary);
  box-shadow: none;
}

.btn-secondary:hover,
.btn.secondary:hover {
  background: rgba(212, 175, 55, 0.1);
  transform: translateY(-2px);
  box-shadow: var(--shadow-gold);
}

.btn-secondary:active,
.btn.secondary:active {
  background: rgba(212, 175, 55, 0.2);
  transform: translateY(0);
}

/* ─── DANGER BUTTON (Rojo) ─── */
.btn-danger,
.btn.danger {
  background: var(--accent);
  color: var(--text-inverse);
  box-shadow: var(--shadow-sm);
}

.btn-danger:hover,
.btn.danger:hover {
  background: var(--accent-dark);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.btn-danger:active,
.btn.danger:active {
  transform: translateY(0);
  box-shadow: var(--shadow-sm);
}

/* ─── SUCCESS BUTTON (Verde) ─── */
.btn-success,
.btn.success {
  background: var(--color-success);
  color: var(--text-inverse);
  box-shadow: var(--shadow-sm);
}

.btn-success:hover,
.btn.success:hover {
  background: #27ae60;
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

/* ─── SMALL BUTTONS ─── */
.btn-sm,
.btn.small {
  padding: var(--space-xs) var(--space-sm);
  font-size: var(--font-size-xs);
}

/* ─── LARGE BUTTONS ─── */
.btn-lg,
.btn.large {
  padding: var(--space-md) var(--space-lg);
  font-size: var(--font-size-md);
}

/* ─── FULL WIDTH ─── */
.btn-full,
.btn.full {
  width: 100%;
}

/* ─── DISABLED STATE ─── */
.btn:disabled,
button:disabled,
.btn[disabled] {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

/* ─── BUTTON GROUPS ─── */
.btn-group {
  display: flex;
  gap: var(--space-sm);
  flex-wrap: wrap;
}

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
  .btn {
    padding: var(--space-sm) var(--space-md);
    font-size: var(--font-size-sm);
  }
  
  .btn-lg {
    padding: var(--space-sm) var(--space-lg);
  }
}

@media (max-width: 640px) {
  .btn-full,
  .btn-group {
    width: 100%;
  }
  
  .btn-group {
    flex-direction: column;
  }
  
  .btn-group .btn {
    width: 100%;
  }
}
```

**✅ Acción:** Crea este archivo y enlázalo en `<head>` de TODOS los PHP

---

### 3.2 CARDS - Crear `/css/components-cards.css`

```
CONTENIDO PARA NUEVO ARCHIVO: /css/components-cards.css
═══════════════════════════════════════════════════════════════

/* ─── CARD BASE ─── */
.card,
.info-card,
[class*="-card"] {
  background: var(--color-white);
  border-radius: var(--border-radius-lg);
  padding: var(--space-lg);
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-base);
  border: var(--border-width-thin) solid var(--color-gray-200);
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--secondary);
}

/* ─── CARD VARIANTS ─── */
.card-elevated {
  box-shadow: var(--shadow-md);
}

.card-premium {
  background: linear-gradient(135deg, var(--color-white) 0%, var(--color-gray-50) 100%);
  border-left: var(--border-width-thick) solid var(--secondary);
  box-shadow: var(--shadow-gold);
}

.card-premium:hover {
  box-shadow: var(--shadow-gold-lg);
}

/* ─── CARD SECTIONS ─── */
.card-header {
  margin-bottom: var(--space-md);
  padding-bottom: var(--space-md);
  border-bottom: var(--border-width-thin) solid var(--color-gray-200);
}

.card-header h3,
.card-title {
  margin: 0 0 var(--space-sm) 0;
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-bold);
  color: var(--primary);
}

.card-body {
  margin-bottom: var(--space-md);
}

.card-footer {
  margin-top: var(--space-md);
  padding-top: var(--space-md);
  border-top: var(--border-width-thin) solid var(--color-gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--space-md);
}

/* ─── CARD GRID ─── */
.cards-grid,
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: var(--space-lg);
}

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
  .card {
    padding: var(--space-md);
  }
  
  .cards-grid {
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: var(--space-md);
  }
}

@media (max-width: 640px) {
  .card {
    padding: var(--space-md);
  }
  
  .cards-grid {
    grid-template-columns: 1fr;
  }
  
  .card-footer {
    flex-direction: column;
    align-items: stretch;
  }
  
  .card-footer .btn {
    width: 100%;
  }
}
```

---

### 3.3 FORMS - Crear `/css/components-forms.css`

```
CONTENIDO PARA NUEVO ARCHIVO: /css/components-forms.css
═══════════════════════════════════════════════════════════════

/* ─── FORM GROUP ─── */
.form-group {
  margin-bottom: var(--space-lg);
  display: flex;
  flex-direction: column;
}

/* ─── LABELS ─── */
label {
  display: block;
  margin-bottom: var(--space-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
  font-size: var(--font-size-sm);
}

label.required::after {
  content: " *";
  color: var(--accent);
}

/* ─── INPUT BASE ─── */
input[type="text"],
input[type="email"],
input[type="password"],
input[type="number"],
input[type="date"],
input[type="datetime-local"],
input[type="tel"],
input[type="url"],
textarea,
select {
  width: 100%;
  padding: var(--space-md);
  border: var(--border-width-normal) solid var(--color-gray-300);
  border-radius: var(--border-radius-md);
  font-family: var(--font-family-base);
  font-size: var(--font-size-md);
  color: var(--text-primary);
  background: var(--color-white);
  transition: all var(--transition-base);
  outline: none;
}

/* ─── INPUT FOCUS ─── */
input:focus,
textarea:focus,
select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(27, 59, 111, 0.1);
  background: var(--color-white);
}

/* ─── INPUT PLACEHOLDER ─── */
input::placeholder,
textarea::placeholder {
  color: var(--text-tertiary);
}

/* ─── TEXTAREA ─── */
textarea {
  resize: vertical;
  min-height: 120px;
  font-family: var(--font-family-base);
}

/* ─── SELECT ─── */
select {
  appearance: none;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231B3B6F' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right var(--space-md) center;
  background-size: 20px;
  padding-right: var(--space-2xl);
  cursor: pointer;
}

/* ─── CHECKBOX & RADIO ─── */
input[type="checkbox"],
input[type="radio"] {
  width: auto;
  margin-right: var(--space-sm);
  cursor: pointer;
}

/* ─── FORM HELPER TEXT ─── */
.form-help,
.form-hint {
  font-size: var(--font-size-xs);
  color: var(--text-tertiary);
  margin-top: var(--space-xs);
}

.form-error {
  color: var(--accent);
  font-size: var(--font-size-xs);
  margin-top: var(--space-xs);
}

/* ─── FORM ERROR STATE ─── */
input.error,
textarea.error,
select.error {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.1);
}

/* ─── FORM SUCCESS STATE ─── */
input.success,
textarea.success {
  border-color: var(--color-success);
  box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
}

/* ─── FORM DISABLED ─── */
input:disabled,
textarea:disabled,
select:disabled,
label[disabled] {
  opacity: 0.6;
  background: var(--color-gray-100);
  cursor: not-allowed;
}

/* ─── FORM WRAPPER ─── */
form {
  width: 100%;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--space-lg);
}

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
  input,
  textarea,
  select {
    font-size: 16px;  /* Previene zoom en iOS */
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
}
```

---

## 🛠️ FASE 4: NAVBARS & HEADERS

### 4.1 Fix Header en `admin/index.php` - LÍNEAS 47-64

```
BÚSQUEDA ACTUAL:
═══════════════════════════════════════════════════════════════
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }

REEMPLAZAR POR:
═══════════════════════════════════════════════════════════════
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--text-inverse);
            padding: var(--space-2xl) var(--space-lg);
            margin-bottom: var(--space-2xl);
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-xl);
            border-left: var(--border-width-thick) solid var(--secondary);
        }

        header h1 {
            font-size: var(--font-size-3xl);
            margin-bottom: var(--space-md);
            font-weight: var(--font-weight-bold);
            letter-spacing: -0.5px;
        }

        header p {
            opacity: 0.9;
            font-size: var(--font-size-sm);
            margin: 0;
        }
        
        /* ── RESPONSIVE ──────────────────────────── */
        @media (max-width: 768px) {
            header {
                padding: var(--space-lg) var(--space-md);
                margin-bottom: var(--space-lg);
            }
            
            header h1 {
                font-size: var(--font-size-2xl);
            }
        }
        
        @media (max-width: 640px) {
            header {
                padding: var(--space-lg) var(--space-md);
                border-radius: var(--border-radius-lg);
            }
            
            header h1 {
                font-size: var(--font-size-xl);
                margin-bottom: var(--space-sm);
            }
        }
```

---

### 4.2 Fix Tabs en `admin/index.php` - LÍNEAS 67-95

```
BÚSQUEDA ACTUAL:
═══════════════════════════════════════════════════════════════
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
            overflow-x: auto;
        }

        .tab-btn {
            padding: 12px 20px;
            border: none;
            background: none;
            color: #667eea;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

REEMPLAZAR POR:
═══════════════════════════════════════════════════════════════
        .tabs {
            display: flex;
            gap: 0;
            margin-bottom: var(--space-2xl);
            border-bottom: var(--border-width-normal) solid var(--color-gray-300);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .tab-btn {
            padding: var(--space-md) var(--space-lg);
            border: none;
            background: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-weight: var(--font-weight-semibold);
            font-size: var(--font-size-md);
            border-bottom: var(--border-width-thick) solid transparent;
            transition: all var(--transition-base);
            white-space: nowrap;
            position: relative;
        }

        .tab-btn:hover {
            color: var(--primary);
            border-bottom-color: var(--secondary);
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -var(--border-width-normal);
            left: 0;
            right: 0;
            height: var(--border-width-normal);
            background: var(--primary);
        }
        
        /* ── RESPONSIVE ──────────────────────────── */
        @media (max-width: 768px) {
            .tabs {
                margin-bottom: var(--space-lg);
            }
            
            .tab-btn {
                padding: var(--space-md) var(--space-md);
                font-size: var(--font-size-sm);
            }
        }
```

---

## ✨ FASE 5: CHECKLIST DE APLICACIÓN

### Prioridad 1️⃣ (CRÍTICA - Aplicar hoy)

- [ ] **1. Crear** `/css/variables-luxury.css` ← Centraliza todo
- [ ] **2. Enlazar** `variables-luxury.css` en `<head>` de:
  - [ ] `views/business/map.php`
  - [ ] `views/business/business_detail_micro.php`
  - [ ] `views/brand/brand_detail_micro.php`
  - [ ] `admin/index.php`
- [ ] **3. Modificar** `views/business/map.php` líneas 39-52 (Sidebar responsive)
- [ ] **4. Modificar** `views/business/map.php` líneas 54-78 (Selector flotante)
- [ ] **5. Crear** `/css/components-buttons.css` y enlazar en todos los PHP

### Prioridad 2️⃣ (IMPORTANTE - Esta semana)

- [ ] **6. Crear** `/css/components-cards.css` y enlazar
- [ ] **7. Crear** `/css/components-forms.css` y enlazar
- [ ] **8. Modificar** `admin/index.php` líneas 47-64 (Header)
- [ ] **9. Modificar** `admin/index.php` líneas 67-95 (Tabs)

### Prioridad 3️⃣ (MEJORA - Próxima semana)

- [ ] **10.** Revisar y actualizar clases en:
  - [ ] `views/brand/brand_detail_micro.php`
  - [ ] `views/business/business_detail_micro.php`
- [ ] **11.** Añadir microinteracciones (hover states)
- [ ] **12.** Testear en móviles (iPhone 12, Samsung A50, iPad)

---

## 📋 TESTING RESPONSIVE

### Puntos clave a verificar:

```
MOBILE (320-480px):
✓ Sidebar se convierte a modal/drawer
✓ Botones expandibles al 100%
✓ Selelector flotante en bottom
✓ Textos legibles (16px mínimo)
✓ Tap targets mínimo 44x44px

TABLET (768px):
✓ Sidebar desplegable con toggle
✓ Dos columnas en grids
✓ Headers con padding reducido
✓ Tabs scrolleables si hay muchos

DESKTOP (1024px+):
✓ Sidebar visible siempre
✓ Grid 3+ columnas
✓ Hover states activos
✓ Máximo ancho 1200px
```

---

## 🎯 SIGUIENTES PASOS

1. **Lee este documento línea por línea**
2. **Copia-pega EXACTAMENTE el código** proporcionado
3. **Prueba en móvil tras cada cambio** con `Ctrl+Shift+M` en DevTools
4. **Sube a Hostinger** y verifica en real device
5. **Reporta cualquier error** (texto que no quepa, colores raros, etc.)

---

**Documento generado:** 2026-04-17  
**Versión:** Mapita v1.2.0  
**Total de archivos a crear/modificar:** 9  
**Tiempo estimado de aplicación:** 2-3 horas  

