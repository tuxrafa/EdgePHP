<?php

use App\Domain\Content\Page;
use App\Domain\Content\PageRevision;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    DB::connection('mongodb')->getCollection('pages')->deleteMany([]);
    DB::connection('mongodb')->getCollection('page_revisions')->deleteMany([]);
});

it('creates and retrieves a page with columns content', function () {
    $payload = json_decode(
        file_get_contents(base_path('fixtures/eds/columns/page.json')),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $create = $this->postJson('/api/v1/pages', $payload);
    $create->assertCreated();
    $id = $create->json('data.id');
    expect($id)->not->toBeEmpty();

    $show = $this->getJson('/api/v1/pages/'.$id);
    $show->assertOk()
        ->assertJsonPath('data.path', '/columns-compatibility')
        ->assertJsonPath('data.revision.content.sections.0.children.0.name', 'columns');

    expect(Page::query()->count())->toBe(1)
        ->and(PageRevision::query()->count())->toBe(1);
});

it('rejects nested blocks', function () {
    $response = $this->postJson('/api/v1/pages', [
        'path' => '/bad',
        'sections' => [
            [
                'children' => [
                    [
                        'type' => 'block',
                        'name' => 'columns',
                        'rows' => [
                            [
                                'cells' => [
                                    [
                                        'content' => [
                                            ['type' => 'block', 'name' => 'hero', 'rows' => []],
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

    $response->assertStatus(422);
});
