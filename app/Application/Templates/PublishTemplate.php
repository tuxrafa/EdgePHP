<?php

namespace App\Application\Templates;

use App\Application\Composition\ContentDependencyGraph;
use App\Domain\Content\Template;
use App\Domain\Content\TemplateRevision;

final class PublishTemplate
{
    public function __construct(
        private readonly ContentDependencyGraph $graph = new ContentDependencyGraph,
    ) {}

    /**
     * @return array{template: Template, revision: TemplateRevision, pages: list<array<string, mixed>>}
     */
    public function handle(string $templateId): array
    {
        $template = Template::query()->find($templateId);
        if (! $template) {
            throw new \RuntimeException('Template not found.', 404);
        }

        $revision = TemplateRevision::query()->find($template->draftRevisionId);
        if (! $revision) {
            throw new \RuntimeException('Draft revision missing.', 422);
        }

        $template->publishedRevisionId = $revision->id;
        $template->status = $template->status === 'disabled' ? 'disabled' : 'active';
        $template->save();

        $this->graph->indexTemplateRevision($template, $revision);

        return [
            'template' => $template->fresh(),
            'revision' => $revision,
            'pages' => $this->graph->pagesUsingTemplate((string) $template->id),
        ];
    }
}
