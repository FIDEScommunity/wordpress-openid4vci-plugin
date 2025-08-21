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

if(isset($_SESSION['presentationResponse'])){
    $presentationResponse = $_SESSION['presentationResponse'];
    if(isset($presentationResponse['Pid']['claims']['given_name'])){
        $givenName = $presentationResponse['Pid']['claims']['given_name'];
        $familyName = $presentationResponse['Pid']['claims']['family_name'];

        $claims['firstname'] = $presentationResponse['Pid']['claims']['given_name'];
        $claims['lastname'] = $presentationResponse['Pid']['claims']['family_name'];
        $claims['jobtitle'] = '';
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
    $html .= '<div class="form-input mb-3"><label class="d-block mb-2"><strong>Wallet URL</strong></label><input type="text" id="org-wallet-url" name="walletUrl" placeholder="Enter wallet URL" /></div>';
    $html .= '<button type="submit" class="btn btn-primary btn-sm">'. __( 'Connect to wallet', 'fides' ).'</button>';
    $html .= '</form>';

}

if(isset($_GET['qrrequest'])){
   foreach($_GET as $name => $value) {
       if($name !== 'qrrequest' && $name !== 'walletUrl'){
           $claims[$name] = $value;
       }
   }
   $response = sendVciRequest($claims, $attributes);

   if (is_wp_error($response)) {
       return 'Error fetching data';
   }

   $body = wp_remote_retrieve_body($response);
   $result = json_decode( $body );

   do_action( 'wp_enqueue_script' );

   wp_redirect( $result -> request_uri );
   exit;
} elseif($form){
   $block_content = '<div ' . get_block_wrapper_attributes() . '>'.$html.'</div>';
} elseif(!isset($_GET['walletUrl'])) {
    $block_content = '<form class="mt-4 d-block" id="OpenID4VCI-form"><div ' . get_block_wrapper_attributes() . '><input type="text" id="org-wallet-url" name="walletUrl" placeholder="Enter wallet URL" />
            <button type="submit" class="btn btn-primary btn-sm">'. __( 'Connect to wallet', 'fides' ).'</button></div></form>';
} else {
   $response = sendVciRequest($claims, $attributes);

   if (is_wp_error($response)) {
       return 'Error fetching data';
   }

   $body = wp_remote_retrieve_body($response);

   $result = json_decode( $body );

   wp_redirect( $result -> request_uri );
   exit;
}

echo $block_content;

