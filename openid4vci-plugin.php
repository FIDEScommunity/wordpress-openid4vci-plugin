<?php
/**
 * Plugin Name:       Universal OID4VCI
 * Description:       Issue verifiable credentials using the universal OID4VCI interface with a business wallet.
 * Version:           0.4.0
 * Requires at least: 6.6
 * Requires PHP:      7.2
 * Author:            Credenco B.V.
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       universal-oid4vci
 *
 * @package           create-block
 */

if ( ! defined( 'ABSPATH' ) ) {
   exit; // Exit if accessed directly.
}

if ( ! defined( 'OPENID4VCI_PLUGIN_URL' ) ) {
   define( 'OPENID4VCI_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
}
if (!defined('OPENID4VCI_PLUGIN_DIR')) {
    define('OPENID4VCI_PLUGIN_DIR', trailingslashit(plugin_dir_path(__FILE__)));
}

require_once(OPENID4VCI_PLUGIN_DIR . 'build/OpenID4VCI.php');

$openid4vci = new OpenID4VCI();

add_action('admin_menu', [$openid4vci, 'plugin_init']);
add_action('wp_logout', [$openid4vci, 'logout']);

register_activation_hook(__FILE__, [$openid4vci, 'setup']);
register_activation_hook(__FILE__, [$openid4vci, 'upgrade']);

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function openid4vci_block_init() {
   register_block_type( __DIR__ . '/build/credentialIssue' );
   register_block_type( __DIR__ . '/build/credentialIssueBusinessWallet' );

   // Backwards-compat alias: prior plugin versions registered the business-wallet block as
   // 'openid4vci-plugin/openid4vc-issue-organisation-wallet'. Existing posts still carry that
   // name in their block comments, so register it as an alias that renders via the same file.
   $business_metadata_path = __DIR__ . '/build/credentialIssueBusinessWallet/block.json';
   if ( file_exists( $business_metadata_path ) ) {
       $business_metadata = json_decode( file_get_contents( $business_metadata_path ), true );
       register_block_type(
           'openid4vci-plugin/openid4vc-issue-organisation-wallet',
           array(
               'attributes'      => ( is_array( $business_metadata ) && isset( $business_metadata['attributes'] ) ) ? $business_metadata['attributes'] : array(),
               'render_callback' => function ( $attributes, $content, $block ) {
                   ob_start();
                   require __DIR__ . '/build/credentialIssueBusinessWallet/render.php';
                   return ob_get_clean();
               },
           )
       );
   }

    if(!session_id()) {
        session_start();
    }
}

add_action( 'init', 'openid4vci_block_init' );
// Add an action to call our script enqueuing function
//add_action( 'wp_enqueue_script', 'enqueue_my_scripts' );

function openid4vci_send_vci_request($claims, $attributes) {
    $options = new OpenID4VCI_Admin_Options();
    $openidEndpoint = $options->openidEndpoint;
    $authenticationHeaderName = $options->authenticationHeaderName;
    $authenticationToken = $options->authenticationToken;
    if (!empty($attributes['openidEndpoint'])) {
        $openidEndpoint = $attributes['openidEndpoint'];
        $authenticationHeaderName = $attributes['authenticationHeaderName'];
        $authenticationToken = $attributes['authenticationToken'];
    }

    $params = [];
    $params['claims'] = $claims;
    $params['template_id'] = $attributes['credentialIssueTemplateKey'];
    // walletUrl arrives via external wallet redirect; nonce verification is not possible. Value is sanitized and only reflected into the outbound wallet request.
    if ( isset( $_GET['walletUrl'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $params['request_uri_base'] = sanitize_url( wp_unslash( $_GET['walletUrl'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }
    if (isset($attributes['qrCodeEnabled']) && $attributes['qrCodeEnabled']) {
        $qrCode = (object)[];
        if (array_key_exists('qrSize', $attributes) && !empty($attributes['qrSize'])) {
            $qrCode->size = $attributes['qrSize'];
        }
        if (array_key_exists('qrColorDark', $attributes) && !empty($attributes['qrColorDark'])) {
            $qrCode->color_dark = $attributes['qrColorDark'];
        }
        if (array_key_exists('qrColorLight', $attributes) && !empty($attributes['qrColorLight'])) {
            $qrCode->color_light = $attributes['qrColorLight'];
        }
        if (array_key_exists('qrPadding', $attributes) && !empty($attributes['qrPadding'])) {
            $qrCode->padding = $attributes['qrPadding'];
        }
        $params['qr_code'] = $qrCode;
    }

    if ( isset( $_GET['walletUrl'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $params['request_uri_base'] = sanitize_url( wp_unslash( $_GET['walletUrl'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }
    $credentialData = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

   $response = wp_remote_post( $openidEndpoint, array(
       'headers' => array('Content-Type' => 'application/json', $authenticationHeaderName => $authenticationToken),
       'timeout'     => 45,
       'redirection' => 5,
       'blocking'    => true,
       'body'        => $credentialData
   ));

    if (is_wp_error($response)) {
        $block_content = '<div ' . get_block_wrapper_attributes() . '><p>Error fetching data</p></div>';
        return ["success" => false, "error" => $block_content];
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode( $body );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        $block_content = '<div ' . get_block_wrapper_attributes() . '><p>JSON decode fout: ' . esc_html( json_last_error_msg() ) . '</p></div>';
        return ["success" => false, "error" => $block_content];
    }

    if ( isset( $result->status ) && isset( $result->detail ) ) {
        $block_content = '<div ' . get_block_wrapper_attributes() . '><p>API fout: ' . esc_html( $result->detail ) . '</p></div>';
        return ["success" => false, "error" => $block_content];
    }

   return ["success" => true, "result" => $result];
}


