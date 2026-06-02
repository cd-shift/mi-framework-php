# Mi Framework PHP

Framework PHP minimalista para aprender internals de routing y ciclo HTTP.

## Estado Actual

Proyecto funcional en fase temprana con:
- Autoload PSR-4 vía Composer
- Router con soporte para `GET`, `POST`, `PUT`, `PATCH`, `DELETE`
- Rutas con parámetros (`/test/{param}`)
- Objetos `Request` y `Response`
- Adaptador de servidor nativo (`PhpNativeServer`)
- Suite de pruebas unitarias con PHPUnit

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

## Pruebas y calidad

```bash
composer tests
composer cs:check
composer cs:fix
```

## Estructura del proyecto

```text
public/
  index.php                # Front controller
src/
  Http/
    HttpMethod.php
    HttpNotfoundException.php
    Request.php
    Response.php
  Routing/
    Route.php
    router.php
  Server/
    PhpNativeServer.php
    Server.php
tests/
  HTTP/
  Routing/
```

## Próximos objetivos sugeridos

- Implementar pipeline de middleware
- Mejorar manejo de errores HTTP (`404`, `405`, `500`)
- Aumentar cobertura de casos borde en tests
- Estandarizar nombre de `router.php` a `Router.php`

## Referencias

- [PSR-4: Autoloading](https://www.php-fig.org/psr/psr-4/)
- [PSR-7: HTTP Messages](https://www.php-fig.org/psr/psr-7/)
