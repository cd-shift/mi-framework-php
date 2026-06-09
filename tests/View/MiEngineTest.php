<?php

declare(strict_types=1);

namespace Tests\View;

use PHPUnit\Framework\TestCase;
use View\MiEngine;

class MiEngineTest extends TestCase
{
    public function test_renders_template_with_parameters(): void
    {
        $parameter1 = 'TEST 1';
        $parameter2 = 2;

        $expected = "
        <html>
            <body>
                <h1>$parameter1</h1>
                <h1>$parameter2</h1>
            </body>
        </html>
        ";

        $engine = new MiEngine(__DIR__ . '/views');

        $content = $engine->render('test', compact('parameter1', 'parameter2'), 'layout');

        $this->assertEquals(
            preg_replace("/\s*/", '', $expected),
            preg_replace("/\s*/", '', $content)
        );
    }

    public function test_renders_same_template_multiple_times(): void
    {
        $engine = new MiEngine(__DIR__ . '/views');

        $firstRender = $engine->render('test', ['parameter1' => 'FIRST', 'parameter2' => 1], 'layout');
        $secondRender = $engine->render('test', ['parameter1' => 'SECOND', 'parameter2' => 2], 'layout');

        $this->assertStringContainsString('<h1>FIRST</h1>', preg_replace("/\s*/", '', $firstRender));
        $this->assertStringContainsString('<h1>SECOND</h1>', preg_replace("/\s*/", '', $secondRender));
        $this->assertStringContainsString('<body>', $firstRender);
        $this->assertStringContainsString('<body>', $secondRender);
    }
}
