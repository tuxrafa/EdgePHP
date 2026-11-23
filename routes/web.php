<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FragmentController;
use App\Http\Controllers\Api\V1\TemplateController;
use App\Http\Controllers\EdsAssetController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/author/');
});

Route::get('/author/{any?}', function (?string $any = null) {
    AuthController::ensureDemoUser();

    if ($any) {
        $candidate = public_path('author/'.$any);
        $realBase = realpath(public_path('author'));
        $realFile = realpath($candidate);
        if (
            $realBase
            && $realFile
            && str_starts_with($realFile, $realBase)
            && is_file($realFile)
        ) {
            return response()->file($realFile);
        }
    }

    $index = public_path('author/index.html');
    if (! is_file($index)) {
        return response(
            'Author UI not built. Run: npm run build:author',
            503,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }

    return response()->file($index, ['Content-Type' => 'text/html; charset=UTF-8']);
})->where('any', '.*');

Route::get('/__preview/pages/{id}', [PreviewController::class, 'show'])
    ->where('id', '[0-9A-HJKMNP-TV-Z]{26}|[A-Za-z0-9]+');
Route::get('/__preview/templates/{id}', [TemplateController::class, 'preview'])
    ->where('id', '[0-9A-HJKMNP-TV-Z]{26}|[A-Za-z0-9]+');
Route::get('/__preview/fragments/{id}', [FragmentController::class, 'preview'])
    ->where('id', '[0-9A-HJKMNP-TV-Z]{26}|[A-Za-z0-9]+');

foreach (['blocks', 'scripts', 'styles', 'icons'] as $root) {
    Route::get('/'.$root.'/{path}', function (string $path) use ($root) {
        return app(EdsAssetController::class)->show($root, $path);
    })->where('path', '.*');
}

Route::get('/{path}', [PublicPageController::class, 'show'])
    ->where('path', '^(?!api(?:/|$)|__preview(?:/|$)|author(?:/|$)|blocks(?:/|$)|scripts(?:/|$)|styles(?:/|$)|icons(?:/|$)|up$).+');
