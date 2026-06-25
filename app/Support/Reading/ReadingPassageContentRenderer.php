<?php

declare(strict_types=1);

namespace App\Support\Reading;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class ReadingPassageContentRenderer
{
    private const LABELS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public static function sanitizeReferenceMarkers(string $html): string
    {
        if ($html === '') {
            return '';
        }

        // Legacy/broken admin markers like {[quoted text]}15}] — keep the text, drop the marker.
        $html = preg_replace('/\{\[([^\]]*)\]\}\d+\}\]/u', '$1', $html) ?? $html;
        $html = preg_replace('/\{\[([^\]]*)\]\}/u', '$1', $html) ?? $html;

        return $html;
    }

    public static function applyParagraphLabels(string $html): string
    {
        $html = trim(self::sanitizeReferenceMarkers($html));

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="reading-passage-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);
        $paragraphs = $xpath->query('//*[@id="reading-passage-root"]//p');

        if ($paragraphs === false || $paragraphs->length === 0) {
            return $html;
        }

        $labelIndex = 0;

        foreach ($paragraphs as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }

            if (self::paragraphHasLabel($paragraph)) {
                continue;
            }

            $label = self::LABELS[$labelIndex] ?? (string) ($labelIndex + 1);
            $labelIndex++;

            $wrapper = $document->createElement('div');
            $wrapper->setAttribute('class', 'reading-passage-paragraph flex gap-4');
            $wrapper->setAttribute('data-paragraph', $label);

            $labelNode = $document->createElement('span', $label);
            $labelNode->setAttribute('class', 'reading-passage-label shrink-0 font-bold');
            $wrapper->appendChild($labelNode);

            $content = $document->createElement('div');
            $content->setAttribute('class', 'reading-passage-paragraph-body flex-1');

            while ($paragraph->firstChild !== null) {
                $content->appendChild($paragraph->firstChild);
            }

            $wrapper->appendChild($content);
            $paragraph->parentNode?->replaceChild($wrapper, $paragraph);
        }

        $root = $document->getElementById('reading-passage-root');

        if ($root === null) {
            return $html;
        }

        $rendered = '';

        foreach ($root->childNodes as $child) {
            $rendered .= $document->saveHTML($child);
        }

        return $rendered;
    }

    public static function htmlToPlainText(string $html): string
    {
        $html = preg_replace('/<\/(p|div|h[1-6]|li)>/i', "\n\n", $html) ?? $html;
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }

    private static function paragraphHasLabel(DOMElement $paragraph): bool
    {
        $class = $paragraph->getAttribute('class');

        return str_contains($class, 'reading-passage-label')
            || $paragraph->parentNode instanceof DOMElement
            && str_contains($paragraph->parentNode->getAttribute('class'), 'reading-passage-paragraph');
    }
}
