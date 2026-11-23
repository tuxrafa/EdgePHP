<?php

namespace App\Compatibility\Eds;

use App\Domain\Content\PageRevision;

/**
 * Deterministic page renderer shared by preview and production.
 */
final class EdsPageRenderer implements ContentRenderer
{
    public function __construct(
        private readonly EdsSectionRenderer $sectionRenderer = new EdsSectionRenderer,
        private readonly EdsMetadataRenderer $metadataRenderer = new EdsMetadataRenderer,
    ) {}

    public function render(PageRevision $page): RenderedPage
    {
        $content = $page->content ?? [];
        if (! is_array($content)) {
            $content = [];
        }

        return $this->renderContent($content);
    }

    /**
     * @param  array<string, mixed>  $content  Resolved document ({metadata, sections})
     */
    public function renderContent(array $content): RenderedPage
    {
        $metadata = is_array($content['metadata'] ?? null) ? $content['metadata'] : [];
        $title = (string) ($metadata['title'] ?? 'EdgePHP');
        $sections = is_array($content['sections'] ?? null) ? $content['sections'] : [];

        $mainInner = '';
        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }
            $mainInner .= $this->sectionRenderer->render($section);
        }

        $head = $this->metadataRenderer->renderHead($metadata, $title);
        $lang = htmlspecialchars((string) ($metadata['locale'] ?? 'en'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
  <head>
    {$head}
  </head>
  <body>
    <main>
      {$mainInner}
    </main>
  </body>
</html>
HTML;

        return new RenderedPage(
            html: $html,
            title: $title,
            metadata: $metadata,
        );
    }
}
