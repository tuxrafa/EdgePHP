<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Composition\ContentComposer;
use App\Application\Composition\ContentDependencyGraph;
use App\Application\Composition\RenderContext;
use App\Application\Templates\CreateTemplate;
use App\Application\Templates\PublishTemplate;
use App\Application\Templates\UpdateTemplate;
use App\Compatibility\Eds\EdsPageRenderer;
use App\Domain\Content\Template;
use App\Domain\Content\TemplateRevision;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $siteId = (string) $request->query('siteId', 'main');
        $templates = Template::query()
            ->where('siteId', $siteId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $templates->map(fn (Template $t) => $this->serialize($t))->values(),
        ]);
    }

    public function store(Request $request, CreateTemplate $create): JsonResponse
    {
        $validated = $request->validate([
            'siteId' => ['sometimes', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:256'],
            'slug' => ['sometimes', 'string', 'max:256'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'layout' => ['sometimes', 'array'],
            'initialContent' => ['sometimes', 'array'],
            'policies' => ['sometimes', 'array'],
            'status' => ['sometimes', 'string', 'max:32'],
            'message' => ['sometimes', 'nullable', 'string', 'max:512'],
        ]);

        try {
            $result = $create->handle($validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->serialize($result['template'], $result['revision']),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $template = Template::query()->find($id);
        if (! $template) {
            return response()->json(['message' => 'Template not found.'], 404);
        }
        $revision = TemplateRevision::query()->find($template->draftRevisionId);

        return response()->json([
            'data' => $this->serialize($template, $revision),
        ]);
    }

    public function update(string $id, Request $request, UpdateTemplate $update): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:256'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'string', 'max:32'],
            'layout' => ['sometimes', 'array'],
            'initialContent' => ['sometimes', 'array'],
            'policies' => ['sometimes', 'array'],
            'expectedRevisionId' => ['sometimes', 'nullable', 'string'],
            'message' => ['sometimes', 'nullable', 'string', 'max:512'],
        ]);

        try {
            $result = $update->handle($id, $validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }

        return response()->json([
            'data' => $this->serialize($result['template'], $result['revision']),
        ]);
    }

    public function publish(string $id, PublishTemplate $publish): JsonResponse
    {
        try {
            $result = $publish->handle($id);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }

        return response()->json([
            'data' => [
                ...$this->serialize($result['template'], $result['revision']),
                'pages' => $result['pages'],
            ],
        ]);
    }

    public function usage(string $id, ContentDependencyGraph $graph): JsonResponse
    {
        $template = Template::query()->find($id);
        if (! $template) {
            return response()->json(['message' => 'Template not found.'], 404);
        }

        return response()->json([
            'data' => [
                'pages' => $graph->pagesUsingTemplate($id),
            ],
        ]);
    }

    public function preview(
        string $id,
        Request $request,
        ContentComposer $composer,
        EdsPageRenderer $renderer,
    ): Response {
        $context = new RenderContext(
            mode: 'preview',
            siteId: (string) $request->query('siteId', 'main'),
        );

        try {
            $composed = $composer->composeTemplatePreview($id, $context);
        } catch (\RuntimeException $e) {
            abort(response($e->getMessage(), $e->getCode() ?: 404));
        }

        $rendered = $renderer->renderContent($composed['content']);

        return response($rendered->html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex')
            ->header('X-EdgePHP-Preview', 'template');
    }

    private function serialize(Template $template, ?TemplateRevision $revision = null): array
    {
        return [
            'id' => $template->id,
            'siteId' => $template->siteId,
            'name' => $template->name,
            'slug' => $template->slug,
            'description' => $template->description,
            'status' => $template->status,
            'draftRevisionId' => $template->draftRevisionId,
            'publishedRevisionId' => $template->publishedRevisionId,
            'metadata' => $template->metadata,
            'revision' => $revision ? [
                'id' => $revision->id,
                'number' => $revision->number,
                'layout' => $revision->layout,
                'initialContent' => $revision->initialContent,
                'policies' => $revision->policies,
            ] : null,
        ];
    }
}
