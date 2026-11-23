<?php

namespace App\Compatibility\Eds;

final class RenderedPage
{
    public function __construct(
        public readonly string $html,
        public readonly string $title,
        public readonly array $metadata = [],
    ) {}
}
