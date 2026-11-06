<?php

declare(strict_types=1);

namespace Shss\MyStoreLocator\DataProcessing;

use Shss\MyStoreLocator\Service\MapConfigurationResolver;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Builds the widget container attributes for the "My Store Locator map" content element.
 *
 * TypoScript:
 *   dataProcessing.10 = Shss\MyStoreLocator\DataProcessing\MapProcessor
 *   dataProcessing.10.defaults { height = auto, language = auto, consent = 1, lazy = 1, offset = 0, layout = default }
 *   dataProcessing.10.as = mslMap
 */
final class MapProcessor implements DataProcessorInterface
{
    /** @var array<string, int> */
    private static array $usedIds = [];

    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array
    {
        $defaults = [];
        $defaultsConf = $processorConfiguration['defaults.'] ?? [];
        foreach (array_keys(MapConfigurationResolver::BUILT_IN_DEFAULTS) as $key) {
            if (!array_key_exists($key, $defaultsConf)) {
                continue;
            }
            $value = trim((string)$cObj->stdWrapValue($key, $defaultsConf, ''));
            if (str_starts_with($value, '{$')) {
                // constant not defined (TypoScript template without our constants)
                continue;
            }
            if ($value === '' && in_array($key, ['consent', 'lazy'], true)) {
                // a site setting of type bool set to false arrives as an empty string
                $value = '0';
            }
            $defaults[$key] = $value;
        }
        $row = $processedData['data'] ?? $cObj->data;
        $siteLanguage = $cObj->getRequest()->getAttribute('language');
        $config = MapConfigurationResolver::resolve(
            MapConfigurationResolver::flexFormSettings($row),
            $defaults,
            $siteLanguage instanceof SiteLanguage ? $siteLanguage : null
        );

        $id = 'msl-map-c' . (int)($row['uid'] ?? 0);
        if (isset(self::$usedIds[$id])) {
            $id .= '-' . (++self::$usedIds[$id]);
        } else {
            self::$usedIds[$id] = 1;
        }

        $result = $config;
        $result['id'] = $id;
        $result['attributes'] = [];
        $result['attributeString'] = '';
        $result['placeholderHeight'] = MapConfigurationResolver::placeholderHeight($config);
        if ($config['valid']) {
            $result['attributes'] = MapConfigurationResolver::attributes($config, $id);
            $result['attributeString'] = self::attributeString($result['attributes']);
            $assets = GeneralUtility::makeInstance(AssetCollector::class);
            if ($config['consent']) {
                // strict consent: only the local gate script; widget.js is injected after the visitor's click
                $assets->addJavaScript('msl-consent', MapConfigurationResolver::CONSENT_SCRIPT, ['defer' => 'defer']);
            } else {
                // widget script once per page (fixed identifier; the id is checked by msl-consent.js)
                $assets->addJavaScript('msl-widget', MapConfigurationResolver::WIDGET_URL, ['async' => 'async', 'id' => 'msl-widget-js']);
            }
        }

        $processedData[$processorConfiguration['as'] ?? 'mslMap'] = $result;
        return $processedData;
    }

    /**
     * @param array<string, string> $attributes
     */
    public static function attributeString(array $attributes): string
    {
        $out = '';
        foreach ($attributes as $name => $value) {
            $name = preg_replace('/[^a-z0-9-]/', '', strtolower((string)$name));
            $out .= ' ' . $name . '="' . htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
        }
        return $out;
    }
}
