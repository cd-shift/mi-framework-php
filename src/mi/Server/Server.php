<?php

namespace mi\Server;

use mi\Http\HttpMethod;

/*
    Interface donde se declara los metodos que debe implementar y hacer una clase concreta
    en cualquier servidor
 */
interface Server {
    public function requestUri(): string;
    public function requestMethod(): HttpMethod;
    public function postData(): array;
    public function queryParams(): array;
}