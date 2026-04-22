# Mi Framework PHP

Proyecto académico para aprender cómo funciona un framework PHP por dentro, construyéndolo desde cero.

## Objetivo

Entender los conceptos fundamentales de un framework web:
- Enrutamiento (routing)
- Request/Response cycle
- Arquitectura MVC
- Y más conceptos esenciales

## Estado

En desarrollo - etapa inicial.

## Instalación

```bash
# Instalar dependencias (cuando existan)
composer install

# Iniciar servidor de desarrollo
php -S localhost:8000
```

## Estructura

```
src/              # Código fuente del framework
index.php         # Punto de entrada
router.php        # Sistema de enrutamiento
```

## Próximos pasos

- [ ] Implementar Request/Response
- [ ] Completar sistema de rutas
- [ ] Añadir estructura MVC
- [ ] Agregar tests

## Recursos de referencia

- [PSR-4: Autoloading](https://www.php-fig.org/psr/psr-4/)
- [PSR-7: HTTP Messages](https://www.php-fig.org/psr/psr-7/)
- [Laravel Router](https://github.com/laravel/framework/tree/master/src/Illuminate/Routing) - inspiraciones
