# My Store Locator for TYPO3

TYPO3 extension (key `my_store_locator`, composer `shss/my-store-locator`) that adds a content element
**"My Store Locator map"**: paste your map token (or embed code, or the public map link) and your store finder /
location map from [My Store Locator](https://my-store-locator.com/?utm_source=typo3&utm_medium=integration&utm_campaign=typo3-plugin)
appears on the page. All data, the map itself and the look (templates, colours) stay in the My Store Locator app –
the extension is a thin connector around the official `widget.js`.

- TYPO3 **12.4 LTS** and **13.4 LTS**, PHP 8.1 – 8.4, requires `fluid_styled_content`
- Content element with New Content Element Wizard entry (group "Plugins"), backend page-module preview, icon
- Per element: map token, height (`auto` or CSS height), language (auto / de / en), consent mode (GDPR click-to-load),
  lazy loading, sticky-header offset, layout (standard / split)
- Defaults per site: Site Set settings (v13) or TypoScript constants (v12 and v13)
- Optional **"Connect with My Store Locator"** button next to the token field (popup, picks a map, fills the field)
- English and German backend labels
- No tracking, no requests to our servers from the TYPO3 backend or server; only the visitor's browser loads the map

No account yet? [Sign up free](https://app.my-store-locator.com/#/register?utm_source=typo3) – plans Free, Starter ($20/month),
Pro ($50/month), Business (on request).

## Installation

### Composer mode (recommended)

```bash
composer require shss/my-store-locator
vendor/bin/typo3 extension:setup
```

### Classic mode (TER / zip)

Admin Tools → Extensions → "Get Extensions" → search `my_store_locator` → install,
or upload the release zip (`my_store_locator_<version>.zip`) with "Upload Extension". Activate the extension.

### Include the TypoScript

- **TYPO3 13 (site sets):** Site Management → Sites → edit your site → "Sets for this site" → add **My Store Locator**
  (`shss/my-store-locator`; it pulls in Fluid Styled Content). Or in `config/sites/<site>/config.yaml`:
  ```yaml
  dependencies:
    - shss/my-store-locator
  ```
- **TYPO3 12 (or v13 with sys_template records):** Site Management → TypoScript → edit the root template →
  "Include TypoScript sets" / "Include static (from extensions)" → add **My Store Locator (my_store_locator)**
  after **Fluid Content Elements (fluid_styled_content)**.

## Usage

1. In My Store Locator open your map → **Embed** and copy the map token, the embed code or the public map link.
2. In TYPO3: Page module → **+ Content** → tab **Plugins** → **My Store Locator map**.
3. Tab **Map**: paste into **Map token** (any of the three formats; the token is extracted and checked:
   `^[A-Za-z0-9_-]{6,64}$`). Or click the button next to the field (see "Connect" below). Save.

Elements with an empty or invalid token render nothing for visitors; the page module shows a hint instead.

### Settings

| Setting | FlexForm (per element) | Default (site setting / TypoScript constant) |
|---|---|---|
| Map token | `settings.token` | – (required) |
| Height | `settings.height` – `auto`, `640`, `640px`, `80vh` … | `plugin.tx_mystorelocator.height = auto` |
| Language | `settings.language` – default / auto / de / en | `plugin.tx_mystorelocator.language = auto` (auto: site language `de*` → de, otherwise en) |
| Consent mode | `settings.consent` – default / on / off | `plugin.tx_mystorelocator.consent = 1` |
| Lazy loading | `settings.lazy` – default / on / off | `plugin.tx_mystorelocator.lazy = 1` |
| Sticky header offset | `settings.offset` (px) | `plugin.tx_mystorelocator.offset = 0` |
| Layout | `settings.layout` – default / standard / split | `plugin.tx_mystorelocator.layout = default` |

An empty element value means "use the default". In TYPO3 13 the defaults are edited in
Site Management → Settings (category "My Store Locator") or in `config/sites/<site>/settings.yaml`:

```yaml
plugin:
  tx_mystorelocator:
    height: auto
    consent: true
    offset: 80
```

In TYPO3 12 use the Constant Editor (category `plugin.tx_mystorelocator`) or TypoScript constants.

### Output

Per element (attributes escaped). **Consent mode off** – a normal widget container, widget.js once per page:

```html
<div class="msl-map" id="msl-map-c12" data-mystorelocator data-token="TOKEN" data-height="auto" data-lang="de" data-lazy="true"></div>
<script async id="msl-widget-js" src="https://app.my-store-locator.com/widget.js"></script>   <!-- AssetCollector id "msl-widget" -->
```

**Consent mode on (strict)** – a gated container without the widget's mount markers, a click-to-load placeholder rendered
by TYPO3 (text in the map language) and only the local script `Resources/Public/JavaScript/msl-consent.js`
(AssetCollector id "msl-consent", once per page). Nothing is requested from app.my-store-locator.com before the click:

```html
<div class="msl-map-consent" data-msl-gate id="msl-map-c12" data-token="TOKEN" data-height="auto" data-lang="de" data-lazy="true">
  <div class="msl-consent" style="height:760px;…"><p>Diese Karte wird von my-store-locator.com geladen …</p>
    <button type="button" class="msl-consent__load">Karte laden</button></div>
</div>
<script defer src="/_assets/<hash>/JavaScript/msl-consent.js"></script>
```

On click, msl-consent.js turns every gated map on the page into a normal container (`class="msl-map"` +
`data-mystorelocator`) and injects widget.js, which mounts them. Pages with consent-on and consent-off maps work together.
The placeholder is 760px high for height `auto`, otherwise the configured height.

`data-offset` is only written when > 0, `data-layout="split"` only when chosen. The widget currently mounts
`#mystorelocator,[data-mystorelocator]`; `class="msl-map"` is written for the coming class-based mounting.

The Fluid template is `EXT:my_store_locator/Resources/Private/Templates/ContentElements/MyStoreLocatorMap.html`
(override via `tt_content.mystorelocator_map.templateRootPaths.300`). The variable `{mslMap}` holds
`valid`, `token`, `height`, `language`, `consent`, `lazy`, `offset`, `layout`, `id`, `attributes`, `attributeString`,
`placeholderHeight`.

### Connect (optional)

The button next to the token field opens
`https://app.my-store-locator.com/connect?platform=typo3&return_url=<…>&state=<random>` in a popup. After login and
choosing a map, the app sends the browser to the return page shipped with the extension
(`Resources/Public/Connect/return.html`, served at `/_assets/<hash>/Connect/return.html` in composer mode or
`/typo3conf/ext/my_store_locator/Resources/Public/Connect/return.html` in classic mode). That page posts the token to the
backend window (same origin), the state is checked (sessionStorage) and the field is filled. Map tokens are public,
no secret is exchanged.

Pasting the token manually always works as well.
The return URL is `https` when the backend runs on https; on plain-http dev backends the app may refuse it.

## Privacy (GDPR)

- The map is provided by the external service **app.my-store-locator.com** (operator: Internet am Dornbusch,
  Inh. Sajjad Hanifa, Frankfurt am Main; hosted in Germany).
- When the map loads, the visitor's browser connects to app.my-store-locator.com and transmits the visitor's
  **IP address** (technically necessary) plus the usual request data. Map tiles and address search run through
  My Store Locator's own servers.
- **Consent mode** (default: on) is strict two-click: the page shows a placeholder with a privacy notice and loads
  nothing from app.my-store-locator.com – no script, no frame – until the visitor clicks "Karte laden" / "Load map".
  Only the extension's own small script (served by your TYPO3 site) runs before that. The click counts as consent for all
  maps on that page.
- The extension itself sets no cookies, does no tracking and makes no requests from the TYPO3 server ("no phoning home").
- Mention My Store Locator in your privacy policy. Details:
  [Datenschutz (de)](https://my-store-locator.com/datenschutz/) · [Privacy (en)](https://my-store-locator.com/en/privacy/).
  A data processing agreement (Art. 28 GDPR) is available in the app.

## Development and testing

Folder layout:

```
Classes/Service/TokenParser.php                 token extraction + validation
Classes/Service/MapConfigurationResolver.php    FlexForm + defaults → widget config / attributes
Classes/DataProcessing/MapProcessor.php         DataProcessor for the Fluid template, adds widget.js or msl-consent.js once
Classes/Frontend/TokenCondition.php             renders nothing without a valid token
Classes/Backend/Preview/MapPreviewRenderer.php  page module preview
Classes/Form/FieldControl/ConnectControl.php    "Connect" button (FormEngine fieldControl)
Configuration/TCA/Overrides/tt_content.php      CType mystorelocator_map, FlexForm, wizard item (v13)
Configuration/TsConfig/Page/…                   wizard item for v12
Configuration/Sets/MyStoreLocator/              v13 site set (+ settings.definitions.yaml)
Configuration/TypoScript/                       static TypoScript (v12) – also imported by the set
Resources/Public/Connect/return.html            connect return page
Resources/Public/JavaScript/connect.js          backend ES module for the connect button
Resources/Public/JavaScript/msl-consent.js      strict consent gate (shared file, copy of tools/msl-consent.js)
bin/build-release.sh                            builds dist/my_store_locator_<version>.zip (TER upload)
```

Local test (as done for 1.0.0, Docker, sqlite, PHP built-in server; ports 127.0.0.1:58110 = v13, 58111 = v12):

```bash
# image: php:8.3-cli + intl, zip, gd + composer
composer.json  → "repositories": [{"type": "path", "url": "/ext"}], "require": {"shss/my-store-locator": "@dev", typo3/cms-* ^13.4}
vendor/bin/typo3 setup --driver=sqlite …   # then add the set to the site (v13) or the static template (v12)
php -S 0.0.0.0:8080 -t public
```

Tested 2026-10-25 with TYPO3 13.4.37 (composer mode and classic mode from the release zip) and 12.4.45 (composer), PHP 8.3: frontend output (raw token, pasted widget code, pasted
iframe code, public map URL, invalid token → nothing, `de`/`en` auto language from the site language, site settings
defaults incl. bool `false`, script once per page, cached pages), page-module preview + invalid-token hint, wizard entry, FlexForm, connect button
(app side simulated with a Playwright route, return page → postMessage → field filled; return URL correct in composer
(`/_assets/…`) and classic mode (`/typo3conf/ext/…`)), saving the element via the backend form. Screenshots: `docs/screenshots/`.

Strict consent, network log (Playwright, all requests recorded), TYPO3 13.4.37 and 12.4.45:
consent on (EN and DE) → 0 requests to *.my-store-locator.com before the click; after the click widget.js (1×) and the
map iframe load. Consent off → widget.js and the map load directly. Page with two consent-off maps and one consent-on map →
the consent-off maps load directly, after the click the gated map mounts too (3/3 containers mounted, no gate left).
Connect re-test: opener path (popup navigates itself to the return page) and BroadcastChannel path (return page opened
without `window.opener`) both fill the field; a forged `state` is ignored.

Note: TYPO3 12 community releases are past their free support period; composer blocks them because of security
advisories unless you use ELTS. The extension still supports 12.4 for ELTS users.

Release: `bin/build-release.sh` → `dist/my_store_locator_<version>.zip` (dist/ is gitignored). Releases are published on GitHub, Packagist (`shss/my-store-locator`) and the TYPO3 Extension Repository.

Support: info@my-store-locator.com · Website: https://my-store-locator.com/

## License

GPL-2.0-or-later (see LICENSE.txt). © SH Software Solution / Internet am Dornbusch, Sajjad Hanifa.
