<?php

use App\Application\Blocks\BlockRegistry;

it('loads author manifests with categories and initial structure', function () {
    $hero = (new BlockRegistry)->find('hero');
    expect($hero)->not->toBeNull()
        ->and($hero->category)->toBe('presentation')
        ->and($hero->initialStructure)->toBeArray()
        ->and($hero->initialStructure['name'] ?? null)->toBe('hero');
});

it('resolves carousel alias carrousel', function () {
    $block = (new BlockRegistry)->find('carrousel');
    expect($block)->not->toBeNull()
        ->and($block->name)->toBe('carousel');
});
