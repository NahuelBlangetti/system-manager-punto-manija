<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class MarketplaceAgent
{
    public const CONTENT_SIGNAL = 'search=yes, ai-input=yes, ai-train=no';

    /**
     * Bots que isitagentready y Cloudflare reconocen como crawlers / agentes IA.
     *
     * @var list<string>
     */
    private const AI_BOTS = [
        'GPTBot',
        'OAI-SearchBot',
        'ChatGPT-User',
        'Claude-Web',
        'ClaudeBot',
        'anthropic-ai',
        'Google-Extended',
        'Applebot-Extended',
        'Amazonbot',
        'PerplexityBot',
        'Bytespider',
        'CCBot',
        'cohere-ai',
        'FacebookBot',
        'Meta-ExternalAgent',
    ];

    public static function robotsTxt(): string
    {
        $lines = [
            'User-agent: *',
            'Content-Signal: '.self::CONTENT_SIGNAL,
            'Allow: /',
            'Disallow: /admin',
            '',
        ];

        foreach (self::AI_BOTS as $bot) {
            $lines[] = 'User-agent: '.$bot;
            $lines[] = 'Content-Signal: '.self::CONTENT_SIGNAL;
            $lines[] = 'Allow: /';
            $lines[] = 'Disallow: /admin';
            $lines[] = '';
        }

        $lines[] = 'Sitemap: '.url('/sitemap.xml');
        $lines[] = '';

        return implode("\n", $lines);
    }

    public static function wantsMarkdown(Request $request): bool
    {
        $accept = strtolower(trim((string) $request->header('Accept', '')));
        if ($accept === '' || $accept === '*/*') {
            return false;
        }

        return $request->prefers(['text/markdown', 'text/html']) === 'text/markdown';
    }

    public static function markdownResponse(string $markdown): Response
    {
        $tokens = max(1, (int) ceil(strlen($markdown) / 4));

        return response($markdown, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Content-Signal' => self::CONTENT_SIGNAL,
            'Vary' => 'Accept',
            'X-Markdown-Tokens' => (string) $tokens,
        ]);
    }

    public static function textResponse(string $body, string $contentType = 'text/plain; charset=UTF-8'): Response
    {
        return response($body, 200, [
            'Content-Type' => $contentType,
            'Content-Signal' => self::CONTENT_SIGNAL,
        ]);
    }

    /**
     * @return list<string>
     */
    public static function linkValues(): array
    {
        return [
            '<'.url('/llms.txt').'>; rel="describedby"; type="text/plain"',
            '<'.url('/sitemap.xml').'>; rel="sitemap"; type="application/xml"',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $jsonLd
     */
    public static function wrapMarkdown(string $title, string $description, string $body, array $jsonLd = []): string
    {
        $parts = [
            '---',
            'title: '.self::yamlScalar($title),
            'description: '.self::yamlScalar($description),
            '---',
            '',
            $body,
        ];

        if ($jsonLd !== []) {
            $parts[] = '';
            $parts[] = '```json';
            $parts[] = json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '[]';
            $parts[] = '```';
        }

        return implode("\n", $parts)."\n";
    }

    private static function yamlScalar(string $value): string
    {
        $trimmed = trim(str_replace(["\r", "\n"], ' ', $value));

        return '"'.str_replace('"', '\\"', $trimmed).'"';
    }
}
