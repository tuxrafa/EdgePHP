<?php

it('keeps Adobe boilerplate vendor files byte-identical to recorded hashes', function () {
    $manifest = json_decode(
        file_get_contents(base_path('fixtures/eds/vendor-hashes.json')),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    foreach ($manifest['files'] as $relative => $expected) {
        $path = base_path($relative);
        expect(file_exists($path))->toBeTrue("Missing vendor file: {$relative}");
        $actual = hash_file('sha256', $path);
        expect($actual)->toBe(
            $expected,
            "Vendor file modified: {$relative}. Expected {$expected}, got {$actual}."
        );
    }
});
