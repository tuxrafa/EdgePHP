<?php

namespace App\Compatibility\Eds;

/**
 * Renders default content nodes to semantic HTML for EDS markup.
 */
final class EdsDefaultContentRenderer
{
    /**
     * @param  array<int, mixed>  $nodes
     */
    public function renderNodes(array $nodes): string
    {
        $html = '';
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }
            $html .= $this->renderNode($node);
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    public function renderNode(array $node): string
    {
        $type = (string) ($node['type'] ?? '');

        return match ($type) {
            'paragraph' => '<p>'.$this->escape((string) ($node['text'] ?? '')).'</p>',
            'heading' => $this->renderHeading($node),
            'image' => $this->renderImage($node),
            'link', 'button' => $this->renderLink($node),
            'list' => $this->renderList($node),
            'quote' => $this->renderQuote($node),
            'html' => $this->sanitizeFragment((string) ($node['html'] ?? '')),
            'block' => throw new \InvalidArgumentException('Nested blocks are not allowed.'),
            default => '',
        };
    }

    public function sanitizeFragment(string $html): string
    {
        $html = preg_replace('#<(script|style|object|embed|iframe|link|meta)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('#<(script|style|object|embed|iframe|link|meta)\b[^>]*/?>#is', '', $html) ?? '';
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/\shref\s*=\s*([\'"])\s*javascript:[^\'"]*\1/i', ' href="#"', $html) ?? '';
        $html = preg_replace('/\ssrc\s*=\s*([\'"])\s*javascript:[^\'"]*\1/i', '', $html) ?? '';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderHeading(array $node): string
    {
        $level = (int) ($node['level'] ?? 1);
        $level = max(1, min(6, $level));
        $tag = 'h'.$level;
        $id = '';
        if (! empty($node['id']) && is_string($node['id'])) {
            $safe = preg_replace('/[^A-Za-z0-9_:-]/', '', $node['id']) ?? '';
            if ($safe !== '') {
                $id = ' id="'.$this->escape($safe).'"';
            }
        }

        return '<'.$tag.$id.'>'.$this->escape((string) ($node['text'] ?? '')).'</'.$tag.'>';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderImage(array $node): string
    {
        $src = $this->safeUrl((string) ($node['src'] ?? ''));
        if ($src === '') {
            return '';
        }
        $alt = $this->escape((string) ($node['alt'] ?? ''));
        $loading = ! empty($node['eager']) ? 'eager' : 'lazy';
        $img = '<picture><img src="'.$this->escape($src).'" alt="'.$alt.'" loading="'.$loading.'"></picture>';
        if (! empty($node['caption']) && is_string($node['caption'])) {
            $img .= '<p><em>'.$this->escape($node['caption']).'</em></p>';
        }
        if (! empty($node['href']) && is_string($node['href'])) {
            $href = $this->safeUrl($node['href']);
            if ($href !== '') {
                return '<p><a href="'.$this->escape($href).'">'.$img.'</a></p>';
            }
        }

        return $img;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderLink(array $node): string
    {
        $href = $this->safeUrl((string) ($node['href'] ?? $node['url'] ?? '#'));
        $text = $this->escape((string) ($node['text'] ?? $node['label'] ?? 'Learn more'));
        $classes = ['button'];
        $variant = (string) ($node['variant'] ?? 'primary');
        if (in_array($variant, ['primary', 'secondary', 'outline', 'text', 'accent'], true)) {
            $classes[] = $variant === 'outline' ? 'secondary' : $variant;
        }
        $target = ! empty($node['newTab']) ? ' target="_blank" rel="noopener noreferrer"' : '';
        $classAttr = $this->escape(implode(' ', $classes));

        return '<p class="button-wrapper"><a class="'.$classAttr.'" href="'.$this->escape($href).'"'.$target.'>'.$text.'</a></p>';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderList(array $node): string
    {
        $ordered = ! empty($node['ordered']);
        $tag = $ordered ? 'ol' : 'ul';
        $items = is_array($node['items'] ?? null) ? $node['items'] : [];
        $lis = '';
        foreach ($items as $item) {
            $text = is_array($item) ? (string) ($item['text'] ?? '') : (string) $item;
            $lis .= '<li>'.$this->escape($text).'</li>';
        }

        return '<'.$tag.'>'.$lis.'</'.$tag.'>';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderQuote(array $node): string
    {
        $text = $this->escape((string) ($node['text'] ?? ''));
        $cite = '';
        if (! empty($node['author']) && is_string($node['author'])) {
            $cite = '<cite>'.$this->escape($node['author']).'</cite>';
        }

        return '<blockquote><p>'.$text.'</p>'.$cite.'</blockquote>';
    }

    private function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^(javascript|data|vbscript):#i', $url)) {
            return '';
        }

        return $url;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
