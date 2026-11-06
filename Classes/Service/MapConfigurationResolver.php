<?php

declare(strict_types=1);

namespace Shss\MyStoreLocator\Service;

use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Turns the FlexForm values of one content element plus the defaults (site settings / TypoScript constants)
 * into the final widget configuration. Element value empty → default.
 */
final class MapConfigurationResolver
{
    public const WIDGET_URL = 'https://app.my-store-locator.com/widget.js';
    public const CONSENT_SCRIPT = 'EXT:my_store_locator/Resources/Public/JavaScript/msl-consent.js';

    public const BUILT_IN_DEFAULTS = [
        'height' => 'auto',
        'language' => 'auto',
        'consent' => '1',
        'lazy' => '1',
        'offset' => '0',
        'layout' => 'default',
    ];

    /**
     * Reads the "settings" section of the content element's FlexForm.
     *
     * @return array<string, string>
     */
    public static function flexFormSettings(array $row): array
    {
        $xml = $row['pi_flexform'] ?? '';
        if (is_array($xml)) {
            // FormEngine / preview may hand over an already parsed structure
            $settings = [];
            foreach ($xml['data']['sDEF']['lDEF'] ?? [] as $key => $value) {
                if (str_starts_with((string)$key, 'settings.')) {
                    $settings[substr((string)$key, 9)] = (string)($value['vDEF'] ?? '');
                }
            }
            return $settings;
        }
        if (!is_string($xml) || trim($xml) === '') {
            return [];
        }
        $data = GeneralUtility::makeInstance(FlexFormService::class)->convertFlexFormContentToArray($xml);
        $settings = [];
        foreach (($data['settings'] ?? []) as $key => $value) {
            $settings[(string)$key] = is_scalar($value) ? (string)$value : '';
        }
        return $settings;
    }

    /**
     * @param array<string, string> $element FlexForm settings of the element
     * @param array<string, string> $defaults defaults from TypoScript / site settings
     * @return array{valid: bool, token: string, height: string, language: string, consent: bool, lazy: bool, offset: int, layout: string}
     */
    public static function resolve(array $element, array $defaults = [], ?SiteLanguage $siteLanguage = null): array
    {
        $pick = static function (string $key) use ($element, $defaults): string {
            $value = trim((string)($element[$key] ?? ''));
            if ($value === '') {
                $value = trim((string)($defaults[$key] ?? ''));
            }
            if ($value === '') {
                $value = self::BUILT_IN_DEFAULTS[$key] ?? '';
            }
            return $value;
        };

        $token = TokenParser::parse($element['token'] ?? '');

        $height = $pick('height');
        if (strtolower($height) === 'auto') {
            $height = 'auto';
        } elseif (preg_match('/^\d{2,5}$/', $height)) {
            $height .= 'px';
        } elseif (!preg_match('/^\d{1,5}(\.\d+)?(px|vh|em|rem|%)$/', $height)) {
            $height = 'auto';
        }

        $language = strtolower($pick('language'));
        if (!in_array($language, ['de', 'en'], true)) {
            $language = self::languageFromSite($siteLanguage);
        }

        $offset = $pick('offset');
        $offset = preg_match('/^\d{1,4}$/', $offset) ? (int)$offset : 0;

        $layout = $pick('layout') === 'split' ? 'split' : 'default';

        return [
            'valid' => $token !== '',
            'token' => $token,
            'height' => $height,
            'language' => $language,
            'consent' => self::toBool($pick('consent')),
            'lazy' => self::toBool($pick('lazy')),
            'offset' => $offset,
            'layout' => $layout,
        ];
    }

    /**
     * Attributes of the map container (unescaped; escape when rendering).
     * Consent off: a normal widget container (class "msl-map" + data-mystorelocator), mounted by widget.js.
     * Consent on (strict): a gated container WITHOUT the widget's mount markers (no "msl-map", no data-mystorelocator,
     * no data-consent) – msl-consent.js turns it into a normal container and injects widget.js only after the click.
     *
     * @return array<string, string>
     */
    public static function attributes(array $config, string $id): array
    {
        if ($config['consent']) {
            $attributes = [
                'class' => 'msl-map-consent',
                'data-msl-gate' => '',
                'id' => $id,
            ];
        } else {
            $attributes = [
                'class' => 'msl-map',
                'id' => $id,
                'data-mystorelocator' => '',
            ];
        }
        $attributes['data-token'] = $config['token'];
        $attributes['data-height'] = $config['height'];
        $attributes['data-lang'] = $config['language'];
        if ($config['lazy']) {
            $attributes['data-lazy'] = 'true';
        }
        if ($config['offset'] > 0) {
            $attributes['data-offset'] = (string)$config['offset'];
        }
        if ($config['layout'] === 'split') {
            $attributes['data-layout'] = 'split';
        }
        return $attributes;
    }

    /**
     * Height of the click-to-load placeholder: 760px for "auto" (the widget's start height), otherwise the map height.
     */
    public static function placeholderHeight(array $config): string
    {
        return $config['height'] === 'auto' ? '760px' : $config['height'];
    }

    public static function languageFromSite(?SiteLanguage $siteLanguage): string
    {
        if ($siteLanguage === null) {
            return 'en';
        }
        $code = strtolower($siteLanguage->getLocale()->getLanguageCode());
        return str_starts_with($code, 'de') ? 'de' : 'en';
    }

    private static function toBool(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
    }
}
