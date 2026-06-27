<?php

declare(strict_types=1);

namespace App\Support\Reading;

use DOMDocument;
use DOMElement;
use DOMNode;

final class ReadingHtmlSanitizer
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 'u', 'ul', 'ol', 'li',
        'table', 'thead', 'tbody', 'tr', 'td', 'th',
        'h2', 'h3', 'h4', 'blockquote', 'span', 'div',
    ];

    /** @var list<string> */
    private const ALLOWED_SPAN_CLASSES = [
        'reading-passage-paragraph',
        'reading-passage-label',
        'reading-passage-paragraph-body',
        'reading-reference-marker',
    ];

    public static function sanitize(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="reading-sanitize-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('reading-sanitize-root');

        if ($root === null) {
            return '';
        }

        self::sanitizeNode($root);

        $rendered = '';

        foreach ($root->childNodes as $child) {
            $rendered .= $document->saveHTML($child);
        }

        return $rendered;
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);

        if ($tag === 'reading-sanitize-root') {
            foreach (iterator_to_array($node->childNodes) as $child) {
                self::sanitizeNode($child);
            }

            return;
        }

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            self::unwrapElement($node);

            return;
        }

        self::sanitizeAttributes($node);

        foreach (iterator_to_array($node->childNodes) as $child) {
            self::sanitizeNode($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);
        $allowed = [];

        if ($tag === 'span') {
            $class = $element->getAttribute('class');
            $classes = array_filter(preg_split('/\s+/', $class) ?: []);

            foreach ($classes as $className) {
                if (in_array($className, self::ALLOWED_SPAN_CLASSES, true)) {
                    $allowed[] = $className;
                }
            }

            if ($allowed !== []) {
                $element->setAttribute('class', implode(' ', array_unique($allowed)));
            } else {
                $element->removeAttribute('class');
            }
        } elseif ($tag === 'div') {
            $class = $element->getAttribute('class');
            $classes = array_filter(preg_split('/\s+/', $class) ?: []);
            $safeDivClasses = array_values(array_intersect($classes, [
                'reading-passage-paragraph',
                'reading-passage-paragraph-body',
            ]));

            if ($safeDivClasses !== []) {
                $element->setAttribute('class', implode(' ', $safeDivClasses));
            } else {
                $element->removeAttribute('class');
            }
        } elseif ($element->hasAttribute('class')) {
            $element->removeAttribute('class');
        }

        if ($element->hasAttribute('data-paragraph')) {
            $label = preg_replace('/[^A-Z0-9]/i', '', $element->getAttribute('data-paragraph')) ?? '';
            $element->setAttribute('data-paragraph', substr($label, 0, 3));
        } else {
            $element->removeAttribute('data-paragraph');
        }

        $attributeNames = [];

        foreach ($element->attributes ?? [] as $attribute) {
            $attributeNames[] = strtolower($attribute->nodeName);
        }

        foreach ($attributeNames as $name) {
            if (str_starts_with($name, 'on')) {
                $element->removeAttribute($name);
                continue;
            }

            if (! in_array($name, self::allowedAttributeNames($tag), true)) {
                $element->removeAttribute($name);
                continue;
            }

            $value = $element->getAttribute($name);

            if (self::isDangerousUrl($value)) {
                $element->removeAttribute($name);
            }
        }
    }

    /**
     * @return list<string>
     */
    private static function allowedAttributeNames(string $tag): array
    {
        return match ($tag) {
            'span', 'div' => ['class', 'data-paragraph'],
            'td', 'th' => ['colspan', 'rowspan'],
            default => [],
        };
    }

    private static function isDangerousUrl(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return str_starts_with($normalized, 'javascript:')
            || str_starts_with($normalized, 'data:text/html')
            || str_starts_with($normalized, 'vbscript:');
    }

    private static function unwrapElement(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);

        foreach (iterator_to_array($parent->childNodes) as $child) {
            self::sanitizeNode($child);
        }
    }
}
