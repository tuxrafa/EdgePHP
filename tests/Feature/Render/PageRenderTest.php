<?php

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    DB::connection('mongodb')->getCollection('pages')->deleteMany([]);
    DB::connection('mongodb')->getCollection('page_revisions')->deleteMany([]);
});

it('renders preview html with columns content before decoration', function () {
    $payload = json_decode(
        file_get_contents(base_path('fixtures/eds/columns/page.json')),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $create = $this->postJson('/api/v1/pages', $payload)->assertCreated();
    $id = $create->json('data.id');

    $preview = $this->get('/__preview/pages/'.$id);
    $preview->assertOk();
    $html = $preview->getContent();

    expect($html)
        ->toContain('<p>Left</p>')
        ->toContain('<p>Right</p>')
        ->toContain('class="columns"')
        ->toContain('/scripts/aem.js')
        ->toContain('/scripts/scripts.js');
});

it('serves the public path with the same renderer markup', function () {
    $payload = json_decode(
        file_get_contents(base_path('fixtures/eds/columns/page.json')),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $this->postJson('/api/v1/pages', $payload)->assertCreated();

    $page = $this->get('/columns-compatibility');
    $page->assertOk();
    expect($page->getContent())->toContain('class="columns"');
});

it('serves original columns assets', function () {
    $this->get('/blocks/columns/columns.js')->assertOk();
    $this->get('/blocks/columns/columns.css')->assertOk();
    $this->get('/scripts/aem.js')->assertOk();
    $this->get('/scripts/scripts.js')->assertOk();
});
