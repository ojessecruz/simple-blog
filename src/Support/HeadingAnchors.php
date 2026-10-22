<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Support;

use Illuminate\Support\Str;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\RawMarkupContainerInterface;
use League\CommonMark\Node\StringContainerHelper;

/**
 * CommonMark extension that gives every heading of a post body an id built
 * from its text (unique within the post), optionally followed by a "#"
 * permalink, and records the headings for the table of contents. Works on
 * the parsed document, so it never re-parses the rendered HTML.
 */
final class HeadingAnchors implements ExtensionInterface
{
    /** Id for headings whose text has nothing to slug (only emoji, CJK…). */
    public const FALLBACK_ID = 'section';

    /** @var list<array{level: int, id: string, text: string}> */
    private array $headings = [];

    public function __construct(private readonly bool $permalinks = true) {}

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, $this->onDocumentParsed(...));
    }

    /**
     * Headings of the last converted document, in order.
     *
     * @return list<array{level: int, id: string, text: string}>
     */
    public function headings(): array
    {
        return $this->headings;
    }

    private function onDocumentParsed(DocumentParsedEvent $event): void
    {
        $this->headings = [];
        $used = [];

        // Collected first: the permalinks appended below would otherwise be walked too.
        $headings = [];
        foreach ($event->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if ($node instanceof Heading) {
                $headings[] = $node;
            }
        }

        foreach ($headings as $heading) {
            $text = trim((string) preg_replace('/\s+/u', ' ', StringContainerHelper::getChildText($heading, [RawMarkupContainerInterface::class])));
            $base = Str::slug($text) ?: self::FALLBACK_ID;
            $id = $base;

            for ($n = 2; isset($used[$id]); $n++) {
                $id = $base.'-'.$n;
            }

            $used[$id] = true;
            $heading->data->set('attributes/id', $id);
            $this->headings[] = ['level' => $heading->getLevel(), 'id' => $id, 'text' => $text];

            if ($this->permalinks) {
                $link = new Link('#'.$id, '#');
                $link->data->set('attributes/class', 'blog-heading-anchor');
                $link->data->set('attributes/aria-label', __('blog::messages.heading_link', ['heading' => $text]));
                $heading->appendChild($link);
            }
        }
    }
}
