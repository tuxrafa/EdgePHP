<?php

namespace App\Compatibility\Eds;

/**
 * Produces semantic <head> fragments for EDS-compatible pages.
 */
final class EdsMetadataRenderer
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function renderHead(array $metadata, string $fallbackTitle = 'EdgePHP'): string
    {
        $title = (string) ($metadata['title'] ?? $fallbackTitle);
        $title = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $parts = [];
        $parts[] = '<meta charset="UTF-8">';
        $parts[] = '<meta name="viewport" content="width=device-width, initial-scale=1">';
        $parts[] = '<title>'.$title.'</title>';

        if (! empty($metadata['description']) && is_string($metadata['description'])) {
            $parts[] = '<meta name="description" content="'.htmlspecialchars($metadata['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">';
        }

        if (! empty($metadata['robots']) && is_string($metadata['robots'])) {
            $parts[] = '<meta name="robots" content="'.htmlspecialchars($metadata['robots'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">';
        }

        if (! empty($metadata['canonical']) && is_string($metadata['canonical'])) {
            $parts[] = '<link rel="canonical" href="'.htmlspecialchars($metadata['canonical'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">';
        }

        $parts[] = '<link rel="stylesheet" href="/styles/tokens.css">';
        $parts[] = '<link rel="stylesheet" href="/styles/styles.css">';
        $parts[] = '<script type="module" src="/scripts/aem.js"></script>';
        $parts[] = '<script type="module" src="/scripts/scripts.js"></script>';

        return implode("\n    ", $parts);
    }
}
