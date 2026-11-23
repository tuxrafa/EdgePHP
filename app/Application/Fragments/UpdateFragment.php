<?php

namespace App\Application\Fragments;

use App\Application\Composition\ContentDependencyGraph;
use App\Domain\Content\Fragment;
use App\Domain\Content\FragmentRevision;
use Illuminate\Support\Str;

final class UpdateFragment
{
    public function __construct(
        private readonly ContentDependencyGraph $graph = new ContentDependencyGraph,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array{fragment: Fragment, revision: FragmentRevision}
     */
    public function handle(string $fragmentId, array $input): array
    {
        $fragment = Fragment::query()->find($fragmentId);
        if (! $fragment) {
            throw new \RuntimeException('Fragment not found.', 404);
        }

        if (! empty($input['expectedRevisionId'])
            && $fragment->draftRevisionId
            && $input['expectedRevisionId'] !== $fragment->draftRevisionId) {
            throw new \RuntimeException('Revision conflict. Reload and try again.', 409);
        }

        $current = FragmentRevision::query()->find($fragment->draftRevisionId);
        $content = $input['content'] ?? ($current?->content ?? ['metadata' => [], 'sections' => []]);
        if (! is_array($content)) {
            throw new \InvalidArgumentException('content must be an object.');
        }

        $variation = (string) ($input['variation'] ?? $current?->variation ?? $fragment->defaultVariation ?? 'default');
        $revisionId = (string) Str::ulid();

        $revision = FragmentRevision::query()->create([
            '_id' => $revisionId,
            'fragmentId' => $fragment->id,
            'variation' => $variation,
            'number' => ((int) ($current?->number ?? 0)) + 1,
            'content' => $content,
            'authorId' => $input['authorId'] ?? null,
            'message' => $input['message'] ?? 'Fragment save',
        ]);

        if (isset($input['name'])) {
            $fragment->name = (string) $input['name'];
        }
        if (isset($input['description'])) {
            $meta = is_array($fragment->metadata) ? $fragment->metadata : [];
            $meta['description'] = (string) $input['description'];
            $fragment->metadata = $meta;
        }
        if (isset($input['category'])) {
            $fragment->category = (string) $input['category'];
        }
        if (isset($input['tags']) && is_array($input['tags'])) {
            $fragment->tags = $input['tags'];
        }

        $fragment->draftRevisionId = $revisionId;
        $fragment->status = $fragment->publishedRevisionId ? 'published' : 'draft';
        $fragment->save();

        $this->graph->indexFragmentRevision($fragment, $revision);

        return ['fragment' => $fragment->fresh(), 'revision' => $revision];
    }
}
