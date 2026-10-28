<?php

namespace App\Domain\News\Actions;

use App\Models\NewsItem;
use App\Models\NewsSource;
use DOMDocument;
use DOMElement;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IngestNewsFeedsAction
{
    public function execute(): int
    {
        $created = 0;

        NewsSource::query()
            ->where('active', true)
            ->orderBy('id')
            ->each(function (NewsSource $source) use (&$created): void {
                $created += $this->ingest($source);
            });

        return $created;
    }

    private function ingest(NewsSource $source): int
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['Accept' => 'application/rss+xml, application/xml, text/xml'])
                ->get($source->feed_url);
        } catch (ConnectionException) {
            return 0;
        }

        if (! $response->successful()) {
            return 0;
        }

        $items = $this->items($response->body());
        if ($items === null) {
            return 0;
        }

        $created = 0;
        foreach (array_slice($items, 0, 15) as $item) {
            if ($this->store($source, $item)) {
                $created++;
            }
        }

        $source->update(['last_fetched_at' => now()]);

        return $created;
    }

    /**
     * @return list<array{title: string, summary: string, guid: string, published_at: Carbon}>|null
     */
    private function items(string $xml): ?array
    {
        if (strlen($xml) > 1_000_000) {
            return null;
        }

        $document = new DOMDocument;
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        if ($loaded === false) {
            return null;
        }

        $parsed = [];
        foreach ($document->getElementsByTagName('item') as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $title = $this->plain($this->child($node, 'title'), 180);
            $guid = $this->child($node, 'guid');
            if ($guid === '') {
                $guid = $this->child($node, 'link');
            }
            if ($title === '' || $guid === '') {
                continue;
            }

            $parsed[] = [
                'title' => $title,
                'summary' => $this->plain($this->child($node, 'description'), 500),
                'guid' => Str::limit($guid, 255, ''),
                'published_at' => $this->publishedAt($this->child($node, 'pubDate')),
            ];
        }

        return $parsed;
    }

    /**
     * @param  array{title: string, summary: string, guid: string, published_at: Carbon}  $item
     */
    private function store(NewsSource $source, array $item): bool
    {
        $existing = NewsItem::query()->where('guid', $item['guid'])->exists();
        if ($existing) {
            return false;
        }

        NewsItem::query()->create([
            'news_source_id' => $source->id,
            'topic_id' => $source->topic_id,
            'guid' => $item['guid'],
            'title' => $item['title'],
            'summary' => $item['summary'] !== '' ? $item['summary'] : $item['title'],
            'source' => $source->name,
            'published_at' => $item['published_at'],
        ]);

        return true;
    }

    private function child(DOMElement $item, string $name): string
    {
        foreach ($item->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $name) {
                return trim($child->textContent);
            }
        }

        return '';
    }

    private function plain(string $value, int $limit): string
    {
        $text = trim(html_entity_decode(strip_tags($value)));
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return Str::limit($text, $limit, '');
    }

    private function publishedAt(string $value): Carbon
    {
        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return now();
        }

        return Carbon::createFromTimestamp($timestamp);
    }
}
