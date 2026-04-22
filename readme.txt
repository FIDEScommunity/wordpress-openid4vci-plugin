=== Universal OID4VCI ===
Contributors:      credenco
Tags:              openid4vci, verifiable-credentials, identity, wallet, gutenberg
Tested up to:      6.9
Stable tag:        0.4.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Issue verifiable credentials using the universal OID4VCI interface with a business wallet.

== Description ==

Universal OID4VCI provides Gutenberg blocks to issue verifiable credentials via the OpenID for Verifiable Credential Issuance (OID4VCI) flow, connecting a WordPress site to a business wallet.

Features:

* Credential Issue block for QR-code based issuance.
* Credential Issue (Business Wallet) block for direct wallet URL flows.
* Admin settings for the OID4VCI endpoint and authentication header.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/universal-oid4vci` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the OID4VCI endpoint and authentication token under Settings -> Universal OID4VCI.

== Frequently Asked Questions ==

= Which wallets are supported? =

Any wallet that implements the OpenID for Verifiable Credential Issuance specification.

== Changelog ==

= 0.4.0 =
* Updated build and metadata.
* Escaped output and added direct-access guards for wordpress.org compliance.

= 0.2.0 =
* Release.

= 0.1.0 =
* Initial release.
