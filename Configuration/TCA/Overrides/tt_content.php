<?php

declare(strict_types=1);

use Shss\MyStoreLocator\Backend\Preview\MapPreviewRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    $cType = 'mystorelocator_map';
    $lll = 'LLL:EXT:my_store_locator/Resources/Private/Language/locallang_db.xlf:';

    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => $lll . 'ce.title',
            'description' => $lll . 'ce.description',
            'value' => $cType,
            'icon' => 'mystorelocator-map',
            'group' => 'plugins',
        ]
    );

    $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$cType] = 'mystorelocator-map';

    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;general,
                --palette--;;headers,
            --div--;' . $lll . 'tab.map,
                pi_flexform,
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                --palette--;;frames,
                --palette--;;appearanceLinks,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                --palette--;;language,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,
                --palette--;;access,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                rowDescription,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        ',
        'previewRenderer' => MapPreviewRenderer::class,
    ];

    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:my_store_locator/Configuration/FlexForms/Map.xml',
        $cType
    );
})();
