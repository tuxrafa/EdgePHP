<?php

namespace App\Application\Fragments;

use App\Application\Composition\ContentDependencyGraph;
use App\Domain\Content\Fragment;
use App\Domain\Content\FragmentRevision;

final class PublishFragment
{
    public function __construct(
        private readonly ContentDependencyGraph $graph = new ContentDependencyGraph,
    ) {}

    /**
     * @return array{fragment: Fragment, revision: FragmentRevision, usage: array<string, mixed>}
     */
    public function handle(string $fragmentId): array
    {
        $fragment = Fragment::query()->find($fragmentId);
        if (! $fragment) {
            throw new \RuntimeException('Fragment not found.', 404);
        }

        $revision = FragmentRevision::query()->find($fragment->draftRevisionId);
        if (! $revision) {
            throw new \RuntimeException('Draft revision missing.', 422);
        }

        $fragment->publishedRevisionId = $revision->id;
        $fragment->status = 'published';
        $fragment->save();

        $this->graph->indexFragmentRevision($fragment, $revision);
        $usage = $this->graph->usageFor('fragment', (string) $fragment->id);

        return [
            'fragment' => $fragment->fresh(),
            'revision' => $revision,
            'usage' => $usage,
        ];
    }
}
