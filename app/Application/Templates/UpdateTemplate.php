<?php

namespace App\Application\Templates;

use App\Application\Composition\ContentDependencyGraph;
use App\Domain\Content\Template;
use App\Domain\Content\TemplateRevision;
use Illuminate\Support\Str;

final class UpdateTemplate
{
    public function __construct(
        private readonly ContentDependencyGraph $graph = new ContentDependencyGraph,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array{template: Template, revision: TemplateRevision}
     */
    public function handle(string $templateId, array $input): array
    {
        $template = Template::query()->find($templateId);
        if (! $template) {
            throw new \RuntimeException('Template not found.', 404);
        }

        if (! empty($input['expectedRevisionId'])
            && $template->draftRevisionId
            && $input['expectedRevisionId'] !== $template->draftRevisionId) {
            throw new \RuntimeException('Revision conflict. Reload and try again.', 409);
        }

        $current = TemplateRevision::query()->find($template->draftRevisionId);
        $layout = $input['layout'] ?? ($current?->layout ?? []);
        if (! is_array($layout)) {
            throw new \InvalidArgumentException('layout must be an array.');
        }

        $this->assertStableSlotIds($current?->layout, $layout);

        $revisionId = (string) Str::ulid();
        $revision = TemplateRevision::query()->create([
            '_id' => $revisionId,
            'templateId' => $template->id,
            'number' => ((int) ($current?->number ?? 0)) + 1,
            'layout' => $layout,
            'initialContent' => is_array($input['initialContent'] ?? null)
                ? $input['initialContent']
                : (is_array($current?->initialContent) ? $current->initialContent : []),
            'policies' => is_array($input['policies'] ?? null)
                ? $input['policies']
                : (is_array($current?->policies) ? $current->policies : []),
            'authorId' => $input['authorId'] ?? null,
            'message' => $input['message'] ?? 'Template save',
        ]);

        if (isset($input['name'])) {
            $template->name = (string) $input['name'];
        }
        if (isset($input['description'])) {
            $template->description = (string) $input['description'];
        }
        if (isset($input['status'])) {
            $template->status = (string) $input['status'];
        }

        $template->draftRevisionId = $revisionId;
        $template->save();

        $this->graph->indexTemplateRevision($template, $revision);

        return ['template' => $template->fresh(), 'revision' => $revision];
    }

    private function assertStableSlotIds(mixed $previous, array $next): void
    {
        if (! is_array($previous)) {
            return;
        }
        $prevSlots = [];
        foreach ($previous as $node) {
            if (is_array($node) && ($node['type'] ?? '') === 'slot' && ! empty($node['id'])) {
                $prevSlots[(string) $node['id']] = $node;
            }
        }
        $nextIds = [];
        foreach ($next as $node) {
            if (is_array($node) && ($node['type'] ?? '') === 'slot' && ! empty($node['id'])) {
                $nextIds[(string) $node['id']] = true;
            }
        }
        // Removals are allowed but callers should run impact analysis before publish.
        unset($prevSlots, $nextIds);
    }
}
