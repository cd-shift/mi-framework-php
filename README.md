# Mi Framework PHP

Framework PHP minimalista para aprender routing, ciclo HTTP, middlewares, helpers globales, renderizado de vistas y bootstrap de aplicación desde una base propia.

## Estado actual

El proyecto ya incluye:
- Bootstrap central con `Framework\App`
- Contenedor simple con instancias singleton
- Helpers globales para `app()`, `singleton()`, `json()`, `redirect()`, `view()` y `request()`
- Router con soporte para `GET`, `POST`, `PUT`, `PATCH`, `DELETE`
- Rutas con parámetros como `/test/{param}`
- Middlewares por ruta
- Objetos `Request` y `Response` con helpers para headers, query, body y route parameters
- Motor de vistas `View\MiEngine` con layouts y renderizado de plantillas PHP
- Adaptador de servidor nativo (`PhpNativeServer`)
- Suite de pruebas para HTTP, routing y vistas
- Generación de documentación con `phpDocumentor`
- Formato de código con `PHP-CS-Fixer`

## Requisitos

- PHP `^8.2`
- Composer

## Instalación

```bash
composer install
```

## Ejecución local

```bash
php -S localhost:8000 -t public
```

El front controller actual vive en `public/index.php` y utiliza `Framework\App::bootstrap()` para levantar router, request, server adapter y view engine.

## Comandos útiles

```bash
composer tests
composer tests:quick
composer tests:one -- tests/HTTP/ResponseTest.php --filter test_json_response_is_structured_correctly
composer test-coverage
composer cs:check
composer cs:fix
composer docs:generate
```

La documentación generada se escribe en `build/docs`.

El comando `composer docs:generate` genera la API de `src/` y `tests/`. `public/index.php` también está documentado con DocBlocks, pero se mantiene fuera del HTML generado porque funciona más como bootstrap de ejecución que como API reusable.

## Estructura del proyecto

```text
public/
  index.php                    # Front controller
src/
  App.php                      # Application bootstrap and runtime coordination
  Container/
    Container.php              # Simple singleton container
  Helpers/
    AppHelper.php              # Global helpers for app/container access
    HttpHelper.php             # Global HTTP and view response helpers
  Http/
    HttpMethod.php
    HttpNotfoundException.php
    Middleware.php
    Request.php
    Response.php
  Routing/
    Route.php
    Router.php
  Server/
    PhpNativeServer.php
    Server.php
  View/
    MiEngine.php               # Layout-based PHP view engine
    View.php                   # View rendering contract
tests/
  HTTP/
    RequestTest.php
    ResponseTest.php
  Routing/
    RouteTest.php
    RouterTest.php
  View/
    MiEngineTest.php
```

## Flujo actual

1. `App::bootstrap()` crea el contenedor mínimo de aplicación.
2. El adaptador `PhpNativeServer` construye un `Request`.
3. `Routing\Router` resuelve la ruta y asigna el `Route` al request.
4. Si la ruta tiene middlewares, se ejecuta la cadena antes de la acción final.
5. La acción devuelve un `Response` o usa helpers como `json()` o `view()`.
6. Si la respuesta es HTML, `View\MiEngine` renderiza la plantilla y su layout.
7. El server adapter envía la respuesta final al cliente.

## Roadmap sugerido

- Mejorar manejo de errores HTTP (`404`, `405`, `500`)
- Definir una estrategia de middlewares globales además de middlewares por ruta
- Fortalecer el contenedor para resolución de dependencias más allá de singletons
- Aumentar cobertura de casos borde para middlewares, headers y errores de routing
- Añadir pruebas para helpers globales y respuestas HTML
- Separar middlewares de ejemplo fuera del front controller

## Referencias

- [PSR-4: Autoloading](https://www.php-fig.org/psr/psr-4/)
- [PSR-7: HTTP Messages](https://www.php-fig.org/psr/psr-7/)
