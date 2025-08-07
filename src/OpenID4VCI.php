<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class OpenID4VCI {
    public $version = '1.0.0';

    public static $_instance = null;

    protected $default_settings = [
        'openidEndpoint'             => '',
        'authenticationHeaderName'   => 'x-api-key',
        'authenticationToken'        => ''
    ];

    public function __construct() {
        add_action('init', [__CLASS__, 'includes']);
    }

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public static function includes() {
        require_once(OPENID4VCI_PLUGIN_DIR . 'build/adminSettings/openid4vci-admin-options.php');
        require_once(OPENID4VCI_PLUGIN_DIR . 'build/adminSettings/openid4vci-admin-settings.php');
    }

    public function setup() {
        $admin_options = get_option('openid4vci_options');

        if (!isset($admin_options['openidEndpoint'])) {
            update_option('openid4vci_options', $this->default_settings);
        }

        $this->install();
    }

    public function logout() {
        wp_redirect(home_url());
        exit();
    }

    public function wp_enqueue() {
        // Registers the script if $src provided (does NOT overwrite), and enqueues it.
        wp_enqueue_script('jquery-ui-accordion');
    }

    public function plugin_init() {
    }

    public function install() {
    }

    public function upgrade() {
    }
}
