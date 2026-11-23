<?php

namespace App\Application\Fragments;

use App\Application\Composition\ContentDependencyGraph;
use App\Domain\Content\Fragment;
use App\Domain\Content\FragmentRevision;
use Illuminate\Support\Str;

final class CreateFragment
{
    public function __construct(
        private readonly ContentDependencyGraph $graph = new ContentDependencyGraph,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array{fragment: Fragment, revision: FragmentRevision}
     */
    public function handle(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('name is required.');
        }

        $slug = $this->slugify((string) ($input['slug'] ?? $name));
        $variation = (string) ($input['defaultVariation'] ?? 'default');
        $content = $input['content'] ?? [
            'metadata' => ['title' => $name],
            'sections' => $input['sections'] ?? [
                [
                    'id' => 'section-'.Str::lower(Str::random(6)),
                    'type' => 'section',
                    'children' => [],
                ],
            ],
        ];
        if (! is_array($content)) {
            throw new \InvalidArgumentException('content must be an object.');
        }

        $fragmentId = (string) Str::ulid();
        $revisionId = (string) Str::ulid();

        $revision = FragmentRevision::query()->create([
            '_id' => $revisionId,
            'fragmentId' => $fragmentId,
            'variation' => $variation,
            'number' => 1,
            'content' => $content,
            'authorId' => $input['authorId'] ?? null,
            'message' => $input['message'] ?? 'Initial fragment',
        ]);

        $fragment = Fragment::query()->create([
            '_id' => $fragmentId,
            'siteId' => (string) ($input['siteId'] ?? 'main'),
            'locale' => (string) ($input['locale'] ?? 'pt-BR'),
            'name' => $name,
            'slug' => $slug,
            'type' => (string) ($input['type'] ?? 'experienceFragment'),
            'status' => (string) ($input['status'] ?? 'draft'),
            'category' => (string) ($input['category'] ?? 'general'),
            'tags' => is_array($input['tags'] ?? null) ? $input['tags'] : [],
            'defaultVariation' => $variation,
            'draftRevisionId' => $revisionId,
            'publishedRevisionId' => null,
            'metadata' => is_array($input['metadata'] ?? null) ? $input['metadata'] : [],
        ]);

        $this->graph->indexFragmentRevision($fragment, $revision);

        return ['fragment' => $fragment, 'revision' => $revision];
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: 'fragment';

        return trim($value, '-') ?: 'fragment';
    }
}
