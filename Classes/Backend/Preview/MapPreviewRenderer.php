<?php

declare(strict_types=1);

namespace Shss\MyStoreLocator\Backend\Preview;

use Shss\MyStoreLocator\Service\MapConfigurationResolver;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Page module preview of the "My Store Locator map" content element.
 */
final class MapPreviewRenderer extends StandardContentPreviewRenderer
{
    private const LLL = 'LLL:EXT:my_store_locator/Resources/Private/Language/locallang_db.xlf:';

    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $row = $item->getRecord();
        $settings = MapConfigurationResolver::flexFormSettings($row);
        $config = MapConfigurationResolver::resolve($settings);
        $lang = $this->getLanguageServiceForPreview();
        $e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (!$config['valid']) {
            $hint = trim((string)($settings['token'] ?? '')) === ''
                ? $lang->sL(self::LLL . 'preview.noToken')
                : $lang->sL(self::LLL . 'preview.invalidToken');
            return '<div class="callout callout-warning" style="margin:0"><div class="callout-body">'
                . $e($hint) . '</div></div>';
        }

        $language = trim((string)($settings['language'] ?? ''));
        $rows = [
            $lang->sL(self::LLL . 'preview.token') => $config['token'],
            $lang->sL(self::LLL . 'flexform.height') => trim((string)($settings['height'] ?? '')) !== '' ? $config['height'] : $lang->sL(self::LLL . 'preview.default'),
            $lang->sL(self::LLL . 'flexform.language') => in_array($language, ['de', 'en'], true) ? $language : $lang->sL(self::LLL . 'preview.default'),
            $lang->sL(self::LLL . 'flexform.consent') => $this->tri($settings['consent'] ?? '', $lang),
        ];
        $html = '<div class="msl-preview">'
            . '<dl style="display:grid;grid-template-columns:max-content 1fr;gap:2px 12px;margin:0">';
        foreach ($rows as $label => $value) {
            $html .= '<dt style="font-weight:normal;opacity:.75">' . $e($label) . '</dt><dd style="margin:0"><code>' . $e($value) . '</code></dd>';
        }
        return $html . '</dl></div>';
    }

    private function tri(string $value, LanguageService $lang): string
    {
        return match (trim($value)) {
            '1' => $lang->sL(self::LLL . 'flexform.on'),
            '0' => $lang->sL(self::LLL . 'flexform.off'),
            default => $lang->sL(self::LLL . 'preview.default'),
        };
    }

    private function getLanguageServiceForPreview(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
