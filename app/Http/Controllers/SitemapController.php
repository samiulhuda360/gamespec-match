<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $xml = Cache::remember('sitemap_xml', 86400, function () {
            return $this->generateSitemap();
        });

        return response($xml, 200)
            ->header('Content-Type', 'application/xml');
    }

    private function generateSitemap(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Homepage
        $xml .= $this->url(url('/'), now(), 'daily', '1.0');

        // Static pages
        $xml .= $this->url(route('apps.browse'), now(), 'daily', '0.9');
        $xml .= $this->url(route('apps.free'), now(), 'daily', '0.8');
        $xml .= $this->url(route('apps.latest'), now(), 'daily', '0.8');
        $xml .= $this->url(route('apps.popular'), now(), 'weekly', '0.8');
        $xml .= $this->url(route('deals.index'), now(), 'daily', '0.9');
        $xml .= $this->url(route('search.advanced'), now(), 'weekly', '0.7');
        $xml .= $this->url(route('hardware.add-specs'), now(), 'monthly', '0.8');

        // Game pages (most important for SEO)
        App::select('slug', 'updated_at')
            ->whereNotNull('slug')
            ->where('type', 'game')
            ->orderBy('updated_at', 'desc')
            ->chunk(1000, function ($apps) use (&$xml) {
                foreach ($apps as $app) {
                    $xml .= $this->url(
                        route('apps.show', $app->slug),
                        $app->updated_at,
                        'weekly',
                        '0.8'
                    );
                }
            });

        // Published collections
        Collection::select('slug', 'updated_at')
            ->where('is_published', true)
            ->orderBy('updated_at', 'desc')
            ->chunk(100, function ($collections) use (&$xml) {
                foreach ($collections as $collection) {
                    $xml .= $this->url(
                        route('collections.show', $collection->slug),
                        $collection->updated_at,
                        'weekly',
                        '0.7'
                    );
                }
            });

        $xml .= '</urlset>';

        return $xml;
    }

    private function url(string $loc, $lastmod, string $changefreq, string $priority): string
    {
        $lastmodFormatted = $lastmod instanceof \DateTime
            ? $lastmod->format('Y-m-d')
            : now()->format('Y-m-d');

        return sprintf(
            '<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%s</priority></url>',
            htmlspecialchars($loc, ENT_XML1, 'UTF-8'),
            $lastmodFormatted,
            $changefreq,
            $priority
        );
    }
}
