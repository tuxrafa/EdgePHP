<?php

namespace App\Application\Composition;

use App\Domain\Content\Fragment;
use App\Domain\Content\FragmentRevision;

final class FragmentResolver
{
    /**
     * @return array{
     *   fragment: Fragment,
     *   revision: FragmentRevision,
     *   variation: string,
     *   sections: list<array<string, mixed>>,
     *   content: array<string, mixed>
     * }
     */
    public function resolve(
        string $fragmentId,
        string $variation,
        RenderContext $context,
        ?string $pinnedRevisionId = null,
    ): array {
        $key = $fragmentId.'@'.$variation.($pinnedRevisionId ? '#'.$pinnedRevisionId : '');
        if (in_array($key, $context->stack, true)) {
            throw new \RuntimeException(
                'Fragment cycle detected: '.implode(' → ', [...$context->stack, $key])
            );
        }

        if (count($context->stack) >= $context->maxDepth) {
            throw new \RuntimeException(
                'Fragment expansion depth exceeded (max '.$context->maxDepth.'). Path: '.implode(' → ', $context->stack)
            );
        }

        $fragment = Fragment::query()->find($fragmentId);
        if (! $fragment) {
            throw new \RuntimeException("Fragment not found: {$fragmentId}", 404);
        }

        if ($fragment->siteId && $fragment->siteId !== $context->siteId) {
            throw new \RuntimeException("Fragment {$fragmentId} belongs to another site.", 403);
        }

        $variation = $variation !== '' ? $variation : (string) ($fragment->defaultVariation ?: 'default');

        $revision = null;
        if ($pinnedRevisionId) {
            $revision = FragmentRevision::query()->find($pinnedRevisionId);
            if (! $revision || $revision->fragmentId !== $fragment->id) {
                throw new \RuntimeException("Pinned fragment revision not found: {$pinnedRevisionId}", 404);
            }
        } elseif ($context->mode === 'published') {
            if (! $fragment->publishedRevisionId) {
                throw new \RuntimeException(
                    "Fragment \"{$fragment->name}\" has no published revision.",
                    422
                );
            }
            $revision = FragmentRevision::query()->find($fragment->publishedRevisionId);
        } else {
            $revisionId = $fragment->draftRevisionId ?: $fragment->publishedRevisionId;
            $revision = FragmentRevision::query()->find($revisionId);
        }

        if (! $revision) {
            throw new \RuntimeException("Fragment revision missing for {$fragmentId}", 404);
        }

        $content = is_array($revision->content) ? $revision->content : [];
        $sections = is_array($content['sections'] ?? null) ? $content['sections'] : [];

        $context->recordDependency('fragment', $fragment->id, $revision->id, $variation);
        $context->resolvedFragments[$key] = (string) $revision->id;

        return [
            'fragment' => $fragment,
            'revision' => $revision,
            'variation' => $variation,
            'sections' => $sections,
            'content' => $content,
        ];
    }
}
