<?php

use App\Compatibility\Eds\EdsBlockRenderer;

it('renders EDS columns input markup with nested row and cell divs', function () {
    $renderer = new EdsBlockRenderer;

    $html = $renderer->render([
        'name' => 'columns',
        'rows' => [
            [
                'cells' => [
                    ['content' => [['type' => 'paragraph', 'text' => 'Left']]],
                    ['content' => [['type' => 'paragraph', 'text' => 'Right']]],
                ],
            ],
        ],
    ]);

    expect($html)->toBe(
        '<div class="columns"><div><div><p>Left</p></div><div><p>Right</p></div></div></div>'
    );
});

it('escapes default content text', function () {
    $renderer = new EdsBlockRenderer;

    $html = $renderer->render([
        'name' => 'columns',
        'rows' => [
            [
                'cells' => [
                    ['content' => [['type' => 'paragraph', 'text' => '<script>alert(1)</script>']]],
                ],
            ],
        ],
    ]);

    expect($html)->not->toContain('<script>')
        ->and($html)->toContain('&lt;script&gt;');
});

it('rejects empty block names', function () {
    $renderer = new EdsBlockRenderer;
    $renderer->render(['name' => '', 'rows' => []]);
})->throws(InvalidArgumentException::class);
