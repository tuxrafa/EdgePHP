<?php

namespace App\Application\Composition;

final class RenderContext
{
    /**
     * @param  list<string>  $stack  Fragment reference path for cycle detection
     * @param  array<string, string>  $resolvedFragments  fragmentId@version => revisionId
     */
    public function __construct(
        public readonly string $mode = 'preview', // preview|published
        public readonly string $siteId = 'main',
        public readonly int $maxDepth = 8,
        public array $stack = [],
        public array $resolvedFragments = [],
        public array $dependencies = [],
    ) {}

    public function withPushed(string $key): self
    {
        $clone = clone $this;
        $clone->stack = [...$this->stack, $key];

        return $clone;
    }

    public function recordDependency(string $type, string $id, ?string $revisionId = null, ?string $variation = null): void
    {
        $this->dependencies[] = [
            'type' => $type,
            'id' => $id,
            'revisionId' => $revisionId,
            'variation' => $variation,
        ];
    }
}
