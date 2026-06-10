<?php

declare(strict_types=1);

namespace View;

/**
 * Renders PHP templates inside a layout-based view system.
 */
class MiEngine implements View
{
    /**
     * Base directory that contains view and layout templates.
     */
    protected string $viewsDirectory;

    /**
     * Default layout name used when none is provided.
     */
    protected string $defaultLayout = 'main';

    /**
     * Placeholder replaced with rendered view content inside the layout.
     */
    protected string $contentAnnotation = '@content';

    /**
     * Creates a new view engine bound to a views directory.
     *
     * @param string $viewsDirectory Absolute or relative path to the views directory.
     */
    public function __construct(string $viewsDirectory)
    {
        $this->viewsDirectory = $viewsDirectory;
    }

    /**
     * Renders a view and injects it into the selected layout.
     *
     * @param string $view View name relative to the views directory.
     * @param array<string, mixed> $params Variables exposed to the view template.
     * @param string|null $layout Layout name or null to use the default layout.
     * @return string
     */
    public function render(string $view, array $params = [], ?string $layout = null): string
    {
        $layoutContent = $this->renderLayout($layout ?? $this->defaultLayout);
        $viewContent = $this->renderView($view, $params);

        return str_replace($this->contentAnnotation, $viewContent, $layoutContent);
    }

    /**
     * Evaluates a PHP template file and captures its output buffer.
     *
     * @param string $phpFile Absolute path to the PHP template file.
     * @param array<string, mixed> $params Variables exposed inside the template.
     * @return string
     */
    protected function phpFileOutput(string $phpFile, array $params = []): string
    {
        foreach ($params as $param => $value) {
            $$param = $value;
        }

        ob_start();
        include $phpFile;
        return ob_get_clean();
    }

    /**
     * Renders a view file from the views directory.
     *
     * @param string $view View name relative to the views directory.
     * @param array<string, mixed> $params Variables exposed to the view template.
     * @return string
     */
    protected function renderView(string $view, array $params = []): string
    {
        return $this->phpFileOutput("{$this->viewsDirectory}/{$view}.php", $params);
    }

    /**
     * Renders the layout file used to wrap view content.
     *
     * @param string $layout Layout name.
     * @return string
     */
    protected function renderLayout(string $layout): string
    {
        return $this->phpFileOutput("{$this->viewsDirectory}/layouts/{$layout}.php");
    }
}
