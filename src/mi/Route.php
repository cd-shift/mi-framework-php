<?php

namespace mi;

use Closure;
use function array_combine;
use function array_slice;

class Route{
    protected string $uri;
    protected Closure $action;
    protected string $regex;
    protected array $params;

    public function __construct(string $uri, Closure $action){
        $this->uri = $uri;
        $this->action = $action;
        $this->regex = preg_replace('/\{([a-zA-Z]+)\}/', '([a-zA-Z0-9]+)', $uri);
        // En PHP se puede omitir este, se contruye abajo tambien 
        // $this->params = [];
        preg_match_all('/\{([a-zA-Z]+)\}/', $uri, $params);
        $this->params = $params[1];
    }

    // Getters & Setters
    // Version moderna de PHP se puede omitir get y set
    public function Uri(){
        return $this->uri;
    }

    public function action(){
        return $this->action;
    }

    // Funcion si una uri hace match
    public function matches(string $uri):bool{
        return preg_match("#^/?{$this->regex}$#", $uri) === 1;
    }

    // Funcion para saber si la ruta tiene o no parametros
    public function hasParameter():bool{
        return count($this->params) > 0;
    } 

    // Funcion que devolvera los parametros en forma de clave-valor
    public function parseParameters(string $uri):array{
        preg_match("#^/?{$this->regex}$#", $uri, $arguments);
        return array_combine($this->params, array_slice($arguments,1)) ?: [];
    }
}