<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

final class RichTextSanitizer
{
    /**
     * @var array<string, true>
     */
    private array $allowedTags = [
        'p' => true,
        'br' => true,
        'strong' => true,
        'b' => true,
        'em' => true,
        'i' => true,
        'u' => true,
        'ul' => true,
        'ol' => true,
        'li' => true,
        'a' => true,
        'blockquote' => true,
        'h1' => true,
        'h2' => true,
        'h3' => true,
        'h4' => true,
        'h5' => true,
        'h6' => true,
    ];

    public function sanitize(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $source = new DOMDocument('1.0', 'UTF-8');
        $target = new DOMDocument('1.0', 'UTF-8');

        $internalErrors = libxml_use_internal_errors(true);

        $source->loadHTML(
            '<?xml encoding="utf-8" ?><div>'.$value.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $container = $source->documentElement;

        if (! $container instanceof DOMElement) {
            return strip_tags($value);
        }

        $wrapper = $target->createElement('div');
        $target->appendChild($wrapper);

        foreach ($container->childNodes as $child) {
            $sanitized = $this->sanitizeNode($child, $target);

            if ($sanitized !== null) {
                $wrapper->appendChild($sanitized);
            }
        }

        $html = '';

        foreach ($wrapper->childNodes as $child) {
            $html .= $target->saveHTML($child);
        }

        return trim($html);
    }

    private function sanitizeNode(DOMNode $node, DOMDocument $target): ?DOMNode
    {
        if ($node instanceof DOMText) {
            return $target->createTextNode($node->textContent ?? '');
        }

        if (! $node instanceof DOMElement) {
            return null;
        }

        $tag = strtolower($node->tagName);

        if (! isset($this->allowedTags[$tag])) {
            $fragment = $target->createDocumentFragment();

            foreach ($node->childNodes as $child) {
                $sanitizedChild = $this->sanitizeNode($child, $target);

                if ($sanitizedChild !== null) {
                    $fragment->appendChild($sanitizedChild);
                }
            }

            return $fragment;
        }

        $element = $target->createElement($tag);

        if ($tag === 'a') {
            $href = trim((string) $node->getAttribute('href'));

            if ($href !== '' && preg_match('/^(https?:|mailto:|tel:|\/)/i', $href) === 1) {
                $element->setAttribute('href', $href);
                $element->setAttribute('target', '_blank');
                $element->setAttribute('rel', 'noreferrer noopener');
            }
        }

        $style = $this->sanitizeStyle($node->getAttribute('style'));

        if ($style !== null) {
            $element->setAttribute('style', $style);
        }

        foreach ($node->childNodes as $child) {
            $sanitizedChild = $this->sanitizeNode($child, $target);

            if ($sanitizedChild !== null) {
                $element->appendChild($sanitizedChild);
            }
        }

        return $element;
    }

    private function sanitizeStyle(string $style): ?string
    {
        $style = trim($style);

        if ($style === '') {
            return null;
        }

        if (preg_match('/text-align\s*:\s*(left|center|right|justify)\s*;?/i', $style, $matches) !== 1) {
            return null;
        }

        return 'text-align: '.strtolower($matches[1]).';';
    }
}
