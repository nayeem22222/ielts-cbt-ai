<?php

declare(strict_types=1);

namespace App\Support\Listening;

final class ListeningContentRenderer
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><ul><ol><li><table><thead><tbody><tr><th><td><h1><h2><h3><h4><div><span>';

    public static function sanitizeEditorHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = str_replace(["\xC2\xA0", '&nbsp;'], ' ', $html);
        $html = preg_replace('/<h[1-6]>\s*<\/h[1-6]>/i', '', $html) ?? $html;
        $html = preg_replace('/<p>\s*<\/p>/i', '', $html) ?? $html;

        return trim(strip_tags($html, self::ALLOWED_TAGS));
    }
}
