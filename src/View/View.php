<?php

declare(strict_types=1);

namespace View;

/**
 * Defines the contract for HTML view renderers.
 */
interface View
{
    /**
     * Renders a view using the given parameters and optional layout.
     *
     * @param string $view View name relative to the views directory.
     * @param array<string, mixed> $params Variables exposed to the template.
     * @param string|null $layout Layout name or null to use the default layout.
     * @return string
     */
    public function render(string $view, array $params = [], ?string $layout = null): string;
}
