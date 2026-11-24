<?php

namespace App\Application\Content;

use Illuminate\Support\Facades\DB;
use MongoDB\BSON\UTCDateTime;

final class ListPageChildren
{
    private static bool $indexed = false;

    public function __construct(private readonly PageHierarchy $hierarchy = new PageHierarchy) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function handle(
        string $siteId,
        ?string $locale,
        string $parent,
        int $limit = 200,
        int $offset = 0,
    ): array {
        $this->ensureIndex();

        $parent = PageHierarchy::normalizeParent($parent);
        $limit = min(500, max(1, $limit));
        $offset = max(0, $offset);
        $pattern = $parent === ''
            ? '^/[^/]+'
            : '^'.preg_quote($parent, '/').'/[^/]+';

        $filter = [
            'siteId' => $siteId,
            'path' => ['$regex' => $pattern],
        ];
        if ($locale !== null && $locale !== '') {
            $filter['locale'] = $locale;
        }

        $cursor = DB::connection('mongodb')->getCollection('pages')->find(
            $filter,
            [
                'projection' => [
                    'path' => 1,
                    'title' => 1,
                    'status' => 1,
                    'templateId' => 1,
                    'updated_at' => 1,
                ],
            ],
        );

        $rows = [];
        foreach ($cursor as $doc) {
            $rows[] = [
                'id' => isset($doc['_id']) ? (string) $doc['_id'] : null,
                'path' => (string) ($doc['path'] ?? ''),
                'title' => isset($doc['title']) ? (string) $doc['title'] : null,
                'status' => isset($doc['status']) ? (string) $doc['status'] : null,
                'templateId' => isset($doc['templateId']) ? (string) $doc['templateId'] : null,
                'updatedAt' => self::iso($doc['updated_at'] ?? null),
            ];
        }

        $level = $this->hierarchy->level($rows, $parent);
        $total = count($level);

        return [
            'data' => array_slice($level, $offset, $limit),
            'meta' => [
                'parent' => $parent === '' ? '/' : $parent,
                'siteId' => $siteId,
                'locale' => $locale,
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
                'hasMore' => ($offset + $limit) < $total,
            ],
        ];
    }

    private function ensureIndex(): void
    {
        if (self::$indexed) {
            return;
        }

        DB::connection('mongodb')->getCollection('pages')->createIndex(
            ['siteId' => 1, 'locale' => 1, 'path' => 1],
            ['name' => 'pages_site_locale_path'],
        );
        self::$indexed = true;
    }

    private static function iso(mixed $value): ?string
    {
        if ($value instanceof UTCDateTime) {
            return $value->toDateTime()->format(DATE_ATOM);
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        return null;
    }
}
