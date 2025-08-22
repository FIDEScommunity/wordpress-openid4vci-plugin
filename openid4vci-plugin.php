<?php
/**
 * Plugin Name:       Universal OID4VCI
 * Description:       Issue verifiable credentials using the universal OID4VCI interface with an organization wallet.
 * Version:           0.3.0
 * Requires at least: 6.6
 * Requires PHP:      7.2
 * Author:            Credenco B.V.
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       openid4vp-exchange
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
function create_block_openid4vci_block_init() {
   register_block_type( __DIR__ . '/build/credentialIssue' );
   register_block_type( __DIR__ . '/build/credentialIssueOrgWallet' );
    if(!session_id()) {
        session_start();
    }
}

add_action( 'init', 'create_block_openid4vci_block_init' );
// Add an action to call our script enqueuing function
//add_action( 'wp_enqueue_script', 'enqueue_my_scripts' );

function sendVciRequest($claims, $attributes) {
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
    if (isset($_GET['walletUrl'])) {
        $params['request_uri_base'] = $_GET['walletUrl'];
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

    if (isset($_GET['walletUrl'])) {
        $params['request_uri_base'] = $_GET['walletUrl'];
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
        $block_content = '<div ' . get_block_wrapper_attributes() . '><p>JSON decode fout: ' . json_last_error_msg().'</p></div>';
        return ["success" => false, "error" => $block_content];
    }

    // Controleer op fout in de API response zelf (bijv. foutcode of foutbericht)
    if ( isset( $result->status ) && isset( $result->detail ) ) {
        $block_content = '<div ' . get_block_wrapper_attributes() . '><p>API fout: ' . $result->detail.'</p></div>';
        return ["success" => false, "error" => $block_content];
    }

   return ["success" => true, "result" => $result];
}


