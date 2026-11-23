<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    DB::connection('mongodb')->getCollection('pages')->deleteMany([]);
    DB::connection('mongodb')->getCollection('page_revisions')->deleteMany([]);
    AuthController::ensureDemoUser();
});

it('lists discovered blocks including phase 1 library', function () {
    $response = $this->getJson('/api/v1/blocks')->assertOk();
    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names)->toContain('columns', 'hero', 'cards', 'slider', 'carousel', 'accordion');
});

it('logs in the demo author and updates page content', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'author@edgephp.local',
        'password' => 'author',
    ])->assertOk();

    $create = $this->postJson('/api/v1/pages', [
        'path' => '/author-home',
        'title' => 'Author Home',
        'sections' => [
            [
                'id' => 'section-1',
                'children' => [],
            ],
        ],
    ])->assertCreated();

    $id = $create->json('data.id');
    $revisionId = $create->json('data.draftRevisionId');

    $this->patchJson('/api/v1/pages/'.$id, [
        'expectedRevisionId' => $revisionId,
        'content' => [
            'metadata' => ['title' => 'Author Home'],
            'sections' => [
                [
                    'id' => 'section-1',
                    'children' => [
                        [
                            'id' => 'block-1',
                            'type' => 'block',
                            'name' => 'columns',
                            'rows' => [
                                [
                                    'cells' => [
                                        ['content' => [['type' => 'paragraph', 'text' => 'A']]],
                                        ['content' => [['type' => 'paragraph', 'text' => 'B']]],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])->assertOk()
        ->assertJsonPath('data.revision.content.sections.0.children.0.name', 'columns');

    $this->get('/__preview/pages/'.$id)
        ->assertOk()
        ->assertSee('class="columns"', false)
        ->assertSee('<p>A</p>', false)
        ->assertSee('<p>B</p>', false);
});
