<?php

use App\Application\Composition\ContentComposer;
use App\Application\Composition\RenderContext;
use App\Application\Fragments\CreateFragment;
use App\Application\Fragments\PublishFragment;
use App\Application\Templates\CreateTemplate;
use App\Application\Templates\PublishTemplate;
use App\Application\Content\CreatePage;
use App\Domain\Content\PageRevision;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    foreach ([
        'pages',
        'page_revisions',
        'templates',
        'template_revisions',
        'fragments',
        'fragment_revisions',
        'content_references',
        'published_releases',
    ] as $collection) {
        DB::connection('mongodb')->getCollection($collection)->deleteMany([]);
    }
});

it('composes article template with linked header and page slot content', function () {
    $header = app(CreateFragment::class)->handle([
        'name' => 'Header',
        'slug' => 'header',
        'content' => [
            'metadata' => ['title' => 'Header'],
            'sections' => [[
                'id' => 'h1',
                'type' => 'section',
                'children' => [[
                    'id' => 'hb',
                    'type' => 'block',
                    'name' => 'text',
                    'rows' => [[
                        'cells' => [[
                            'content' => [['type' => 'paragraph', 'text' => 'HEADER']],
                        ]],
                    ]],
                ]],
            ]],
        ],
    ]);
    app(PublishFragment::class)->handle($header['fragment']->id);

    $tpl = app(CreateTemplate::class)->handle([
        'name' => 'Article',
        'slug' => 'article',
        'layout' => [
            [
                'type' => 'fragmentReference',
                'id' => 'header-ref',
                'fragmentId' => $header['fragment']->id,
                'mode' => 'linked',
                'variation' => 'default',
            ],
            [
                'type' => 'slot',
                'id' => 'main',
                'label' => 'Main',
                'required' => true,
            ],
        ],
    ]);
    app(PublishTemplate::class)->handle($tpl['template']->id);

    $page = app(CreatePage::class)->handle([
        'path' => '/post-1',
        'title' => 'Post 1',
        'templateId' => $tpl['template']->id,
        'content' => [
            'metadata' => ['title' => 'Post 1'],
            'templateId' => $tpl['template']->id,
            'slots' => [
                'main' => [
                    'sections' => [[
                        'id' => 'body',
                        'type' => 'section',
                        'children' => [[
                            'id' => 'body-block',
                            'type' => 'block',
                            'name' => 'heading',
                            'rows' => [[
                                'cells' => [[
                                    'content' => [['type' => 'heading', 'level' => 1, 'text' => 'BODY']],
                                ]],
                            ]],
                        ]],
                    ]],
                ],
            ],
        ],
    ]);

    $revision = PageRevision::query()->find($page['page']->draftRevisionId);
    $composer = new ContentComposer;
    $composed = $composer->composePage(
        $page['page'],
        $revision,
        new RenderContext(mode: 'preview', siteId: 'main'),
    );

    $htmlParts = collect($composed['content']['sections'])
        ->flatMap(fn ($s) => $s['children'] ?? [])
        ->pluck('name')
        ->all();

    expect($composed['templateId'])->toBe($tpl['template']->id)
        ->and($htmlParts)->toContain('text', 'heading');

    $texts = json_encode($composed['content']);
    expect($texts)->toContain('HEADER')->toContain('BODY');
});

it('detects fragment cycles', function () {
    $a = app(CreateFragment::class)->handle([
        'name' => 'A',
        'slug' => 'a',
        'content' => ['metadata' => [], 'sections' => []],
    ]);
    $b = app(CreateFragment::class)->handle([
        'name' => 'B',
        'slug' => 'b',
        'content' => [
            'metadata' => [],
            'sections' => [[
                'id' => 's',
                'type' => 'section',
                'children' => [[
                    'type' => 'fragmentReference',
                    'id' => 'to-a',
                    'fragmentId' => $a['fragment']->id,
                    'mode' => 'linked',
                ]],
            ]],
        ],
    ]);

    // Point A at B to create a cycle once resolved from B.
    app(\App\Application\Fragments\UpdateFragment::class)->handle($a['fragment']->id, [
        'content' => [
            'metadata' => [],
            'sections' => [[
                'id' => 's',
                'type' => 'section',
                'children' => [[
                    'type' => 'fragmentReference',
                    'id' => 'to-b',
                    'fragmentId' => $b['fragment']->id,
                    'mode' => 'linked',
                ]],
            ]],
        ],
    ]);

    $tpl = app(CreateTemplate::class)->handle([
        'name' => 'Cyclic',
        'slug' => 'cyclic',
        'layout' => [[
            'type' => 'fragmentReference',
            'id' => 'start',
            'fragmentId' => $b['fragment']->id,
            'mode' => 'linked',
        ]],
    ]);

    $composer = new ContentComposer;
    expect(fn () => $composer->composeTemplatePreview(
        $tpl['template']->id,
        new RenderContext(mode: 'preview'),
    ))->toThrow(RuntimeException::class, 'cycle');
});
