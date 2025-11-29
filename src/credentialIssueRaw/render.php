<?php
/**
 * Server-side rendering for raw issue block.
 */

global $_SESSION;

if ( session_status() === PHP_SESSION_NONE ) {
    session_start();
}

if ( ! function_exists( 'getByPath' ) ) {
    function getByPath( array $arr, string $path, string $separators = './' ) {
        $parts = preg_split( '/[' . preg_quote( $separators, '/' ) . ']+/', $path, -1, PREG_SPLIT_NO_EMPTY );
        if ( ! $parts ) {
            return null;
        }

        $ref = $arr;
        foreach ( $parts as $p ) {
            if ( ! is_array( $ref ) || ! array_key_exists( $p, $ref ) ) {
                return null;
            }
            $ref = $ref[ $p ];
        }

        return $ref;
    }
}

if ( ! function_exists( 'setByPath' ) ) {
    function setByPath( array &$arr, string $path, $value, string $separators = './' ): void {
        $parts = preg_split( '/[' . preg_quote( $separators, '/' ) . ']+/', $path, -1, PREG_SPLIT_NO_EMPTY );
        if ( ! $parts ) {
            return;
        }

        $ref  =& $arr;
        $last = array_pop( $parts );

        foreach ( $parts as $p ) {
            if ( ! isset( $ref[ $p ] ) || ! is_array( $ref[ $p ] ) ) {
                $ref[ $p ] = [];
            }
            $ref =& $ref[ $p ];
        }

        $ref[ $last ] = $value;
    }
}

$claims = [];

if ( isset( $attributes['credentialData'] ) && ! empty( $attributes['credentialData'] ) ) {
    $decoded = json_decode( $attributes['credentialData'], true );
    if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
        $claims = $decoded;
    }
}

if ( isset( $attributes['sessionData'][0] ) || isset( $attributes['sessionData'] ) ) {
    $sessionData = is_string( $attributes['sessionData'] )
        ? json_decode( $attributes['sessionData'] )
        : $attributes['sessionData'];

    if ( json_last_error() === JSON_ERROR_NONE || is_array( $sessionData ) ) {
        if ( isset( $_SESSION['presentationResponse'] ) ) {
            $presentationResponse = $_SESSION['presentationResponse'];

            foreach ( $sessionData as $item ) {
                if ( ! isset( $item->key, $item->mapping, $item->type ) ) {
                    continue;
                }
                $type    = $item->type;
                $mapping = $item->mapping;

                $val = null;
                if ( isset( $presentationResponse[ $type ]['claims'] ) && is_array( $presentationResponse[ $type ]['claims'] ) ) {
                    $val = getByPath( $presentationResponse[ $type ]['claims'], (string) $mapping );
                }

                if ( null !== $val ) {
                    setByPath( $claims, (string) $item->key, $val );
                }
            }
        } else {
            echo '<div ' . get_block_wrapper_attributes() . '><p>Er kunnen geen credentials opgehaald worden.</p></div>';
            return;
        }
    } else {
        echo '<div ' . get_block_wrapper_attributes() . '><p>SessionData is niet geldig.</p></div>';
        return;
    }
}

do_action( 'wp_enqueue_script' );

$html = '';
$form = false;
$show_credential = array_key_exists( 'showCredential', $attributes ) ? (bool) $attributes['showCredential'] : true;

if ( isset( $attributes['formData'] ) && ! empty( $attributes['formData'] ) ) {
    $formData = json_decode( $attributes['formData'] );
    if ( json_last_error() === JSON_ERROR_NONE && ( is_object( $formData ) || is_array( $formData ) ) ) {
        $form = true;
        $html .= '<form class="mt-4 d-block" id="openid4vci-raw-form">';
        foreach ( $formData as $key => $value ) {
            $html .= '<div class="form-input mb-3">';
            $html .= '<label class="d-block mb-2"><strong>' . esc_html( $value ) . '</strong></label>';
            $html .= '<input type="text" class="input--standard" name="' . esc_attr( $key ) . '" placeholder="' . esc_attr( $value ) . '">';
            $html .= '</div>';
        }
        $html .= '<input type="hidden" name="rawissuerequest" value="1">';
        $html .= '<button type="submit" class="btn btn-primary btn-sm">' . esc_html( $attributes['buttonLabel'] ?? __( 'Genereer credential', 'openid4vc-issue' ) ) . '</button>';
        $html .= '</form>';
    }
}


$issue_and_render = function () use ( &$claims, $attributes, $show_credential ) {
    $response = sendRawIssueRequest( $claims, $attributes );

    if ( $response['success'] === false ) {
        echo $response['error'];
        return true;
    }

    $result = $response['result'];
    if ( isset( $result->credential ) ) {
        if ( is_string( $result->credential ) ) {
            $payload = $result->credential;
        } elseif ( is_object( $result->credential ) && isset( $result->credential->token ) && is_string( $result->credential->token ) ) {
            $payload = $result->credential->token;
        } else {
            $payload = wp_json_encode( $result->credential, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
        }
    } elseif ( isset( $result->token ) && is_string( $result->token ) ) {
        $payload = $result->token;
    } else {
        $payload = wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
    }

    error_log( '[openid4vci] rawIssue credential response: ' . $payload );
    $_SESSION['RawCredential'] = $payload;

    $block_content = '<div ' . get_block_wrapper_attributes() . '>';
    if ( $show_credential ) {
        $block_content .= '<p>' . __( 'SD-JWT credential:', 'openid4vc-issue' ) . '</p>'
            . '<textarea readonly style="width:100%;min-height:180px;">' . esc_textarea( $payload ) . '</textarea>';
    }
    $block_content .= '</div>';

    echo $block_content;

    return true;
};

if ( isset( $_GET['rawissuerequest'] ) ) {
    $originalFormKeys = [];
    if ( isset( $attributes['formData'] ) && ! empty( $attributes['formData'] ) ) {
        $decodedForm = json_decode( $attributes['formData'] );
        if ( $decodedForm && ( is_object( $decodedForm ) || is_array( $decodedForm ) ) ) {
            foreach ( $decodedForm as $k => $label ) {
                $originalFormKeys[] = (string) $k;
            }
        }
    }

    foreach ( $_GET as $name => $value ) {
        if ( 'rawissuerequest' === $name ) {
            continue;
        }

        $originalKey = $name;
        foreach ( $originalFormKeys as $formKey ) {
            if ( $formKey === $name ) {
                $originalKey = $formKey;
                break;
            }
            if ( str_replace( array( '.', '/' ), '_', $formKey ) === $name ) {
                $originalKey = $formKey;
                break;
            }
        }

        setByPath( $claims, $originalKey, sanitize_text_field( wp_unslash( $value ) ) );
    }

    $issue_and_render();
} elseif ( $form ) {
    echo '<div ' . get_block_wrapper_attributes() . '>' . $html . '</div>';
} else {
    $submit_label = esc_html( $attributes['buttonLabel'] ?? __( 'Genereer credential', 'openid4vc-issue' ) );
    echo '<div ' . get_block_wrapper_attributes() . '>'
        . '<form class="mt-4 d-block" id="openid4vci-raw-form">'
        . '<input type="hidden" name="rawissuerequest" value="1">'
        . '<button type="submit" class="btn btn-primary btn-sm">' . $submit_label . '</button>'
        . '</form>'
        . '</div>';
}
