=== Universal OID4VCI ===
Contributors:      credenco
Tags:              openid4vci, verifiable-credentials, identity, wallet, gutenberg
Requires at least: 6.6
Tested up to:      6.9
Requires PHP:      7.2
Stable tag:        0.4.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Issue verifiable credentials using the OpenID for Verifiable Credential Issuance (OID4VCI) protocol with a business wallet.

== Description ==

Universal OID4VCI provides Gutenberg blocks that let a WordPress site kick off a credential issuance flow against any OID4VCI-compliant business wallet. Visitors can receive verifiable credentials directly into their personal wallet by scanning a QR code or by entering a wallet URL.

Features:

* **Credential Issue** block — starts a QR-code based credential issuance flow for personal wallets.
* **Credential Issue (Business Wallet)** block — starts a direct wallet-URL flow for business-to-business issuance.
* Admin settings for the OID4VCI endpoint and API authentication header.
* Configurable QR code appearance (size, colors, padding).
* Optional form fields so the visitor can supply claim values before issuance.

== External services ==

This plugin connects to an external OID4VCI credential-issuance endpoint (a "business wallet") whose URL is configured by the site administrator in **Settings → Universal OID4VCI**. The plugin does not connect to any endpoint until the administrator configures one and a visitor interacts with a block.

What is sent, and when:

* When a visitor submits a credential-issue block, the plugin sends an HTTPS POST request to the configured endpoint. The request includes:
  * The `template_id` configured on the block.
  * Any claim values that the visitor entered in the block's form fields, or that the administrator configured via the block's `credentialData` attribute.
  * Optional QR-code rendering parameters (size, colors) when the QR output is enabled.
  * The configured authentication header and token.
* The plugin does not send any WordPress user data, cookies, IP addresses, or other personal data beyond what is listed above.
* The endpoint returns a `qr_uri` and `request_uri` which the plugin renders on the page so the visitor can complete the issuance in their wallet.

The endpoint is operated by the wallet provider that the site administrator has chosen. Review that provider's terms of service and privacy policy before enabling the plugin on a production site.

Example wallet service (not affiliated with this plugin unless configured by the site owner):

* Credenco Business Wallet — https://www.credenco.com — terms: https://www.credenco.com/terms — privacy: https://www.credenco.com/privacy

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/universal-oid4vci` directory, or install the plugin through the WordPress Plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings → Universal OID4VCI** and configure:
   * **OID4VCI Endpoint** — the issuance endpoint URL of your business wallet.
   * **Authentication header** — the header name your wallet expects (default `x-api-key`).
   * **Authentication token** — the API key / secret for your wallet.
4. Add the **Credential Issue** or **Credential Issue (Business Wallet)** block to any page or post and set a `credentialIssueTemplateKey` that exists on your wallet.

== Frequently Asked Questions ==

= Which wallets are supported? =

Any wallet that implements the OpenID for Verifiable Credential Issuance specification and accepts the plugin's request shape.

= Does this plugin share data with third parties? =

Only with the OID4VCI endpoint configured by the site administrator, and only when a visitor interacts with a block. See the **External services** section above.

= Does the plugin store personal data locally? =

No. The plugin stores only the endpoint URL and API credentials that the administrator enters on the settings page.

== Screenshots ==

1. Universal OID4VCI settings page under **Settings → Universal OID4VCI**.
2. A page with the Credential Issue block rendering a QR code that opens the visitor's wallet.

== Changelog ==

= 0.4.0 =
* Renamed plugin to "Universal OID4VCI".
* Aligned text domain to `universal-oid4vci` across PHP and block editor files.
* Added `load_plugin_textdomain` hook and a `.pot` translation template.
* Added nonce verification on the credential-issue form handlers.
* Added `ABSPATH` direct-access guards.
* Hardened output escaping for wordpress.org compliance.
* Documented external service usage in readme.

= 0.2.0 =
* Release.

= 0.1.0 =
* Initial release.

== Upgrade Notice ==

= 0.4.0 =
Plugin slug and text domain have changed to `universal-oid4vci`. Review your settings after upgrading.
