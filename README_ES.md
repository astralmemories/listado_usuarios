
# Listado Usuarios

## Objetivo

**Listado Usuarios** es un módulo personalizado para Drupal 10/11, desarrollado como parte de una prueba técnica. El objetivo fue demostrar un sistema de listado de usuarios completamente funcional **sin depender de Drupal Views**, utilizando solo código personalizado y buenas prácticas de desarrollo en Drupal.

El módulo muestra una **lista paginada de usuarios**, presentando la siguiente información por cada usuario:

- Nombre de usuario  
- Nombre  
- Apellido1 y Apellido2  
- Correo electrónico

En la parte superior del listado se encuentra un formulario de búsqueda que permite filtrar los resultados por nombre, apellidos y correo electrónico. Tanto el filtrado como la paginación están implementados usando **AJAX**, proporcionando una experiencia fluida sin recargar la página.

Los datos de los usuarios se obtienen a través de una **llamada POST simulada** a una API que devuelve un JSON. El conjunto de datos está simulado para asegurar que la paginación funcione correctamente (5 usuarios por página).

Este módulo demuestra técnicas de programación personalizada en Drupal, incluyendo el uso de formularios, AJAX, JavaScript y CSS personalizado, cumpliendo los requisitos de la prueba técnica. Proporciona una interfaz limpia y amigable para la gestión y visualización de datos de usuarios.

---

## Funcionalidades Principales

1. **Listado de Usuarios**
   - Muestra una lista paginada de usuarios, 5 por página.
   - Muestra el nombre de usuario, nombre, apellidos y correo electrónico.

2. **Búsqueda y Filtrado**
   - Un formulario permite filtrar por nombre, apellidos y correo electrónico.

3. **Funcionalidad AJAX**
   - La búsqueda y la paginación funcionan con AJAX, sin recargar la página.

4. **Integración con API Simulada**
   - Los datos se obtienen desde una API simulada (`fetch_users.php`) que lee de un archivo JSON (`usuarios.json`) y permite paginación y filtrado.

5. **Ajustes Configurables**
   - URL de la API
   - Usuarios por página
   - Activar/desactivar logs en consola para depuración

6. **JavaScript Personalizado**
   - Controla la paginación AJAX y reatacha comportamientos de Drupal dinámicamente.

7. **Estilos Personalizados**
   - Incluye CSS para la tabla, listado y paginación.

---

## Estructura del Módulo

```
listado_usuarios/
│── api/
│   └── fetch_users.php
│── config/
│   ├── install/
│   │   └── listado_usuarios.settings.yml
│   ├── schema/
│   │   └── listado_usuarios.schema.yml
│── css/
│   └── style.css
│── data/
│   └── usuarios.json
│── js/
│   └── custom-pager.js
│── src/
│   └── Form/
│       ├── ListadoUsuariosForm.php
│       └── SettingsForm.php
│── listado_usuarios.info.yml
│── listado_usuarios.libraries.yml
│── listado_usuarios.links.menu.yml
│── listado_usuarios.routing.yml
```

---

## ¿Cómo Funciona?

1. **Configuración**
   - Los administradores configuran la URL de la API, usuarios por página y consola desde el formulario de ajustes.

2. **Listado de Usuarios**
   - `ListadoUsuariosForm` obtiene y muestra datos vía AJAX, con filtros y paginación.

3. **Actualizaciones AJAX**
   - El método `updateList()` gestiona solicitudes AJAX para actualizar el listado y la paginación.

4. **Paginador Personalizado**
   - Controlado por `custom-pager.js`, asegura navegación compatible con AJAX.

5. **Integración con API**
   - `fetch_users.php` filtra y pagina los datos de `usuarios.json`, devolviendo un JSON.

---

## Capturas de Pantalla

### Página del Módulo  
![Página del Módulo](./screenshots/module_page.png)  
*La página `/listado-usuarios` mostrando la tabla de usuarios.*

### Funcionalidad del Paginador  
![Paginador](./screenshots/module_pager.png)  
*Navega por la lista de usuarios usando el paginador con AJAX.*

### Filtrado con la Caja de Búsqueda  
![Filtrado](./screenshots/search_box_filtering.png)  
*La caja de búsqueda permite filtrar fácilmente la tabla.*

### Página de Configuración del Módulo  
![Configuración](./screenshots/configuration_page.png)  
*La página `/admin/config/system/listado_usuarios` mostrando las opciones de configuración.*

---

## Requisitos

Este módulo requiere:

- **Drupal core**: `^10 || ^11`

Asegúrate de usar una versión compatible antes de habilitar el módulo.

---

## Instalación

Para instalar el módulo **Listado Usuarios** en tu proyecto Drupal:

### 1. Clonar o Copiar el Módulo

Clona el repositorio dentro del directorio `modules/custom/`:

```bash
git clone https://github.com/astralmemories/listado_usuarios.git web/modules/custom/listado_usuarios
```

### 2. Habilitar el Módulo

Usando Drush:

```bash
drush en listado_usuarios
```

O desde la interfaz administrativa:

- Ve a **Extender** (`/admin/modules`)
- Busca **Listado Usuarios**
- Marca la casilla y haz clic en **Instalar**

### 3. Limpiar Caché (Recomendado)

Limpia la caché para registrar rutas y servicios:

```bash
drush cr
```
