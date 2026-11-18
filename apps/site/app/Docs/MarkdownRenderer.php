<?php

declare(strict_types=1);

namespace App\Docs;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalink;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use Phiki\Adapters\CommonMark\PhikiExtension;
use Phiki\Theme\Theme;

/**
 * Renders a guide body to HTML with highlighted code, heading anchors, and
 * GitHub-style alerts, and lists its headings for the table of contents.
 */
final class MarkdownRenderer
{
    private const array ALERTS = [
        'NOTE' => 'Note',
        'TIP' => 'Tip',
        'IMPORTANT' => 'Important',
        'WARNING' => 'Warning',
        'CAUTION' => 'Caution',
    ];

    private ?MarkdownConverter $converter = null;

    public function render(string $markdown): RenderedGuide
    {
        $result = $this->converter()->convert($markdown);

        return new RenderedGuide(
            html: $this->alerts($result->getContent()),
            headings: $this->headings($result->getDocument()),
        );
    }

    private function converter(): MarkdownConverter
    {
        if ($this->converter !== null) {
            return $this->converter;
        }

        $environment = new Environment([
            'heading_permalink' => [
                'html_class' => 'heading-anchor',
                'id_prefix' => '',
                'fragment_prefix' => '',
                'apply_id_to_heading' => true,
                'insert' => 'after',
                'symbol' => '#',
                'aria_hidden' => true,
                'min_heading_level' => 2,
                'max_heading_level' => 3,
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new PhikiExtension([
            'light' => Theme::GithubLight,
            'dark' => Theme::GithubDark,
        ]));

        return $this->converter = new MarkdownConverter($environment);
    }

    /**
     * @return list<array{id: string, text: string, level: int}>
     */
    private function headings(Node $document): array
    {
        $headings = [];

        foreach ($document->iterator() as $node) {
            if (! $node instanceof Heading || $node->getLevel() < 2 || $node->getLevel() > 3) {
                continue;
            }

            $slug = null;
            $text = '';

            foreach ($node->iterator() as $child) {
                if ($child instanceof HeadingPermalink) {
                    $slug = $child->getSlug();
                } elseif ($child instanceof Text || $child instanceof Code) {
                    $text .= $child->getLiteral();
                }
            }

            if ($slug !== null) {
                $headings[] = ['id' => $slug, 'text' => trim($text), 'level' => $node->getLevel()];
            }
        }

        return $headings;
    }

    /**
     * Turns `> [!NOTE]` blockquotes into labelled asides, as GitHub renders them.
     */
    private function alerts(string $html): string
    {
        $pattern = '#<blockquote>\s*<p>\[!('.implode('|', array_keys(self::ALERTS)).')\]\s*(.*?)</blockquote>#s';

        return (string) preg_replace_callback($pattern, function (array $match): string {
            $kind = strtolower($match[1]);
            $title = self::ALERTS[$match[1]];
            $body = $match[2] === '' || str_starts_with($match[2], '</p>')
                ? ltrim(substr($match[2], 4))
                : '<p>'.$match[2];

            return "<aside class=\"alert alert-{$kind}\"><p class=\"alert-title\">{$title}</p>{$body}</aside>";
        }, $html);
    }
}
