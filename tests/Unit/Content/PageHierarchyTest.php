<?php

use App\Application\Content\PageHierarchy;

it('builds a virtual blog folder from nested paths', function () {
    $tree = new PageHierarchy;

    $children = $tree->children([
        ['id' => 'a', 'path' => '/blog/17-artistas-disputarao-o-supernova-2027', 'title' => 'Supernova 2027', 'status' => 'published'],
        ['id' => 'b', 'path' => '/blog/albania-revela-primeiros-detalhes', 'title' => 'Albânia', 'status' => 'published'],
        ['id' => 'c', 'path' => '/home', 'title' => 'Home', 'status' => 'draft'],
    ], '/');

    expect($children)->toHaveCount(2)
        ->and($children[0]['path'])->toBe('/blog')
        ->and($children[0]['kind'])->toBe('folder')
        ->and($children[0]['explicit'])->toBeFalse()
        ->and($children[0]['pageId'])->toBeNull()
        ->and($children[0]['hasChildren'])->toBeTrue()
        ->and($children[1]['path'])->toBe('/home')
        ->and($children[1]['kind'])->toBe('page')
        ->and($children[1]['title'])->toBe('Home');

    $articles = $tree->children([
        ['id' => 'a', 'path' => '/blog/17-artistas-disputarao-o-supernova-2027', 'title' => 'Supernova 2027', 'status' => 'published'],
        ['id' => 'b', 'path' => '/blog/albania-revela-primeiros-detalhes', 'title' => 'Albânia', 'status' => 'published'],
    ], '/blog');

    expect($articles)->toHaveCount(2)
        ->and(collect($articles)->pluck('title')->all())->toBe(['Albânia', 'Supernova 2027'])
        ->and($articles[0]['kind'])->toBe('page')
        ->and($articles[0]['hasChildren'])->toBeFalse();
});

it('keeps a page and its children as one node', function () {
    $children = (new PageHierarchy)->children([
        ['id' => 'blog', 'path' => '/blog', 'title' => 'Blog', 'status' => 'published'],
        ['id' => 'a', 'path' => '/blog/artigo-a', 'title' => 'Artigo A', 'status' => 'draft'],
    ], '/');

    expect($children)->toHaveCount(1)
        ->and($children[0]['kind'])->toBe('page')
        ->and($children[0]['pageId'])->toBe('blog')
        ->and($children[0]['explicit'])->toBeTrue()
        ->and($children[0]['hasChildren'])->toBeTrue()
        ->and($children[0]['title'])->toBe('Blog');
});

it('does not rewrite stored paths', function () {
    $pages = [
        ['id' => 'a', 'path' => '/blog/artigo-a', 'title' => 'Artigo A', 'status' => 'published'],
    ];
    $snapshot = $pages;

    (new PageHierarchy)->children($pages, '/');

    expect($pages)->toBe($snapshot);
});
