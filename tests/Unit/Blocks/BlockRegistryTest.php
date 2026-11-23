<?php

use App\Application\Blocks\BlockRegistry;

it('discovers the unmodified columns block', function () {
    $registry = new BlockRegistry(base_path('blocks'));
    $columns = $registry->find('columns');

    expect($columns)->not->toBeNull()
        ->and($columns->js)->toBe('/blocks/columns/columns.js')
        ->and($columns->css)->toBe('/blocks/columns/columns.css')
        ->and($columns->compatibility)->toBe('EDS-C1')
        ->and($columns->category)->toBe('layout');
});
