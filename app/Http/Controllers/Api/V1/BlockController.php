<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Blocks\BlockRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BlockController extends Controller
{
    public function index(BlockRegistry $registry): JsonResponse
    {
        $blocks = array_map(
            fn ($definition) => $definition->toArray(),
            $registry->scan()
        );

        return response()->json(['data' => $blocks]);
    }

    public function show(string $name, BlockRegistry $registry): JsonResponse
    {
        $definition = $registry->find($name);
        if (! $definition) {
            return response()->json(['message' => 'Block not found.'], 404);
        }

        return response()->json(['data' => $definition->toArray()]);
    }

    public function scan(BlockRegistry $registry): JsonResponse
    {
        return $this->index($registry);
    }
}
