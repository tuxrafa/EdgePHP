<?php

namespace App\Application\Templates;

use App\Application\Composition\ContentDependencyGraph;
use App\Domain\Content\Template;
use App\Domain\Content\TemplateRevision;
use Illuminate\Support\Str;

final class CreateTemplate
{
    public function __construct(
        private readonly ContentDependencyGraph $graph = new ContentDependencyGraph,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array{template: Template, revision: TemplateRevision}
     */
    public function handle(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('name is required.');
        }

        $slug = $this->slugify((string) ($input['slug'] ?? $name));
        $layout = $input['layout'] ?? $this->defaultLayout();
        if (! is_array($layout)) {
            throw new \InvalidArgumentException('layout must be an array.');
        }

        $templateId = (string) Str::ulid();
        $revisionId = (string) Str::ulid();

        $revision = TemplateRevision::query()->create([
            '_id' => $revisionId,
            'templateId' => $templateId,
            'number' => 1,
            'layout' => $layout,
            'initialContent' => is_array($input['initialContent'] ?? null) ? $input['initialContent'] : [],
            'policies' => is_array($input['policies'] ?? null)
                ? $input['policies']
                : ['allowPageSections' => true],
            'authorId' => $input['authorId'] ?? null,
            'message' => $input['message'] ?? 'Initial template',
        ]);

        $template = Template::query()->create([
            '_id' => $templateId,
            'siteId' => (string) ($input['siteId'] ?? 'main'),
            'name' => $name,
            'slug' => $slug,
            'description' => (string) ($input['description'] ?? ''),
            'status' => (string) ($input['status'] ?? 'active'),
            'draftRevisionId' => $revisionId,
            'publishedRevisionId' => null,
            'metadata' => is_array($input['metadata'] ?? null) ? $input['metadata'] : [],
        ]);

        $this->graph->indexTemplateRevision($template, $revision);

        return ['template' => $template, 'revision' => $revision];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaultLayout(): array
    {
        return [
            [
                'type' => 'slot',
                'id' => 'main',
                'region' => 'main',
                'label' => 'Conteúdo principal',
                'editable' => true,
                'required' => true,
                'allowedBlocks' => [],
                'allowSections' => true,
                'allowFragments' => true,
            ],
        ];
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: 'template';

        return trim($value, '-') ?: 'template';
    }
}
