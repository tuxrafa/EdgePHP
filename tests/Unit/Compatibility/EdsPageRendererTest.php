<?php

use App\Compatibility\Eds\EdsPageRenderer;
use App\Domain\Content\PageRevision;

it('renders a full page with aem.js and scripts.js and columns markup', function () {
    $revision = new PageRevision([
        '_id' => '01TESTREVISION000000000000',
        'pageId' => '01TESTPAGE0000000000000000',
        'number' => 1,
        'content' => json_decode(
            file_get_contents(base_path('fixtures/eds/columns/page.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        ),
    ]);

    // Fixture root is create-page payload; normalize to revision content shape.
    $payload = $revision->content;
    $revision->content = [
        'metadata' => $payload['metadata'],
        'sections' => $payload['sections'],
    ];

    $rendered = (new EdsPageRenderer)->render($revision);

    expect($rendered->html)
        ->toContain('Columns compatibility')
        ->toContain('src="/scripts/aem.js"')
        ->toContain('src="/scripts/scripts.js"')
        ->toContain('href="/styles/styles.css"')
        ->toContain('<div class="columns">')
        ->toContain('<p>Left</p>')
        ->toContain('<p>Right</p>')
        ->toContain('<main>');
});
