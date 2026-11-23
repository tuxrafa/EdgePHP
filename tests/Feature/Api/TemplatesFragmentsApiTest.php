<?php

use App\Http\Controllers\Api\V1\AuthController;
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
    AuthController::ensureDemoUser();
});

it('creates publishes template and page then previews composed html', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'author@edgephp.local',
        'password' => 'author',
    ])->assertOk();

    $fragment = $this->postJson('/api/v1/fragments', [
        'name' => 'Header Global',
        'slug' => 'header-global',
        'content' => [
            'metadata' => ['title' => 'Header'],
            'sections' => [[
                'id' => 'hs',
                'type' => 'section',
                'children' => [[
                    'id' => 'hb',
                    'type' => 'block',
                    'name' => 'text',
                    'rows' => [[
                        'cells' => [[
                            'content' => [['type' => 'paragraph', 'text' => 'SHARED-HEADER']],
                        ]],
                    ]],
                ]],
            ]],
        ],
    ])->assertCreated();

    $fragmentId = $fragment->json('data.id');
    $this->postJson('/api/v1/fragments/'.$fragmentId.'/publish')->assertOk();

    $template = $this->postJson('/api/v1/templates', [
        'name' => 'Article',
        'slug' => 'article',
        'layout' => [
            [
                'type' => 'fragmentReference',
                'id' => 'header-ref',
                'fragmentId' => $fragmentId,
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
    ])->assertCreated();

    $templateId = $template->json('data.id');
    $this->postJson('/api/v1/templates/'.$templateId.'/publish')->assertOk();

    $page = $this->postJson('/api/v1/pages', [
        'title' => 'My Article',
        'path' => '/blog/my-article',
        'templateId' => $templateId,
    ])->assertCreated();

    expect($page->json('data.templateId'))->toBe($templateId);
    expect($page->json('data.revision.content.slots'))->toHaveKey('main');

    $pageId = $page->json('data.id');
    $this->get('/__preview/pages/'.$pageId)
        ->assertOk()
        ->assertSee('SHARED-HEADER', false)
        ->assertHeader('X-EdgePHP-Composition', 'template');

    $this->postJson('/api/v1/pages/'.$pageId.'/publish')
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    $this->get('/blog/my-article')
        ->assertOk()
        ->assertSee('SHARED-HEADER', false);
});
