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

$options = new OpenID4VCI_Admin_Options();
$openidEndpoint = $options->openidEndpoint;
$authenticationHeaderName = $options->authenticationHeaderName;
$authenticationToken = $options->authenticationToken;
if (!empty($attributes['openidEndpoint'])) {
    $openidEndpoint = $attributes['openidEndpoint'];
    $authenticationHeaderName = $attributes['authenticationHeaderName'];
    $authenticationToken = $attributes['authenticationToken'];
}

 if(isset($_SESSION['presentationResponse'])){
    $presentationResponse = $_SESSION['presentationResponse'];
    if(isset($presentationResponse['Pid']['credential']['given_name'])){
        $givenName = $presentationResponse['Pid']['credential']['given_name'];
        $familyName = $presentationResponse['Pid']['credential']['family_name'];
        $fullName = $givenName.' '.$familyName;

        $attributes['credentialData'] = '{
         "firstname": "'.$givenName.'",
         "lastname": "'.$familyName.'",
         "jobtitle": ""
        }';
    }
}

$response = wp_remote_post( $openidEndpoint . '/' . $attributes['credentialIssueTemplateKey'] . '/qr', array(
    'headers' => array('Content-Type' => 'application/json', $authenticationHeaderName => $authenticationToken),
    'timeout'     => 45,
    'redirection' => 5,
    'blocking'    => true,
    'body'        => $attributes['credentialData']
));

if (is_wp_error($response)) {
    return 'Error fetching data';
}

$body = wp_remote_retrieve_body($response);
$result = json_decode( $body );

do_action( 'wp_enqueue_script' );

$html = '';
$form = false;

if(isset($attributes['formData'])){
    $formData = json_decode($attributes['formData']);
    $form = true;
    $html .= '<form class="mt-4 d-block"  id="OpenID4VP-form">';
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

    $params = [];

    foreach($_GET as $name => $value) {
        if($name !== 'qrrequest'){
            $params[$name] = $value;
        }
    }

    $credentialData = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

    $attributes['credentialData'] = $credentialData;

   $response = wp_remote_post( $openidEndpoint . '/' . $attributes['credentialIssueTemplateKey'] . '/qr', array(
       'headers' => array('Content-Type' => 'application/json', $authenticationHeaderName => $authenticationToken),
       'timeout'     => 45,
       'redirection' => 5,
       'blocking'    => true,
       'body'        => $attributes['credentialData']
   ));

   if (is_wp_error($response)) {
       return 'Error fetching data';
   }

   $body = wp_remote_retrieve_body($response);
   $result = json_decode( $body );

   do_action( 'wp_enqueue_script' );

   $block_content = '<div ' . get_block_wrapper_attributes() . '><img id="openid4vp_qrImage" src="data:' . $result->qrImage . '"></>'. __( 'or click', 'fides' ).' <a href="' . $result->credentialIssueUri . '">link</a></div>';


} elseif($form){
         $block_content = '<div ' . get_block_wrapper_attributes() . '>'.$html.'</div>';
} else {

    $block_content = '<div ' . get_block_wrapper_attributes() . '>'.$html.'<img id="openid4vp_qrImage" src="data:' . $result->qrImage . '"></>'. __( 'or click', 'fides' ).' <a href="' . $result->credentialIssueUri . '">link</a></div>';

}



echo $block_content;

