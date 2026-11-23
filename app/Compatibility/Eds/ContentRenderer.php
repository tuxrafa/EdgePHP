<?php

namespace App\Compatibility\Eds;

use App\Domain\Content\PageRevision;

interface ContentRenderer
{
    public function render(PageRevision $page): RenderedPage;
}
