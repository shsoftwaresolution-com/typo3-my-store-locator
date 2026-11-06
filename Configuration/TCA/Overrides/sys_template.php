<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Static TypoScript for TYPO3 v12 (and v13 sites that use sys_template records instead of the site set)
ExtensionManagementUtility::addStaticFile(
    'my_store_locator',
    'Configuration/TypoScript/',
    'My Store Locator'
);
