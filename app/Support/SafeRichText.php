<?php

namespace App\Support;

use Illuminate\Support\Str;

class SafeRichText
{
    /**
     * Render rich text with a small safe HTML whitelist.
     */
    public static function sanitize(?string $html): string
    {
        $value = trim((string) $html);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $value) ?? $value;
        $value = strip_tags($value, '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><blockquote><a>');
        $value = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/iu', '', $value) ?? $value;
        $value = preg_replace('/\son\w+\s*=\s*[^\s>]+/iu', '', $value) ?? $value;

        $value = preg_replace_callback('/<a\b[^>]*href\s*=\s*(["\'])(.*?)\1[^>]*>/iu', function (array $matches): string {
            $url = trim((string) ($matches[2] ?? ''));
            if (preg_match('/^(javascript:|data:|vbscript:)/i', $url)) {
                return '<a href="#">';
            }
            return '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer nofollow">';
        }, $value) ?? $value;

        return trim($value);
    }

    public static function plainText(?string $html): string
    {
        $safe = static::sanitize($html);
        return trim(html_entity_decode(strip_tags($safe), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public static function excerpt(?string $html, int $limit = 180): string
    {
        return Str::limit(static::plainText($html), $limit);
    }
}

