<?php

declare(strict_types=1);

use Shss\MyStoreLocator\Form\FieldControl\ConnectControl;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// "Connect with My Store Locator" button next to the map token field
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1761370001] = [
    'nodeName' => 'mslConnect',
    'priority' => 40,
    'class' => ConnectControl::class,
];

// TYPO3 v12: New Content Element Wizard entry via page TSconfig (v13 reads it from the TCA item)
if ((new Typo3Version())->getMajorVersion() < 13) {
    ExtensionManagementUtility::addPageTSConfig(
        "@import 'EXT:my_store_locator/Configuration/TsConfig/Page/ContentElementWizard.tsconfig'"
    );
}
