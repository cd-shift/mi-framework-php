<?php

declare(strict_types=1);

namespace View;

interface View
{
    public function render(string $view): string;
}
