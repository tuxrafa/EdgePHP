<?php

use App\Domain\Content\Page;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    DB::connection('mongodb')->getCollection('pages')->deleteMany([]);
    DB::connection('mongodb')->getCollection('page_revisions')->deleteMany([]);
});

it('lists virtual folders and nested pages without loading revision content', function () {
    $blog = $this->postJson('/api/v1/pages', [
        'path' => '/blog',
        'title' => 'Blog',
        'sections' => [['children' => [['type' => 'paragraph', 'text' => 'Índice']]]],
    ])->assertCreated();

    $this->postJson('/api/v1/pages', [
        'path' => '/blog/artigo-a',
        'title' => 'Artigo A',
        'sections' => [['children' => [['type' => 'paragraph', 'text' => 'Corpo A']]]],
    ])->assertCreated();

    $this->postJson('/api/v1/pages', [
        'path' => '/contato',
        'title' => 'Contato',
    ])->assertCreated();

    $root = $this->getJson('/api/v1/pages/children?parent=/');
    $root->assertOk()
        ->assertJsonPath('meta.parent', '/')
        ->assertJsonPath('data.0.path', '/blog')
        ->assertJsonPath('data.0.kind', 'page')
        ->assertJsonPath('data.0.hasChildren', true)
        ->assertJsonPath('data.0.pageId', $blog->json('data.id'))
        ->assertJsonPath('data.1.path', '/contato')
        ->assertJsonPath('data.1.kind', 'page');

    expect($root->json('data.0.revision'))->toBeNull();

    $nested = $this->getJson('/api/v1/pages/children?parent=/blog');
    $nested->assertOk()
        ->assertJsonPath('data.0.path', '/blog/artigo-a')
        ->assertJsonPath('data.0.title', 'Artigo A')
        ->assertJsonPath('data.0.kind', 'page');

    $this->getJson('/api/v1/pages/'.$blog->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.path', '/blog')
        ->assertJsonPath('data.revision.content.sections.0.children.0.text', 'Índice');

    expect(Page::query()->count())->toBe(3);
});

it('derives a virtual folder when the parent page does not exist', function () {
    $this->postJson('/api/v1/pages', [
        'path' => '/blog/somente-artigo',
        'title' => 'Somente artigo',
    ])->assertCreated();

    $this->getJson('/api/v1/pages/children')
        ->assertOk()
        ->assertJsonPath('data.0.path', '/blog')
        ->assertJsonPath('data.0.kind', 'folder')
        ->assertJsonPath('data.0.explicit', false)
        ->assertJsonPath('data.0.pageId', null)
        ->assertJsonPath('data.0.hasChildren', true);
});
