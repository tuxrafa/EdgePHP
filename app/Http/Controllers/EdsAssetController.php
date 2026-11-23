<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves EDS code-bus assets from the repository root trees.
 * Keeps /blocks, /scripts, /styles available under artisan serve and HTTP tests.
 */
class EdsAssetController extends Controller
{
    private const ALLOWED_ROOTS = ['blocks', 'scripts', 'styles', 'icons'];

    public function show(string $root, string $path): BinaryFileResponse
    {
        if (! in_array($root, self::ALLOWED_ROOTS, true)) {
            abort(404);
        }

        $path = str_replace('\\', '/', $path);
        if (str_contains($path, '..')) {
            abort(404);
        }

        $full = base_path($root.'/'.$path);
        $realBase = realpath(base_path($root));
        $realFile = realpath($full);

        if ($realBase === false || $realFile === false || ! str_starts_with($realFile, $realBase) || ! is_file($realFile)) {
            abort(404);
        }

        return response()->file($realFile, [
            'Cache-Control' => 'public, max-age=60',
        ]);
    }
}
