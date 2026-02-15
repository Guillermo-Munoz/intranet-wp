# 🎯 INTRANET GESTORÍA - REFACTORIZADO v2.0.0

Sistema de gestión de documentos y auditoría de borrados para WordPress.

## 📦 ARCHIVOS INCLUIDOS
```
CORE:
01-intranet-gestoria-ACTUALIZADO.php    → wp-content/plugins/intranet-gestoria/intranet-gestoria.php
02-autoload.php                         → wp-content/plugins/intranet-gestoria/includes/autoload.php
03-constants.php                        → wp-content/plugins/intranet-gestoria/includes/constants.php
04-functions.php                        → wp-content/plugins/intranet-gestoria/includes/functions.php

FILE MANAGEMENT:
05-FileHandler.php                      → wp-content/plugins/intranet-gestoria/src/File/FileHandler.php
06-FileSecurity.php                     → wp-content/plugins/intranet-gestoria/src/File/FileSecurity.php

TRASH/AUDIT:
07-TrashLogger.php                      → wp-content/plugins/intranet-gestoria/src/Trash/TrashLogger.php
08-TrashManager.php                     → wp-content/plugins/intranet-gestoria/src/Trash/TrashManager.php

CLIENT SIDE:
09-ClientManager.php                    → wp-content/plugins/intranet-gestoria/src/Client/ClientManager.php
10-ClientUI.php                         → wp-content/plugins/intranet-gestoria/src/Client/ClientUI.php
11-ClientActions.php                    → wp-content/plugins/intranet-gestoria/src/Client/ClientActions.php

ADMIN SIDE:
12-AdminManager.php                     → wp-content/plugins/intranet-gestoria/src/Admin/AdminManager.php
13-AdminUI.php                          → wp-content/plugins/intranet-gestoria/src/Admin/AdminUI.php
14-AdminActions.php                     → wp-content/plugins/intranet-gestoria/src/Admin/AdminActions.php

STYLES:
15-styles.css                           → wp-content/plugins/intranet-gestoria/assets/css/styles.css
```

## 📂 ESTRUCTURA DE CARPETAS

```
wp-content/
└── plugins/
    └── intranet-gestoria/
        ├── intranet-gestoria.php           (USAR: 01-ACTUALIZADO.php)
        ├── includes/
        │   ├── autoload.php                (02)
        │   ├── constants.php               (03)
        │   └── functions.php               (04)
        ├── src/
        │   ├── File/
        │   │   ├── FileHandler.php         (05)
        │   │   └── FileSecurity.php        (06)
        │   ├── Trash/
        │   │   ├── TrashLogger.php         (07)
        │   │   └── TrashManager.php        (08)
        │   ├── Client/
        │   │   ├── ClientManager.php       (09)
        │   │   ├── ClientUI.php            (10)
        │   │   └── ClientActions.php       (11)
        │   └── Admin/
        │       ├── AdminManager.php        (12)
        │       ├── AdminUI.php             (13)
        │       └── AdminActions.php        (14)
        └── assets/
            └── css/
                └── styles.css              (15) ← CSS EXTERNO
```

## 🚀 INSTALACIÓN PASO A PASO

### PASO 1: Copia la carpeta en Plugins

### PASO 3: Activar en WordPress

1. Ve a **WordPress > Plugins**
2. Busca "Intranet Gestoría"
3. Haz clic en **Activar**

### PASO 4: Verificar funcionamiento

✅ Los estilos CSS se cargan automáticamente
✅ Shortcodes están registrados
✅ Papelera de auditoría funciona

## ✅ VERIFICACIÓN

```
□ Plugin aparece en WordPress > Plugins
□ CSS se carga (revisa en Inspeccionar > Estilos)
□ Shortcode [area_cliente_simple] funciona
□ Shortcode [admin_ver_simple] funciona
□ Subida de archivos funciona
□ Papelera de auditoría funciona
□ Borrado de archivos funciona
```

## 🔧 SHORTCODES

**Página de cliente:**
```
[area_cliente_simple]
```

**Página de administrador:**
```
[admin_ver_simple]
```

## 📊 CARACTERÍSTICAS COMPLETAS

