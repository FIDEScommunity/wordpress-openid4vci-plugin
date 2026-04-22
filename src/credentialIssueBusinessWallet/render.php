<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $_SESSION;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('openid4vci_set_by_path')) {
    function openid4vci_set_by_path(array &$arr, string $path, $value, string $separators='./'): void {
        $parts = preg_split('/[' . preg_quote($separators, '/') . ']+/', $path, -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) return;

        $ref =& $arr;
        $last = array_pop($parts);

        foreach ($parts as $p) {
            if (!isset($ref[$p]) || !is_array($ref[$p])) {
                $ref[$p] = [];
            }
            $ref =& $ref[$p];
        }
        $ref[$last] = $value;
    }
}

$claims = [];

if (isset($attributes['credentialData']) && !empty($attributes['credentialData'])) {
    $decoded = json_decode($attributes['credentialData'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $claims = $decoded;
    }
}

if (isset($attributes['sessionData'][0]) || isset($attributes['sessionData'])) {
    $sessionData = is_string($attributes['sessionData'])
        ? json_decode($attributes['sessionData'])
        : $attributes['sessionData'];

    if (json_last_error() === JSON_ERROR_NONE || is_array($sessionData)) {
        if (isset($_SESSION['presentationResponse'])) {
            // $_SESSION['presentationResponse'] is populated by the companion OpenID4VP verifier flow on the same site; not external user input.
            $presentationResponse = $_SESSION['presentationResponse']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

            foreach ($sessionData as $item) {
                if (!isset($item->key, $item->mapping, $item->type)) {
                    continue;
                }
                $type = $item->type;
                $mapping = $item->mapping;

                if (isset($presentationResponse[$type]['claims'][$mapping])) {
                    $val = $presentationResponse[$type]['claims'][$mapping];

                    openid4vci_set_by_path($claims, (string)$item->key, $val);
                }
            }
        } else {
            $block_content = '<div ' . get_block_wrapper_attributes() . '><p>' . esc_html__( 'Er kunnen geen credentials opgehaald worden.', 'universal-oid4vci' ) . '</p></div>';
            echo $block_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }
    } else {
        $block_content = '<div ' . get_block_wrapper_attributes() . '><p>' . esc_html__( 'SessionData is niet geldig.', 'universal-oid4vci' ) . '</p></div>';
        echo wp_kses_post( $block_content );
        return;
    }
}

do_action( 'wp_enqueue_script' );

$html = '';
$form = false;

if (isset($attributes['formData']) && !empty($attributes['formData'])) {
    $formData = json_decode($attributes['formData']);
    $form = true;
    $html .= '<form class="mt-4 d-block"  id="OpenID4VCI-form">';
    foreach ($formData as $key => $value) {
        $html .= '<div class="form-input mb-3">';
        $html .= '<label class="d-block mb-2"><strong>' . esc_html( $value ) . '</strong></label>';
        $html .= '<input type="text" class="input--standard" name="' . esc_attr( $key ) . '" placeholder="' . esc_attr( $value ) . '">';
        $html .= '</div>';
    }
    $html .= '<input type="hidden" name="qrrequest">';
    $html .= '<input type="hidden" name="openid4vci_nonce" value="' . esc_attr( wp_create_nonce( 'openid4vci_issue' ) ) . '">';
    $html .= '<div class="form-input mb-3"><label class="d-block mb-2"><strong>' . esc_html__( 'Wallet URL', 'universal-oid4vci' ) . '</strong></label><input type="text" id="business-wallet-url" name="walletUrl" placeholder="' . esc_attr__( 'Enter wallet URL', 'universal-oid4vci' ) . '" /></div>';
    $html .= '<button type="submit" class="btn btn-primary btn-sm">' . esc_html__( 'Connect to wallet', 'universal-oid4vci' ) . '</button>';
    $html .= '</form>';
}

if ( isset( $_GET['qrrequest'] ) ) {
    $nonce = isset( $_GET['openid4vci_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['openid4vci_nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'openid4vci_issue' ) ) {
        $block_content = '<div ' . get_block_wrapper_attributes() . '><p>' . esc_html__( 'Security check failed.', 'universal-oid4vci' ) . '</p></div>';
        echo $block_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return;
    }
    foreach ( $_GET as $name => $value ) {
        if ( $name !== 'qrrequest' && $name !== 'walletUrl' && $name !== 'openid4vci_nonce' ) {
            $claims[ sanitize_text_field( wp_unslash( $name ) ) ] = sanitize_text_field( wp_unslash( $value ) );
        }
    }
    $response = openid4vci_send_vci_request($claims, $attributes);

    if ($response["success"] === false) {
        echo $response["error"]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return;
    }
    do_action( 'wp_enqueue_script' );

    if (!headers_sent()) {
        wp_safe_redirect( $response["result"]->request_uri );
        exit;
    } else {
        $block_content = '<script>window.location.replace("' . esc_js( $response["result"]->request_uri ) . '")</script>';
    }
} elseif ($form) {
    $block_content = '<div ' . get_block_wrapper_attributes() . '>' . $html . '</div>';
} elseif ( ! isset( $_GET['walletUrl'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- walletUrl arrives via external wallet redirect; presence check only.
    $block_content = '<form class="mt-4 d-block" id="OpenID4VCI-form"><div ' . get_block_wrapper_attributes() . '><input type="text" id="business-wallet-url" name="walletUrl" placeholder="' . esc_attr__( 'Enter wallet URL', 'universal-oid4vci' ) . '" />
            <button type="submit" class="btn btn-primary btn-sm">' . esc_html__( 'Connect to wallet', 'universal-oid4vci' ) . '</button></div></form>';
} else {
    $response = openid4vci_send_vci_request($claims, $attributes);

    if ($response["success"] === false) {
        echo $response["error"]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return;
    }

    if (!headers_sent()) {
        wp_safe_redirect( $response["result"]->request_uri );
        exit;
    } else {
        $block_content = '<script>window.location.replace("' . esc_js( $response["result"]->request_uri ) . '")</script>';
    }
}

if ( isset( $block_content ) ) {
    echo $block_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
