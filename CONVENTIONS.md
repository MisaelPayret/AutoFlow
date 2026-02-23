# AutoFlow — Convenciones del proyecto

Fecha: 10/02/2026

## 1) Enrutado
- Formato: `recurso/accion`.
- CRUD estándar:
  - `index`, `create`, `store`, `edit`, `update`, `delete`, `show`.
- Rutas públicas: `Public/*` o acciones `share`.
- Todas las rutas deben estar definidas en `Router/web.php`.

## 2) Controladores
- Nombre: `XController`.
- Archivo: `Controller/XController.php`.
- Métodos: verbos simples (`index`, `create`, etc.).
- Autenticación: centralizar en `ensureAuthenticated()`.
- Redirecciones: usar `redirectToRoute()`.

## 3) Modelos
- Nombre: `XModel`.
- Archivo: `Model/XModel.php`.
- Métodos recomendados:
  - `find`, `create`, `update`, `delete`.
  - `normalizeInput`, `validate`.
  - `listWith*` o `search` según el caso.

## 4) Vistas
- Ubicación: `View/<Modulo>/<Nombre>.php`.
- Nombres sugeridos: `Index.php`, `Form.php`, `Show.php`.
- Layout: incluir `View/Include/Header.php` y `View/Include/Footer.php`.
- Variables: definir defaults al inicio de la vista.

## 5) Assets públicos
- CSS global: `Public/Css/Global.css`.
- JS por módulo: `Public/Js/<modulo>-*.js`.
- Imágenes: `Public/Img/`.
- Uploads: `Public/Uploads/<Modulo>/<id>/`.

## 6) Nomenclatura y estilo
- Variables en `snake_case` para inputs HTTP y campos DB.
- Variables internas en `camelCase`.
- Métodos y clases en `PascalCase`.
- Evitar lógica compleja dentro de las vistas.

## 7) Seguridad
- Sanitizar salida con `htmlspecialchars`.
- Validar inputs en el servidor.
- Usar `POST` para acciones destructivas.
