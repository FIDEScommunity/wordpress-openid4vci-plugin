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

$claims = [];
if(isset($attributes['credentialData']) && !empty($attributes['credentialData'])) {
   $claims = json_decode($attributes['credentialData'], true);
}
if(isset($attributes['sessionData'][0])){
    $sessionData = json_decode($attributes['sessionData']);

    $sessionMapping = [];
     if(isset($_SESSION['presentationResponse'])){
       $presentationResponse = $_SESSION['presentationResponse'];
        foreach ($sessionData as $key => $value) {
            if (array_key_exists($value->type, $presentationResponse)) {

               $sessionMapping[$value->key] = $presentationResponse[$value->type]['claims'][$value->mapping];
            } else {
               echo $value->type . ' is nog niet gevalideerd<br>';
               // return;
            }
        }

       $claims = array_merge($sessionMapping, $claims);

    } else {
       echo $block_content = '<div ' . get_block_wrapper_attributes() . '><p>Er kunnen geen credentials opgehaald worden.</p></div>';
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
    foreach($_GET as $name => $value) {
        if($name !== 'qrrequest'){
            $claims[$name] = $value;
        }
    }
    $response = sendVciRequest($claims, $attributes);

    if ($response["success"] === false) {
        echo $response["error"];
        return;
    }
   do_action( 'wp_enqueue_script' );

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

