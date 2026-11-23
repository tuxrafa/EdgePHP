<?php

use App\Compatibility\Eds\EdsPageRenderer;
use App\Domain\Content\PageRevision;

it('renders hero markup with image link and heading for EDS decoration', function () {
    $revision = new PageRevision([
        'content' => [
            'metadata' => ['title' => 'Hero page'],
            'sections' => [
                [
                    'children' => [
                        [
                            'type' => 'block',
                            'name' => 'hero',
                            'classes' => ['split'],
                            'rows' => [
                                [
                                    'cells' => [
                                        [
                                            'content' => [
                                                ['type' => 'heading', 'level' => 1, 'text' => 'Olá Hero'],
                                                ['type' => 'link', 'text' => 'CTA', 'href' => '/go', 'variant' => 'primary'],
                                            ],
                                        ],
                                        [
                                            'content' => [
                                                ['type' => 'image', 'src' => 'https://placehold.co/100.png', 'alt' => 'Pic'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $html = (new EdsPageRenderer)->render($revision)->html;

    expect($html)
        ->toContain('class="hero split"')
        ->toContain('<h1>Olá Hero</h1>')
        ->toContain('class="button primary"')
        ->toContain('<picture>')
        ->toContain('href="/styles/tokens.css"');
});
