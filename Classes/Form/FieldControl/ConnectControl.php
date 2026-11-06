<?php

declare(strict_types=1);

namespace Shss\MyStoreLocator\Form\FieldControl;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * FormEngine field control next to the map token field: "Connect with My Store Locator".
 * Opens the app's connect page in a popup; the shipped return page hands the selected map token back
 * (postMessage, same origin) and the JavaScript module fills the field. The manual token input always works.
 */
final class ConnectControl extends AbstractNode
{
    public const CONNECT_URL = 'https://app.my-store-locator.com/connect';

    public function render(): array
    {
        $returnUrl = self::returnUrl($GLOBALS['TYPO3_REQUEST'] ?? null);
        $connectUrl = self::CONNECT_URL . '?platform=typo3&return_url=' . rawurlencode($returnUrl);

        return [
            'iconIdentifier' => 'mystorelocator-connect',
            'title' => 'LLL:EXT:my_store_locator/Resources/Private/Language/locallang_db.xlf:connect.button',
            'linkAttributes' => [
                'class' => 'msl-connect-button',
                'data-msl-connect' => $connectUrl,
                'data-msl-field' => (string)($this->data['parameterArray']['itemFormElName'] ?? ''),
            ],
            'javaScriptModules' => [
                JavaScriptModuleInstruction::create('@shss/my-store-locator/connect.js'),
            ],
        ];
    }

    /**
     * Absolute URL of Resources/Public/Connect/return.html (composer mode: /_assets/<hash>/…, classic mode: /typo3conf/ext/…).
     */
    public static function returnUrl(?ServerRequestInterface $request): string
    {
        $path = PathUtility::getPublicResourceWebPath('EXT:my_store_locator/Resources/Public/Connect/return.html');
        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }
        $params = $request?->getAttribute('normalizedParams');
        if (!$params instanceof NormalizedParams) {
            return $path;
        }
        if (str_starts_with($path, '/')) {
            return $params->getRequestHost() . $path;
        }
        return $params->getSiteUrl() . ltrim($path, '/');
    }
}
