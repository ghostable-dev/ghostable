<?php

declare(strict_types=1);

namespace App\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

final class GetDesktopAppcast extends Controller
{
    public function __invoke(Request $request): Response
    {
        $channel = $this->resolveChannel($request);
        $release = config("desktop-updates.channels.{$channel}", []);

        $xml = [
            '<?xml version="1.0" encoding="utf-8"?>',
            '<rss version="2.0" xmlns:sparkle="http://www.andymatuschak.org/xml-namespaces/sparkle" xmlns:dc="http://purl.org/dc/elements/1.1/">',
            '  <channel>',
            '    <title>'.$this->escape((string) config('desktop-updates.title', config('app.name', 'Ghostable').' Desktop Updates')).'</title>',
            '    <link>'.$this->escape((string) config('desktop-updates.link', rtrim((string) config('app.url', ''), '/'))).'</link>',
            '    <description>'.$this->escape((string) config('desktop-updates.description', 'Release feed for the Ghostable desktop app.')).'</description>',
            '    <language>'.$this->escape((string) config('desktop-updates.language', 'en-US')).'</language>',
            '    <atom:link xmlns:atom="http://www.w3.org/2005/Atom" rel="self" type="application/rss+xml" href="'.$this->escape($request->fullUrl()).'" />',
        ];

        if ($this->hasRelease($release)) {
            $xml = [...$xml, ...$this->buildItemXml($channel, $release)];
        }

        $xml[] = '  </channel>';
        $xml[] = '</rss>';

        return response(implode("\n", $xml), 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }

    private function resolveChannel(Request $request): string
    {
        $requested = strtolower((string) $request->query('channel', 'stable'));

        return in_array($requested, ['stable', 'beta'], true)
            ? $requested
            : 'stable';
    }

    /**
     * @param  array<string, mixed>  $release
     * @return array<int, string>
     */
    private function buildItemXml(string $channel, array $release): array
    {
        $shortVersion = (string) Arr::get($release, 'short_version');
        $title = (string) (Arr::get($release, 'title') ?: 'Ghostable '.$shortVersion);
        $description = (string) Arr::get($release, 'description', '');
        $releaseNotesUrl = (string) Arr::get($release, 'release_notes_url', '');
        $minimumSystemVersion = (string) Arr::get($release, 'minimum_system_version', '');
        $version = (string) Arr::get($release, 'version');
        $downloadUrl = (string) Arr::get($release, 'download_url');
        $edSignature = (string) Arr::get($release, 'ed_signature');
        $length = max(0, (int) Arr::get($release, 'length', 0));
        $pubDate = (string) Arr::get($release, 'pub_date', now()->toRfc2822String());

        $enclosureAttributes = [
            'url="'.$this->escape($downloadUrl).'"',
            'sparkle:version="'.$this->escape($version).'"',
            'sparkle:shortVersionString="'.$this->escape($shortVersion).'"',
            'sparkle:edSignature="'.$this->escape($edSignature).'"',
            'length="'.$length.'"',
            'type="application/octet-stream"',
        ];

        if ($minimumSystemVersion !== '') {
            $enclosureAttributes[] = 'sparkle:minimumSystemVersion="'.$this->escape($minimumSystemVersion).'"';
        }

        $xml = [
            '    <item>',
            '      <title>'.$this->escape($title).'</title>',
            '      <pubDate>'.$this->escape($pubDate).'</pubDate>',
        ];

        if ($description !== '') {
            $xml[] = '      <description>'.$this->escape($description).'</description>';
        }

        if ($releaseNotesUrl !== '') {
            $xml[] = '      <sparkle:releaseNotesLink>'.$this->escape($releaseNotesUrl).'</sparkle:releaseNotesLink>';
        }

        if ($channel !== 'stable') {
            $xml[] = '      <sparkle:channel>'.$this->escape($channel).'</sparkle:channel>';
        }

        $xml[] = '      <enclosure '.implode(' ', $enclosureAttributes).' />';
        $xml[] = '    </item>';

        return $xml;
    }

    /**
     * @param  array<string, mixed>  $release
     */
    private function hasRelease(array $release): bool
    {
        foreach (['version', 'short_version', 'download_url', 'ed_signature'] as $key) {
            if (trim((string) Arr::get($release, $key, '')) === '') {
                return false;
            }
        }

        return true;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
