<?php

declare(strict_types=1);

namespace Shss\MyStoreLocator\Frontend;

use Shss\MyStoreLocator\Service\MapConfigurationResolver;
use Shss\MyStoreLocator\Service\TokenParser;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * USER function for "stdWrap.if": the content element renders nothing for visitors
 * when it has no valid map token (no empty frame, no header).
 */
final class TokenCondition
{
    private ?ContentObjectRenderer $cObj = null;

    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    public function hasValidToken(string $content, array $conf): string
    {
        $settings = MapConfigurationResolver::flexFormSettings($this->cObj?->data ?? []);
        return TokenParser::parse($settings['token'] ?? '') !== '' ? '1' : '';
    }
}
