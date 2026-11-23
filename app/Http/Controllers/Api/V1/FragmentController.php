<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Composition\ContentDependencyGraph;
use App\Application\Fragments\CreateFragment;
use App\Application\Fragments\PublishFragment;
use App\Application\Fragments\UpdateFragment;
use App\Compatibility\Eds\EdsPageRenderer;
use App\Domain\Content\Fragment;
use App\Domain\Content\FragmentRevision;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FragmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $siteId = (string) $request->query('siteId', 'main');
        $fragments = Fragment::query()
            ->where('siteId', $siteId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $fragments->map(fn (Fragment $f) => $this->serialize($f))->values(),
        ]);
    }

    public function store(Request $request, CreateFragment $create): JsonResponse
    {
        $validated = $request->validate([
            'siteId' => ['sometimes', 'string', 'max:64'],
            'locale' => ['sometimes', 'string', 'max:16'],
            'name' => ['required', 'string', 'max:256'],
            'slug' => ['sometimes', 'string', 'max:256'],
            'category' => ['sometimes', 'string', 'max:128'],
            'tags' => ['sometimes', 'array'],
            'defaultVariation' => ['sometimes', 'string', 'max:64'],
            'content' => ['sometimes', 'array'],
            'sections' => ['sometimes', 'array'],
            'message' => ['sometimes', 'nullable', 'string', 'max:512'],
        ]);

        try {
            $result = $create->handle($validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->serialize($result['fragment'], $result['revision']),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $fragment = Fragment::query()->find($id);
        if (! $fragment) {
            return response()->json(['message' => 'Fragment not found.'], 404);
        }
        $revision = FragmentRevision::query()->find($fragment->draftRevisionId);

        return response()->json([
            'data' => $this->serialize($fragment, $revision),
        ]);
    }

    public function update(string $id, Request $request, UpdateFragment $update): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:256'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'category' => ['sometimes', 'string', 'max:128'],
            'tags' => ['sometimes', 'array'],
            'variation' => ['sometimes', 'string', 'max:64'],
            'content' => ['sometimes', 'array'],
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
            'data' => $this->serialize($result['fragment'], $result['revision']),
        ]);
    }

    public function publish(string $id, PublishFragment $publish): JsonResponse
    {
        try {
            $result = $publish->handle($id);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }

        return response()->json([
            'data' => [
                ...$this->serialize($result['fragment'], $result['revision']),
                'usage' => $result['usage'],
            ],
        ]);
    }

    public function usage(string $id, ContentDependencyGraph $graph): JsonResponse
    {
        $fragment = Fragment::query()->find($id);
        if (! $fragment) {
            return response()->json(['message' => 'Fragment not found.'], 404);
        }

        return response()->json([
            'data' => $graph->usageFor('fragment', $id),
        ]);
    }

    public function preview(string $id, EdsPageRenderer $renderer): Response
    {
        $fragment = Fragment::query()->find($id);
        if (! $fragment) {
            abort(404);
        }
        $revision = FragmentRevision::query()->find($fragment->draftRevisionId);
        if (! $revision) {
            abort(404);
        }

        $content = is_array($revision->content) ? $revision->content : [];
        $rendered = $renderer->renderContent($content);

        return response($rendered->html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex')
            ->header('X-EdgePHP-Preview', 'fragment');
    }

    private function serialize(Fragment $fragment, ?FragmentRevision $revision = null): array
    {
        return [
            'id' => $fragment->id,
            'siteId' => $fragment->siteId,
            'locale' => $fragment->locale,
            'name' => $fragment->name,
            'slug' => $fragment->slug,
            'type' => $fragment->type,
            'status' => $fragment->status,
            'category' => $fragment->category,
            'tags' => $fragment->tags,
            'defaultVariation' => $fragment->defaultVariation,
            'draftRevisionId' => $fragment->draftRevisionId,
            'publishedRevisionId' => $fragment->publishedRevisionId,
            'metadata' => $fragment->metadata,
            'revision' => $revision ? [
                'id' => $revision->id,
                'number' => $revision->number,
                'variation' => $revision->variation,
                'content' => $revision->content,
            ] : null,
        ];
    }
}
