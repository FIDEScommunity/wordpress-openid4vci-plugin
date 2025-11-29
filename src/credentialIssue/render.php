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
// do a session a start
global $_SESSION;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: get a value from a nested array using a path like "credentialSubject.given_name"
if (!function_exists('getByPath')) {
    function getByPath(array $arr, string $path, string $separators = './') {
        // Split on "." or "/"
        $parts = preg_split('/[' . preg_quote($separators, '/') . ']+/', $path, -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return null;
        }

        $ref = $arr;
        foreach ($parts as $p) {
            if (!is_array($ref) || !array_key_exists($p, $ref)) {
                return null;
            }
            $ref = $ref[$p];
        }
        return $ref;
    }
}

// Helper: zet $value op een genest pad (bijv. "phone.value" of "email/value")
if (!function_exists('setByPath')) {
    function setByPath(array &$arr, string $path, $value, string $separators='./'): void {
        // splits op . of /
        $parts = preg_split('/[' . preg_quote($separators, '/') . ']+/', $path, -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) return;

        $ref =& $arr;
        $last = array_pop($parts);

        foreach ($parts as $p) {
            if (!isset($ref[$p]) || !is_array($ref[$p])) {
                // maak lege array als het nog niet bestaat of geen array is
                $ref[$p] = [];
            }
            $ref =& $ref[$p];
        }
        $ref[$last] = $value;
    }
}

$claims = [];

// 1) Statische credentialData als basis
if (isset($attributes['credentialData']) && !empty($attributes['credentialData'])) {
    $decoded = json_decode($attributes['credentialData'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $claims = $decoded; // basisstructuur (bijv. phone.verified_at, phone.method)
    }
}

// 2) SessionData toepassen als geneste updates
if (isset($attributes['sessionData'][0]) || isset($attributes['sessionData'])) {
    // $attributes['sessionData'] kan string JSON zijn (zoals in je voorbeeld)
    $sessionData = is_string($attributes['sessionData'])
        ? json_decode($attributes['sessionData'])
        : $attributes['sessionData'];

    if (json_last_error() === JSON_ERROR_NONE || is_array($sessionData)) {
        if (isset($_SESSION['presentationResponse'])) {
            $presentationResponse = $_SESSION['presentationResponse'];

            foreach ($sessionData as $item) {
                // verwacht object met ->key, ->mapping, ->type
                if (!isset($item->key, $item->mapping, $item->type)) {
                    continue;
                }
                $type = $item->type;
                $mapping = $item->mapping;

                // Read from the PID claims using the mapping as a nested path
                $val = null;
                if (isset($presentationResponse[$type]['claims']) && is_array($presentationResponse[$type]['claims'])) {
                    $val = getByPath($presentationResponse[$type]['claims'], (string) $mapping);
                }

                if ($val !== null) {
                    // Write into the new credential using "key" as a nested path
                    setByPath($claims, (string) $item->key, $val);
                }

            }
        } else {
          echo $block_content = '<div ' . get_block_wrapper_attributes() . '><p>Er kunnen geen credentials opgehaald worden.</p></div>';
          return;
        }
    } else {
      echo $block_content = '<div ' . get_block_wrapper_attributes() . '><p>SessionData is niet geldig.</p></div>';
      return;
    }
}



do_action( 'wp_enqueue_script' );

$html = '';
$form = false;

if(isset($attributes['formData']) && !empty($attributes['formData'])){
    $formData = json_decode($attributes['formData']);
    $form = true;
    $html .= '<form class="mt-4 d-block"  id="OpenID4VCI-form">';
    foreach ($formData as $key => $value) {
        $html .= '<div class="form-input mb-3">';
        $html .= '<label class="d-block mb-2"><strong>'.$value.'</strong></label>';
        $html .= '<input type="text" class="input--standard" name="'.$key.'" placeholder="'.$value.'">';

        $html .= '</div>';
    }
    $html .= '<input type="hidden" name="qrrequest">';
    $html .= '<button type="submit" class="btn btn-primary btn-sm">'. __( 'Submit', 'fides' ).'</button>';
    $html .= '</form>';

}

if(isset($_GET['qrrequest'])){

  // Extract the original form keys defined in the block attributes.
  // These may contain dots or slashes (e.g. "credentialSubject.given_name").
  // PHP automatically converts dots in form field names to underscores
  // when populating $_GET and $_POST. Therefore, we need the original keys
  // to map the "mangled" PHP keys back to the intended nested structure.
  $originalFormKeys = [];
  if (isset($attributes['formData']) && !empty($attributes['formData'])) {
    $decodedForm = json_decode($attributes['formData']);
    if ($decodedForm && (is_object($decodedForm) || is_array($decodedForm))) {
      foreach ($decodedForm as $k => $label) {
        $originalFormKeys[] = (string) $k; // Example: "credentialSubject.given_name"
      }
    }
  }

  foreach ($_GET as $name => $value) {
    if ($name === 'qrrequest') {
      continue;
    }

    $originalKey = $name;

    // Try to find the original key:
    foreach ($originalFormKeys as $formKey) {
      // Exact match
      if ($formKey === $name) {
        $originalKey = $formKey;
        break;
      }

      // Dots or slashes replaced by underscores
      if (str_replace(['.', '/'], '_', $formKey) === $name) {
        $originalKey = $formKey;
        break;
        }
      }

      // Write the submitted value into $claims using the *intended* nested path.
      setByPath($claims, $originalKey, $value);
    }

    $response = sendVciRequest($claims, $attributes);

    if ($response["success"] === false) {
        echo $response["error"];
        return;
    }
   do_action( 'wp_enqueue_script' );

   if ( isset( $response['result']->credential ) ) {
       error_log( '[openid4vci] SD-JWT credential response: ' . wp_json_encode( $response['result']->credential ) );
   } else {
       error_log( '[openid4vci] Credential response zonder sd-jwt veld: ' . wp_json_encode( $response['result'] ) );
   }
   if ( isset( $response['result']->credential ) ) {
       error_log( '[openid4vci] SD-JWT credential response: ' . wp_json_encode( $response['result']->credential ) );
   } else {
       error_log( '[openid4vci] Credential response zonder sd-jwt veld: ' . wp_json_encode( $response['result'] ) );
   }
   $qr_content = $attributes['qrCodeEnabled'] ? '<img id="openid4vp_qrImage" src="data:' . $response["result"]->qr_uri . '"></>'. __( 'or ', 'fides' ) : '';
   $block_content = '<div ' . get_block_wrapper_attributes() . '>' . $qr_content . __( 'click ', 'fides' ) . '<a href="' . $response["result"]->request_uri . '">link</a></div>';
} elseif($form){
   $block_content = '<div ' . get_block_wrapper_attributes() . '>'.$html.'</div>';
} else {
   $response = sendVciRequest($claims, $attributes);

    if ($response["success"] === false) {
        echo $response["error"];
        return;
    }

   $qr_content = $attributes['qrCodeEnabled'] ? '<img id="openid4vp_qrImage" src="data:' . $response["result"]->qr_uri . '"></>'. __( 'or ', 'fides' ) : '';
   $block_content = '<div ' . get_block_wrapper_attributes() . '>' . $qr_content . __( 'click ', 'fides' ) . '<a href="' . $response["result"]->request_uri . '">link</a></div>';
}



echo $block_content;