✅ Área de cliente con drag-and-drop
✅ Pantalla de carga (loading overlay)
✅ Panel admin con gestión de clientes
✅ Sistema de papelera de auditoría
✅ Log de eliminaciones con acordeón
✅ Descarga de archivos
✅ Búsqueda en tiempo real
✅ Botones de acción circular
✅ Interfaz responsive (móvil, tablet, desktop)
✅ Tema oscuro (automático según preferencias)
✅ Accesibilidad mejorada
✅ Seguridad validada

## 🎨 ESTILOS CSS INCLUIDOS

El archivo `styles.css` incluye:

- **Pantalla de carga:** Overlay con spinner
- **Contenedores:** Cliente y gestor
- **Tablas:** Responsivas con badges
- **Botones:** Circulares minimalistas
- **Acordeón:** Para el log de eliminaciones
- **Responsive:** Móvil (< 600px), Tablet (601-1024px), Desktop (1025px+)
- **Tema oscuro:** Automático según `prefers-color-scheme`
- **Accesibilidad:** Respeta `prefers-reduced-motion`
- **Impresión:** Optimizado para PDF

## 🛠️ PERSONALIZACIÓN

### Cambiar colores principales

En `styles.css`, busca `#003B77` y reemplázalo:

```css
#003B77 → Tu color preferido
```

### Cambiar animaciones

```css
animation-duration: 1s → 2s (más lento)
transition: 0.2s ease → 0.5s ease (más lento)
```

### Cambiar tamaños de fuente

```css
font-size: 14px → 16px (más grande)
```

## 📱 RESPONSIVE

**Móvil (< 600px):**
- Tablas convertidas a bloques
- Botones más grandes
- Texto más legible
- Stack vertical

**Tablet (601-1024px):**
- Ancho 95%
- Fuente ligéramente reducida
- Layout optimizado

**Desktop (1025px+):**
- Ancho máximo 1000px
- Centrado en pantalla
- Espacios generosos

## 🌙 TEMA OSCURO

Automáticamente se aplica si el usuario tiene:
- `prefers-color-scheme: dark` en su sistema operativo

Se puede probar en:
- Chrome DevTools > Rendering > Emulate CSS media feature prefers-color-scheme

## ♿ ACCESIBILIDAD

- Respeta `prefers-reduced-motion`
- Botones accesibles
- Contraste de colores WCAG AA
- Navegación por teclado
- Labels claros

## 📝 NOTAS IMPORTANTES

1. **El archivo principal debe llamarse:** `intranet-gestoria.php` (en raíz de la carpeta del plugin)

2. **El CSS se carga automáticamente** via `wp_enqueue_scripts()` y `admin_enqueue_scripts()`

3. **No necesitas incluir CSS en los archivos PHP** - ya está todo en `styles.css`

4. **Los estilos inline se eliminaron** - todo está en CSS externo para mejor rendimiento

5. **Compatible con:**
   - WordPress 5.0+
   - PHP 7.2+
   - Todos los navegadores modernos

## 🐛 TROUBLESHOOTING

**Problema: CSS no carga**
- Verifica que `assets/css/styles.css` existe
- Haz Ctrl+Shift+R (reload sin caché)
- Revisa WordPress > Plugins que está activo

**Problema: Página se ve rota**
- El CSS no se cargó correctamente
- Verifica permisos de carpeta assets/
- Comprueba en inspeccionar > aplicación que se carga

**Problema: Botones sin estilos**
- Espera a que cargue el CSS
- Limpia caché del navegador
- Verifica que styles.css no tiene errores

## 🎓 APRENDIZAJE

Archivos para entender la arquitectura (en orden):

1. `intranet-gestoria.php` - Punto de entrada
2. `includes/autoload.php` - Sistema de carga de clases
3. `src/File/FileHandler.php` - Lógica de archivos
4. `src/Client/ClientManager.php` - Gestión de cliente
5. `src/Client/ClientUI.php` - Interfaz cliente
6. `assets/css/styles.css` - Todos los estilos

## 📞 SOPORTE

Si tienes problemas:

1. Verifica estructura de carpetas
2. Revisa permisos (755)
3. Limpia caché de navegador
4. Desactiva plugins de caché
5. Revisa error_log de WordPress

## 🎉 ¡LISTO!

Deberías tener un sistema profesional y modular.

Los estilos están centralizados, el código es limpio, y es fácil de extender.

**Versión:** 2.0.0  
**Última actualización:** 2025  
**Estado:** ✅ Completamente funcional
