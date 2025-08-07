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
    if(!session_id()) {
        session_start();
    }
}

add_action( 'init', 'create_block_openid4vci_block_init' );
// Add an action to call our script enqueuing function
add_action( 'wp_enqueue_script', 'enqueue_my_scripts' );
