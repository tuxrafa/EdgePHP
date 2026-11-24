<?php

namespace App\Application\Content;

/**
 * Derives one level of the site tree from page paths.
 *
 * A path is the only parent/child source. Missing intermediate documents
 * become virtual folders. A document that also has deeper paths stays a
 * single page node with hasChildren=true.
 */
final class PageHierarchy
{
    /**
     * @param  list<array{
     *   id?: string|null,
     *   path: string,
     *   title?: string|null,
     *   status?: string|null,
     *   templateId?: string|null,
     *   updatedAt?: string|null
     * }>  $pages
     * @return list<array{
     *   path: string,
     *   segment: string,
     *   title: string,
     *   kind: string,
     *   explicit: bool,
     *   pageId: string|null,
     *   status: string|null,
     *   templateId: string|null,
     *   hasChildren: bool,
     *   updatedAt: string|null
     * }>
     */
    public function children(array $pages, string $parent, int $limit = 200, int $offset = 0): array
    {
        $offset = max(0, $offset);
        $limit = max(1, $limit);

        return array_slice($this->level($pages, $parent), $offset, $limit);
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return list<array<string, mixed>>
     */
    public function level(array $pages, string $parent): array
    {
        $parent = self::normalizeParent($parent);
        $parentSegments = self::segments($parent);
        $parentDepth = count($parentSegments);
        /** @var array<string, array<string, mixed>> $nodes */
        $nodes = [];

        foreach ($pages as $page) {
            $path = self::normalizeParent((string) ($page['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            $segments = self::segments($path);
            if (count($segments) <= $parentDepth) {
                continue;
            }
            if (array_slice($segments, 0, $parentDepth) !== $parentSegments) {
                continue;
            }

            $childSegments = array_slice($segments, 0, $parentDepth + 1);
            $childPath = '/'.implode('/', $childSegments);
            $segment = $childSegments[$parentDepth];

            if (! isset($nodes[$childPath])) {
                $nodes[$childPath] = [
                    'path' => $childPath,
                    'segment' => $segment,
                    'title' => self::label($segment),
                    'kind' => 'folder',
                    'explicit' => false,
                    'pageId' => null,
                    'status' => null,
                    'templateId' => null,
                    'hasChildren' => false,
                    'updatedAt' => null,
                ];
            }

            if ($path === $childPath) {
                $nodes[$childPath]['kind'] = 'page';
                $nodes[$childPath]['explicit'] = true;
                $nodes[$childPath]['pageId'] = isset($page['id']) ? (string) $page['id'] : null;
                $title = trim((string) ($page['title'] ?? ''));
                if ($title !== '') {
                    $nodes[$childPath]['title'] = $title;
                }
                $nodes[$childPath]['status'] = $page['status'] ?? null;
                $nodes[$childPath]['templateId'] = $page['templateId'] ?? null;
                $nodes[$childPath]['updatedAt'] = $page['updatedAt'] ?? null;
            } else {
                $nodes[$childPath]['hasChildren'] = true;
            }
        }

        $sorted = array_values($nodes);
        usort($sorted, function (array $a, array $b): int {
            $rank = static fn (array $node): int => $node['kind'] === 'folder' ? 0 : 1;
            $byKind = $rank($a) <=> $rank($b);
            if ($byKind !== 0) {
                return $byKind;
            }

            return strcasecmp((string) $a['title'], (string) $b['title']);
        });

        return $sorted;
    }

    public static function normalizeParent(string $parent): string
    {
        $parent = str_replace('\\', '/', trim($parent));
        $parent = '/'.trim($parent, '/');

        return $parent === '/' ? '' : $parent;
    }

    /**
     * @return list<string>
     */
    private static function segments(string $path): array
    {
        if ($path === '') {
            return [];
        }

        return array_values(array_filter(explode('/', $path), static fn (string $part): bool => $part !== ''));
    }

    private static function label(string $segment): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $segment));
    }
}
