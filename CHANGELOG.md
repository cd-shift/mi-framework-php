# Registro de Archivos

Documentación de los archivos del proyecto, su propósito y funcionamiento.

---

## Archivos del Proyecto

### 1. `index.php` - Punto de Entrada

**¿Qué es?**
Archivo principal que se ejecuta cuando alguien accede al servidor.

**¿Por qué existe?**
Es el punto de entrada de toda aplicación PHP. Cuando el servidor recibe una petición, ejecuta este archivo primero.

**¿Cómo funciona?**

```php
require "./router.php";          // Carga el archivo del router

$router = new Router();           // Crea instancia del router

// Registra rutas - define qué código ejecutar según la URL
$router->get('/test', function () {
    return "GET OK";
});

$router->post('/test', function () {
    return "POST OK";
});

// Intenta encontrar y ejecutar la ruta correspondiente
try {
    $action = $router->resolve();  // Busca la ruta
    print($action());                // Ejecuta y muestra el resultado
} catch (HttpNotFoundException $e) { // Si no existe la ruta
    print("NOT FOUND");
    http_response_code(404);
}
```

---

### 2. `router.php` - Sistema de Enrutamiento

**¿Qué es?**
Clase que gestiona el registro y resolución de rutas HTTP.

**¿Por qué existe?**
Es el "cerebro" de la aplicación. Sin un router, no hay forma de saber qué código ejecutar para cada URL.

**¿Cómo funciona?**

```php
class Router {
    protected array $routes = [];  // Almacena todas las rutas registradas

    // Constructor: inicializa un array para cada método HTTP
    public function __construct() {
        foreach (HttpMethod::cases() as $method) {
            $this->routes[$method->value] = [];
        }
    }

    // GET: registra ruta para método GET
    public function get(string $uri, callable $action) {
        $this->routes[HttpMethod::GET->value][$uri] = $action;
    }

    // POST: registra ruta para método POST
    public function post(string $uri, callable $action) {
        $this->routes[HttpMethod::POST->value][$uri] = $action;
    }

    // Resolve: encuentra la ruta que coincide con la petición actual
    public function resolve() {
        $method = $_SERVER["REQUEST_METHOD"];   // Ej: "GET"
        $uri = $_SERVER["REQUEST_URI"];          // Ej: "/test"

        // Busca si existe una ruta registrada para esa combinación
        $action = $this->routes[$method][$uri] ?? null;

        // Si no existe, lanza excepción 404
        if (is_null($action)) {
            throw new HttpNotFoundException();
        }

        return $action;  // Retorna la función a ejecutar
    }
}
```

---

### 3. `HttpMethod.php` - Enum de Métodos HTTP

**¿Qué es?**
Enumeración que define los métodos HTTP soportados.

**¿Por qué existe?**
- Evita errores de tipeo (escribir "GET" vs "GOT")
- Mejora el autocompletado en el IDE
- Centraliza los métodos válidos en un solo lugar

**¿Cómo funciona?**

```php
enum HttpMethod: string {
    case GET = "GET";     // "GET" es el valor real
    case POST = "POST";
    case PUT = "PUT";
    case PATCH = "PATCH";
    case DELETE = "DELETE";
}
```

Uso: `HttpMethod::GET->value` retorna `"GET"`.

---

### 4. `HttpNotFoundException.php` - Excepción 404

**¿Qué es?**
Excepción personalizada para manejar rutas no encontradas.

**¿Por qué existe?**
Permite diferenciar entre errores generales y el caso específico de "ruta no encontrada" (404).

**¿Cómo funciona?**

```php
class HttpNotFoundException extends Exception {
    // Solo hereda el comportamiento de Exception
    // No necesita código adicional por ahora
}
```

---

### 5. `README.md` - Documentación General

**¿Qué es?**
Archivo de documentación principal del proyecto.

**¿Por qué existe?**
Proporciona contexto a cualquier persona que vea el repositorio: qué es el proyecto, cómo instalarlo, su estado actual, y próximos pasos.

---

### 6. `AGENTS.md` - Guía para Agentes IA

**¿Qué es?**
Archivo de configuración para sesiones de OpenCode/IA.

**¿Por qué existe?**
Ayuda a agentes de IA a entender las particularidades del proyecto (cómo correrlo, estructura, limitaciones) sin necesidad de explicarlo en cada sesión.

---

## Flujo de Ejecución

```
Usuario accede a http://localhost:8000/test (GET)

        ↓
    
index.php se ejecuta

        ↓
    
$router->resolve() es llamado

        ↓
    
$_SERVER["REQUEST_METHOD"] = "GET"
$_SERVER["REQUEST_URI"] = "/test"

        ↓
    
Busca en $routes["GET"]["/test"]

        ↓
    
Encuentra la función anónima
return "GET OK";

        ↓
    
print() muestra "GET OK" en el navegador
```

---

## Notas Técnicas

- **Sin Composer**: Los archivos se cargan con `require` manual
- **Sin dependencias**: Todo el código es propio
- **Rutas simples**: Solo acepta URLs exactas (sin wildcards ni parámetros)
- **PSR-4**: No implementado aún (usa require manual)
