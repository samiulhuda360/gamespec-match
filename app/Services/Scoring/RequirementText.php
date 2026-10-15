<?php

namespace App\Services\Scoring;

/**
 * Steam's requirement HTML ("<ul><li><strong>Processor:</strong> Intel Core i5-4460 or AMD FX-6300</li>...")
 * into plain text per component: cpu, ram, gpu, storage, directx. A PHP port of html_to_kv()
 * in scripts/steam_scraper.py, so the importer and the scraper read requirements the same way.
 */
final class RequirementText
{
    private const KEYS = [
        'cpu' => '/^(processor|cpu|proc)\b/i',
        'ram' => '/^(memory|ram)\b/i',
        'gpu' => '/^(graphics|graphic\s*card|video\s*card|videocard|gpu|vga)\b/i',
        'storage' => '/^(storage|disk\s*space|disc\s*space|hard\s*drive|hdd|ssd|free\s*space|space\s*(required|available)?)\b/i',
        'directx' => '/^(direct\s*x|directx|dx)\b/i',
    ];

    /** @return array{cpu: ?string, ram: ?string, gpu: ?string, storage: ?string, directx: ?string, os: ?string} */
    public static function parse(?string $html): array
    {
        $out = ['cpu' => null, 'ram' => null, 'gpu' => null, 'storage' => null, 'directx' => null, 'os' => null];
        if ($html === null || trim($html) === '') {
            return $out;
        }

        // One line per <li> or <br>, tags stripped, whitespace collapsed.
        $text = preg_replace(['/<br\s*\/?>/i', '/<\/li>/i'], "\n", $html);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        foreach (preg_split('/\n+/', $text) as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', str_replace(["\u{00ae}", "\u{2122}"], '', $line)));
            if ($line === '' || preg_match('/^(minimum|recommended)\s*:?$/i', $line)) {
                continue;
            }
            if (! preg_match('/^([^:]{1,60}):\s*(.+)$/u', $line, $m)) {
                continue;
            }
            $key = strtolower(trim(preg_replace('/[^a-z0-9\s\-\+]/i', ' ', $m[1])));
            $value = trim($m[2]);
            if (preg_match('/^(os|os version|operating system)\b/', $key)) {
                $out['os'] ??= $value;

                continue;
            }
            foreach (self::KEYS as $field => $pattern) {
                if (preg_match($pattern, $key)) {
                    // Keep the longer value when a page lists a component twice.
                    if ($out[$field] === null || mb_strlen($value) > mb_strlen($out[$field])) {
                        $out[$field] = mb_substr($value, 0, 480);
                    }
                    break;
                }
            }
        }

        return $out;
    }
}
