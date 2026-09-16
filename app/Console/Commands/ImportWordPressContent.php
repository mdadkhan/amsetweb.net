<?php

namespace App\Console\Commands;

use App\Models\Conference;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class ImportWordPressContent extends Command
{
    protected $signature = 'amset:import-wordpress {--base-url=https://amsetweb.net}';

    protected $description = 'Import curated public content from the legacy AMSET WordPress REST API';

    private const PAGE_SLUGS = [
        'about-us',
        'association-of-muslim-scientists-engineers-and-technology-professionals',
        'conferences',
        'contact-us',
        'eminent-scientists',
        'from-the-past',
        'future-holds',
        'gallery',
        'reading',
        'readings-links',
    ];

    private const CONFERENCE_CATEGORY = 4;

    private const POST_CATEGORIES = [3 => 'news', 6 => 'about'];

    private const SCIENTIST_POST_SLUGS = ['abouheif', 'salman-hameed', 'shanavas-m-d', 'speaker_2016_tanvir_arfi'];

    public function handle(): int
    {
        $baseUrl = rtrim((string) $this->option('base-url'), '/');
        $sanitizer = new HtmlSanitizer((new HtmlSanitizerConfig)->allowSafeElements());

        $pages = $this->fetchCollection($baseUrl.'/wp-json/wp/v2/pages');
        $posts = $this->fetchCollection($baseUrl.'/wp-json/wp/v2/posts');
        $pageCount = 0;
        $postCount = 0;
        $conferenceCount = 0;

        foreach ($pages as $source) {
            if (! in_array($source['slug'], self::PAGE_SLUGS, true)) {
                continue;
            }

            Page::query()->updateOrCreate(['slug' => $source['slug']], [
                'title' => $this->plainText($source['title']['rendered'] ?? ''),
                'summary' => $this->plainText($source['excerpt']['rendered'] ?? ''),
                'body' => $sanitizer->sanitize($source['content']['rendered'] ?? ''),
                'meta' => ['wordpress_id' => $source['id'], 'source_url' => $source['link']],
                'is_published' => $source['status'] === 'publish',
            ]);
            $pageCount++;
        }

        foreach ($posts as $source) {
            if (in_array(self::CONFERENCE_CATEGORY, $source['categories'] ?? [], true)) {
                $title = $this->plainText($source['title']['rendered'] ?? '');
                $year = preg_match('/\b(19|20)\d{2}\b/', $title, $matches) ? $matches[0] : null;
                $slug = $year ? $year.'-annual-conference' : $source['slug'];

                Conference::query()->updateOrCreate(['slug' => $slug], [
                    'title' => Str::of($title)->replaceEnd(' Program', '')->toString(),
                    'summary' => $this->plainText($source['excerpt']['rendered'] ?? ''),
                    'body' => $sanitizer->sanitize($source['content']['rendered'] ?? ''),
                    'program_url' => $source['link'],
                    'is_published' => $source['status'] === 'publish',
                ]);
                $conferenceCount++;

                continue;
            }

            $category = collect($source['categories'] ?? [])->first(fn (int $id): bool => isset(self::POST_CATEGORIES[$id]));
            $isScientist = in_array($source['slug'], self::SCIENTIST_POST_SLUGS, true);

            if ($category === null && ! $isScientist) {
                continue;
            }

            Post::query()->updateOrCreate(['slug' => $source['slug']], [
                'title' => $this->plainText($source['title']['rendered'] ?? ''),
                'excerpt' => $this->plainText($source['excerpt']['rendered'] ?? ''),
                'body' => $sanitizer->sanitize($source['content']['rendered'] ?? ''),
                'category' => $isScientist ? 'scientist' : self::POST_CATEGORIES[$category],
                'published_at' => $source['date_gmt'] ?? $source['date'] ?? null,
            ]);
            $postCount++;
        }

        $this->components->info("Imported {$pageCount} pages, {$postCount} posts, and {$conferenceCount} conferences from {$baseUrl}.");

        return self::SUCCESS;
    }

    private function fetchCollection(string $url): array
    {
        $response = Http::retry(3, 500)->timeout(30)->get($url, [
            'context' => 'view',
            'per_page' => 100,
            'status' => 'publish',
        ]);

        $response->throw();

        return $response->json();
    }

    private function plainText(string $html): string
    {
        return Str::of(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'))->squish()->toString();
    }
}
