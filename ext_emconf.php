<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'My Store Locator',
    'description' => 'Embed your My Store Locator store finder / location map as a content element. GDPR click-to-load, hosted in Germany.',
    'category' => 'fe',
    'author' => 'Sajjad Hanifa',
    'author_email' => 'info@my-store-locator.com',
    'author_company' => 'SH Software Solution / Internet am Dornbusch',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'php' => '8.1.0-8.4.99',
            'typo3' => '12.4.0-13.4.99',
            'fluid_styled_content' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Shss\\MyStoreLocator\\' => 'Classes/',
        ],
    ],
];
