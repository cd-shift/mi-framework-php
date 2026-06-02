# Changelog

Todos los cambios importantes de este proyecto se documentan aquí.

## [2026-05-27]

### Cambiado
- `Request::data()`, `Request::query()` y `Request::routeParameters()` ahora permiten devolver un valor específico por clave además del arreglo completo.
- Ajustes en `public/index.php` para mantener consistencia con el flujo actual de request/response.
- Nuevas pruebas para validar acceso por clave en `Request`.

## [2026-05-25]

### Agregado
- Pruebas unitarias para `Request` en `tests/HTTP/RequestTest.php`.

### Cambiado
- Refactor de `Request` y contratos del adaptador de servidor.
- Reorganización de tests de `Response` a `tests/HTTP/ResponseTest.php`.

## [2026-05-24]

### Agregado
- Configuración de `PHP-CS-Fixer` en `.php-cs-fixer.dist.php`.

### Cambiado
- Normalización de estilo en código fuente y pruebas.
- Scripts de calidad en `composer.json` (`cs:check`, `cs:fix`).

## [2026-05-21]

### Cambiado
- Mejoras en factorías de `Response` (`json`, `text`, `redirect`).

## [2026-05-14]

### Agregado
- Clase `Http\Response` con soporte de estado, headers, contenido y método `prepare()`.

### Cambiado
- Reorganización final de namespaces y carpetas a:
  - `src/Http`
  - `src/Routing`
  - `src/Server`
- Migración de pruebas a estructura por dominio (`tests/HTTP`, `tests/Routing`).

## [2026-05-13]

### Agregado
- Clase `Http\Request`.
- Interfaz `Server\Server` y adaptador `Server\PhpNativeServer`.
- Configuración de PHPUnit en `phpunit.xml`.

### Cambiado
- Router y pruebas actualizados para trabajar con `Request` y el adaptador de servidor.

## [2026-05-12]

### Agregado
- Pruebas parametrizadas para `Route`.

### Cambiado
- Mejoras al matching de rutas y parseo de parámetros.

## [2026-04-27]

### Agregado
- Clase `Routing\Route`.
- Pruebas iniciales para `Router`.
- `composer.lock`.

### Cambiado
- Soporte de rutas con parámetros en el router.

## [2026-04-23]

### Agregado
- `composer.json` con autoload PSR-4.
- Front controller en `public/index.php`.
- Configuración base de proyecto para agentes en `.agents/skills/`.

### Cambiado
- Reorganización de archivos bajo `src/`.

### Eliminado
- `index.php` en raíz (reemplazado por `public/index.php`).

## [2026-04-21]

### Agregado
- Commit inicial: router básico, enum de métodos HTTP y excepción para 404.
