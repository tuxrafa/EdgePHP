<?php

namespace App\Console\Commands;

use App\Application\Fragments\CreateFragment;
use App\Application\Fragments\PublishFragment;
use App\Application\Templates\CreateTemplate;
use App\Application\Templates\PublishTemplate;
use App\Domain\Content\Fragment;
use App\Domain\Content\Template;
use Illuminate\Console\Command;

class SeedTemplatesCommand extends Command
{
    protected $signature = 'edgephp:seed-templates {--force : Recreate demo templates/fragments}';

    protected $description = 'Seed demo Experience Fragments and Page Templates';

    public function handle(
        CreateFragment $createFragment,
        PublishFragment $publishFragment,
        CreateTemplate $createTemplate,
        PublishTemplate $publishTemplate,
    ): int {
        if ($this->option('force')) {
            Fragment::query()->delete();
            Template::query()->delete();
            $this->warn('Cleared existing templates/fragments.');
        }

        if (Template::query()->where('slug', 'article')->exists()) {
            $this->info('Demo templates already present. Use --force to recreate.');

            return self::SUCCESS;
        }

        $header = $createFragment->handle([
            'name' => 'Header Global',
            'slug' => 'header-global',
            'category' => 'chrome',
            'content' => [
                'metadata' => ['title' => 'Header Global'],
                'sections' => [[
                    'id' => 'header-section',
                    'type' => 'section',
                    'children' => [[
                        'id' => 'header-cta',
                        'type' => 'block',
                        'name' => 'cta',
                        'classes' => [],
                        'rows' => [[
                            'cells' => [[
                                'content' => [
                                    ['type' => 'heading', 'level' => 2, 'text' => 'EdgePHP'],
                                    ['type' => 'paragraph', 'text' => 'Header global compartilhado'],
                                    ['type' => 'link', 'text' => 'Home', 'href' => '/', 'variant' => 'primary'],
                                ],
                            ]],
                        ]],
                    ]],
                ]],
            ],
        ]);
        $publishFragment->handle($header['fragment']->id);

        $footer = $createFragment->handle([
            'name' => 'Footer Global',
            'slug' => 'footer-global',
            'category' => 'chrome',
            'content' => [
                'metadata' => ['title' => 'Footer Global'],
                'sections' => [[
                    'id' => 'footer-section',
                    'type' => 'section',
                    'children' => [[
                        'id' => 'footer-text',
                        'type' => 'block',
                        'name' => 'text',
                        'rows' => [[
                            'cells' => [[
                                'content' => [
                                    ['type' => 'paragraph', 'text' => '© EdgePHP — Footer global'],
                                ],
                            ]],
                        ]],
                    ]],
                ]],
            ],
        ]);
        $publishFragment->handle($footer['fragment']->id);

        $newsletter = $createFragment->handle([
            'name' => 'Newsletter Global',
            'slug' => 'newsletter-global',
            'category' => 'marketing',
            'content' => [
                'metadata' => ['title' => 'Newsletter'],
                'sections' => [[
                    'id' => 'news-section',
                    'type' => 'section',
                    'children' => [[
                        'id' => 'news-cta',
                        'type' => 'block',
                        'name' => 'cta',
                        'rows' => [[
                            'cells' => [[
                                'content' => [
                                    ['type' => 'heading', 'level' => 3, 'text' => 'Newsletter'],
                                    ['type' => 'paragraph', 'text' => 'Receba novidades do CMS.'],
                                    ['type' => 'link', 'text' => 'Inscrever', 'href' => '#', 'variant' => 'primary'],
                                ],
                            ]],
                        ]],
                    ]],
                ]],
            ],
        ]);
        $publishFragment->handle($newsletter['fragment']->id);

        $blank = $createTemplate->handle([
            'name' => 'Blank',
            'slug' => 'blank',
            'description' => 'Página livre sem chrome compartilhado',
            'layout' => [[
                'type' => 'slot',
                'id' => 'main',
                'region' => 'main',
                'label' => 'Conteúdo',
                'editable' => true,
                'required' => true,
                'allowedBlocks' => [],
                'allowSections' => true,
                'allowFragments' => true,
            ]],
        ]);
        $publishTemplate->handle($blank['template']->id);

        $article = $createTemplate->handle([
            'name' => 'Article',
            'slug' => 'article',
            'description' => 'Template para artigos com header, slot e footer',
            'layout' => [
                [
                    'type' => 'fragmentReference',
                    'id' => 'header-reference',
                    'region' => 'header',
                    'fragmentId' => $header['fragment']->id,
                    'variation' => 'default',
                    'mode' => 'linked',
                ],
                [
                    'type' => 'slot',
                    'id' => 'article-content',
                    'region' => 'main',
                    'label' => 'Conteúdo do artigo',
                    'editable' => true,
                    'required' => true,
                    'allowedBlocks' => [
                        'heading', 'text', 'image', 'cards', 'gallery', 'video', 'accordion',
                    ],
                    'allowSections' => true,
                    'allowFragments' => true,
                ],
                [
                    'type' => 'fragmentReference',
                    'id' => 'newsletter-reference',
                    'region' => 'main',
                    'fragmentId' => $newsletter['fragment']->id,
                    'variation' => 'default',
                    'mode' => 'linked',
                ],
                [
                    'type' => 'fragmentReference',
                    'id' => 'footer-reference',
                    'region' => 'footer',
                    'fragmentId' => $footer['fragment']->id,
                    'variation' => 'default',
                    'mode' => 'linked',
                ],
            ],
            'initialContent' => [
                'article-content' => [
                    'sections' => [[
                        'id' => 'article-seed',
                        'type' => 'section',
                        'children' => [[
                            'id' => 'article-heading',
                            'type' => 'block',
                            'name' => 'heading',
                            'rows' => [[
                                'cells' => [[
                                    'content' => [
                                        ['type' => 'heading', 'level' => 1, 'text' => 'Novo artigo'],
                                    ],
                                ]],
                            ]],
                        ]],
                    ]],
                ],
            ],
        ]);
        $publishTemplate->handle($article['template']->id);

        $this->info('Seeded Blank + Article templates and Header/Footer/Newsletter fragments.');

        return self::SUCCESS;
    }
}
