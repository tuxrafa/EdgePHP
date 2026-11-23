<?php

namespace App\Application\Composition;

use App\Domain\Content\Template;
use App\Domain\Content\TemplateRevision;

final class TemplateResolver
{
    /**
     * @return array{
     *   template: Template,
     *   revision: TemplateRevision,
     *   layout: list<array<string, mixed>>,
     *   policies: array<string, mixed>,
     *   initialContent: array<string, mixed>
     * }
     */
    public function resolve(string $templateId, RenderContext $context, ?string $pinnedRevisionId = null): array
    {
        $template = Template::query()->find($templateId);
        if (! $template) {
            throw new \RuntimeException("Template not found: {$templateId}", 404);
        }

        if ($template->status === 'disabled') {
            throw new \RuntimeException("Template \"{$template->name}\" is disabled.", 422);
        }

        $revision = null;
        if ($pinnedRevisionId) {
            $revision = TemplateRevision::query()->find($pinnedRevisionId);
            if (! $revision || $revision->templateId !== $template->id) {
                throw new \RuntimeException("Pinned template revision not found: {$pinnedRevisionId}", 404);
            }
        } elseif ($context->mode === 'published') {
            if (! $template->publishedRevisionId) {
                throw new \RuntimeException(
                    "Template \"{$template->name}\" has no published revision.",
                    422
                );
            }
            $revision = TemplateRevision::query()->find($template->publishedRevisionId);
        } else {
            $revisionId = $template->draftRevisionId ?: $template->publishedRevisionId;
            $revision = TemplateRevision::query()->find($revisionId);
        }

        if (! $revision) {
            throw new \RuntimeException("Template revision missing for {$templateId}", 404);
        }

        $layout = is_array($revision->layout) ? $revision->layout : [];
        $policies = is_array($revision->policies) ? $revision->policies : [];
        $initialContent = is_array($revision->initialContent) ? $revision->initialContent : [];

        $context->recordDependency('template', $template->id, $revision->id);

        return [
            'template' => $template,
            'revision' => $revision,
            'layout' => $layout,
            'policies' => $policies,
            'initialContent' => $initialContent,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $layout
     * @return list<array<string, mixed>>
     */
    public function slots(array $layout): array
    {
        return array_values(array_filter(
            $layout,
            fn ($node) => is_array($node) && ($node['type'] ?? '') === 'slot'
        ));
    }
}
