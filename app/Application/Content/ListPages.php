<?php

namespace App\Application\Content;

use App\Domain\Content\Page;
use Illuminate\Support\Collection;

final class ListPages
{
    /**
     * @return Collection<int, Page>
     */
    public function handle(string $siteId = 'main', ?string $locale = null): Collection
    {
        $query = Page::query()->where('siteId', $siteId);
        if ($locale) {
            $query->where('locale', $locale);
        }

        return $query->orderBy('path')->get();
    }
}
