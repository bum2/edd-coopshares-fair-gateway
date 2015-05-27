<?php
/*
Plugin Name: Easy Digital Downloads - CoopShares-FIF Manual Gateway
Plugin URL: https://github/bum2/edd-coopshares-fair-gateway
Description: Another EDD gateway for the CoopShares-FIF (Faircoin Investment Fund) option in getfaircoin.net, forked from the edd-manual-gateway (a fork of the manual_edd_wp_plugin).
Version: 0.5
Author: Bumbum
Author URI: https://getfaircoin.net
*/

//Language
load_plugin_textdomain( 'edd-coopshares-fair', false,  dirname(plugin_basename(__FILE__) ) );

//Load post fields management
require_once ( __DIR__ . '/edd-coopshares-fair-post.php');

//Registers the gateway
function coopshares_fair_wp_edd_register_gateway( $gateways ) {
	$gateways['coopshares_fair'] = array( 'admin_label' => 'CoopShares-Faircoin', 'checkout_label' => __( 'CoopShares Faircoin', 'edd-coopshares-fair' ) );
	return $gateways;
}
add_filter( 'edd_payment_gateways', 'coopshares_fair_wp_edd_register_gateway' );


//Pre purchase form
function edd_coopshares_fair_gateway_cc_form() {

	$output = '<div>';

		global $edd_options;
		$output .= $edd_options['coopshares_fair_checkout_info'];
    
	$output .= "</div>";
    
        $output .= '<label class="edd-label" for="edd_faircoins"> Faircoins <span class="edd-required-indicator">*</span></label>';
        $output .= '<span class="edd-description"> Enter the number of Faircoins you will send from your wallet to the Coopsshares-FIF faircoin address. </span>';
        $output .= '<input class="edd-input" type="text" name="edd_faircoins" id="edd-faircoins" placeholder="Number of Faircoins" value="" ><br/><br/>';
    
	echo $output;
    
    return false;

}
add_action('edd_coopshares_fair_cc_form', 'edd_coopshares_fair_gateway_cc_form');


//////////////////
/**
* Make faircoins number required
* Add more required fields here if you need to
*/
function coopshares_fair_edd_required_checkout_fields( $required_fields ) {
  //print_r($required_fields);
  /*$required_fields['edd_faircoins'] = array(
    'error_id' => 'invalid_faircoins',
    'error_message' => __('Please enter a valid Faircoins quantity', 'edd-coopshares-fair')
  );*/
  return $required_fields;
}
add_filter( 'edd_purchase_form_required_fields', 'coopshares_fair_edd_required_checkout_fields' );


/**
* Set error if faircoins number field is empty
* You can do additional error checking here if required
*/
function coopshares_fair_edd_validate_checkout_fields( $valid_data, $data ) {
  $msg = '';//'DATA: '.print_r($data);
  if( $data['edd-gateway'] == 'coopshares_fair' ) {
    $faircoins = $data['edd_faircoins'];
    if( $faircoins === '0' || $faircoins < 1 || $faircoins == '') {
      edd_set_error( 'invalid_faircoins', $msg.__(' Please enter a Faircoins quantity.', 'edd-coopshares-fair') );
    }
  }
}
add_action( 'edd_checkout_error_checks', 'coopshares_fair_edd_validate_checkout_fields', 10, 2 );


/**
* Store the custom field data into EDD's payment meta
*/
function coopshares_fair_edd_store_custom_fields( $payment_meta ) {
  $payment_meta['faircoins'] = isset( $_POST['edd_faircoins'] ) ? sanitize_text_field( $_POST['edd_faircoins'] ) : '';
  return $payment_meta;
}
add_filter( 'edd_payment_meta', 'coopshares_fair_edd_store_custom_fields');


/**
* Add the faircoins number to the "View Order Details" page
*/
function coopshares_fair_edd_view_order_details( $payment_meta, $user_info ) {
  $faircoins = isset( $payment_meta['faircoins'] ) ? $payment_meta['faircoins'] : '';
  ?>
  <div class="column-container">
    <div class="column">
      <strong><?php echo _e('FIF-Faircoins: ', 'edd-coopshares-fair'); ?></strong>
      <input type="text" name="edd_faircoins" value="<?php esc_attr_e( $faircoins ); ?>" class="small-text" />
      <p class="description"><?php _e( 'Coopshares-FIF invested Faircoins', 'edd-coopshares-fair' ); ?></p>
    </div>
  </div>
  <?php
}
add_action( 'edd_payment_personal_details_list', 'coopshares_fair_edd_view_order_details', 10, 2 );


/**
* Save the faircoins field when it's modified via view order details
*/
function coopshares_fair_edd_updated_edited_purchase( $payment_id ) {
  // get the payment meta
  $payment_meta = edd_get_payment_meta( $payment_id );
  // update our fairaddress number
  $payment_meta['faircoins'] = isset( $_POST['edd_faircoins'] ) ? $_POST['edd_faircoins'] : '';
  // update the payment meta with the new array
  update_post_meta( $payment_id, '_edd_payment_meta', $payment_meta );
}
add_action( 'edd_updated_edited_purchase', 'getfaircoin_edd_updated_edited_purchase' );


////    E M A I L   T A G S    ////

/**
* The {csf_faircoins} email tag
*/
function cs_fair_edd_email_tag_faircoins( $payment_id ) {
  $payment_data = edd_get_payment_meta( $payment_id );
  return $payment_data['faircoins'];
}
/**
* The {csf_FAIR} email tag
*/
function cs_fair_edd_email_tag_FAIR( $payment_id ) {
	global $edd_options;
	if ( $edd_options['coopshares_fair_one_or_multiple_FAIR'] == 1 ) {
		$FAIR = $edd_options['coopshares_fair_Address'];
	} else {
		$downloads = edd_get_payment_meta_cart_details( $payment_id );
		$post_id = $downloads[0]['id'];
		$FAIR = get_post_meta( $post_id, 'coopshares_fair_post_FAIR', true );
	}
	return $FAIR;
}

/**
* Add a email tags for use in either the purchase receipt email or admin notification emails
*/
function coopshares_fair_edd_setup_email_tags() {
	// Setup default tags array
	$email_tags = array(
		array(
			'tag'         => 'csf_FAIR',
			'description' => __( 'Coopshares-FIF faircoin Address', 'edd-coopshares-fair' ),
			'function'    => 'cs_fair_edd_email_tag_FAIR'
		),
        	array(
        	    'tag'           => 'csf_faircoins',
        	    'description'   => __( 'Coopshares-FIF payment Faircoin amount', 'edd-coopshares-fair'),
        	    'function'      => 'cs_fair_edd_email_tag_faircoins'
        	)
	);

	// Apply edd_email_tags filter
	$email_tags = apply_filters( 'edd_email_tags', $email_tags );

	// Add email tags
	foreach ( $email_tags as $email_tag ) {
		edd_add_email_tag( $email_tag['tag'], $email_tag['description'], $email_tag['function'] );
	}
}
add_action( 'edd_add_email_tags', 'coopshares_fair_edd_setup_email_tags' );


////    P R O C E S S   P A Y M E N T    ////

function coopshares_fair_currency_filter($price){
  if( count( explode(' ', $price) ) > 1) {
    $price = str_replace('&euro;', '', $price);
    $price = str_replace('EUR', 'FAIR', $price);
  }
  return $price;
}
//add_filter( 'edd_eur_currency_filter_after', 'coopshares_fair_currency_filter' );

function coopshares_fair_saved_currency( $saved_currency ) {
  return 'FAIR';
}
//add_filter( 'edd_currency_get_saved_currency', 'coopshares_fair_saved_currency' );

// processes the payment
function coopshares_fair_wp_edd_process_payment( $purchase_data ) {
    global $edd_options;
    add_filter( 'edd_currency_get_saved_currency', 'coopshares_fair_saved_currency' );
    add_filter( 'edd_eur_currency_filter_after', 'coopshares_fair_currency_filter' );

	// check for any stored errors
	$errors = edd_get_errors();
	if ( ! $errors ) {

		$purchase_summary = edd_get_purchase_summary( $purchase_data );
        
        	$purchase_data['price'] = $purchase_data['post_data']['edd_faircoins'];
        	$purchase_data['cart_details'][0]['item_number']['quantity'] = $purchase_data['post_data']['edd_faircoins'];
        	$purchase_data['cart_details'][0]['item_number']['item_price'] = $edd_options['faircoin_price'];
        
		$payment = array(
			'price'        => $purchase_data['price'],
			'date'         => $purchase_data['date'],
			'user_email'   => $purchase_data['user_email'],
			'purchase_key' => $purchase_data['purchase_key'],
			'currency'     => 'FAIR',//$edd_options['currency'],
			'downloads'    => $purchase_data['downloads'],
			'cart_details' => $purchase_data['cart_details'],
			'user_info'    => $purchase_data['user_info'],
			'status'       => 'pending'
		);

		// record the pending payment
		$payment = edd_insert_payment( $payment );

		// send email with payment info
		coopshares_fair_email_purchase_order( $payment );

		edd_send_to_success_page();

		remove_filter( 'edd_currency_get_saved_currency', 'coopshares_fair_saved_currency' );
		remove_filter( 'edd_eur_currency_filter_after', 'coopshares_fair_currency_filter' );

	} else {
		$fail = true; // errors were detected
	}

	if ( $fail !== false ) {
		// if errors are present, send the user back to the purchase page so they can be corrected
		//edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
		edd_send_back_to_checkout( '/checkout' );
	}
}
add_action( 'edd_gateway_coopshares_fair', 'coopshares_fair_wp_edd_process_payment' );


// adds the settings to the Payment Gateways section
function coopshares_fair_wp_edd_add_settings ( $settings ) {

	$coopshares_fair_gateway_settings = array(
		array(
			'id' => 'coopshares_fair_gateway_settings',
			'name' => '<strong>' . __( 'CoopShares-Fair Settings', 'edd-coopshares-fair' ) . '</strong>',
			'desc' => __( 'Settings to manage the coopshares-fair manual payment gateway', 'edd-coopshares-fair' ),
			'type' => 'header'
		),
		array(
			'id' => 'coopshares_fair_Address',
			'name' => __( 'Coopshares Fair Address', 'edd-coopshares-fair' ),
			'desc' => __( 'The main faircoin address (account) to receive the funds', 'edd-coopshares-fair' ),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'coopshares_fair_one_or_multiple_FAIR',
			'name' => __( 'One or Multiple FAIR accounts', 'edd-coopshares-fair' ),
			'desc' => __( 'Use the main FAIR address or the one setted in the post level', 'edd-coopshares-fair' ),
			'type' => 'select',
			'options' => array(1 => 'ONE', 2 => 'MULTIPLE'),
			'std'  => 1
		),
		array(
			'id' => 'coopshares_fair_checkout_info',
			'name' => __( 'Coopshares-FIF Checkout Info', 'edd-coopshares-fair' ),
			'desc' => __( 'Add some html in the checkbox page', 'edd-coopshares-fair' ),
			'type' => 'rich_editor'
		),
		array(
			'id' => 'coopshares_fair_receipt_info',
			'name' => __( 'Coopshares-FIF Receipt Info', 'edd-coopshares-fair' ),
			'desc' => __( 'Add some html in the receipt page', 'edd-coopshares-fair' ),
			'type' => 'rich_editor'
		),
		array(
			'id' => 'coopshares_fair_from_email',
			'name' => __( 'Coopshares-FIF Email From', 'edd-coopshares-fair' ),
			'desc' => __( 'The main remitent of the Coopshares-FIF comunication', 'edd-coopshares-fair' ),
			'type' => 'text',
			'size' => 'regular',
			'std'  => get_bloginfo( 'admin_email' )
		),
		array(
			'id' => 'coopshares_fair_subject_mail',
			'name' => __( 'Coopshares-FIF Email Subject', 'edd-coopshares-fair' ),
			'desc' => __( 'The subject of the email to the user (can use email tags)', 'edd-coopshares-fair' ),  //. '<br/>' . edd_get_emails_tags_list(),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'coopshares_fair_body_mail',
			'name' => __( 'Coopshares-FIF Email Body', 'edd-coopshares-fair' ),
			'desc' => __('The body of the email to the user (can use email tags below)', 'edd-coopshares-fair') . '<br/>' . edd_get_emails_tags_list()  ,
			'type' => 'rich_editor',
		),

	);

	return array_merge( $settings, $coopshares_fair_gateway_settings );
}
add_filter( 'edd_settings_gateways', 'coopshares_fair_wp_edd_add_settings' );



////   R E C E I P T    ////

function coopshares_fair_payment_receipt_after($payment){
  global $edd_options; 
  if( edd_get_payment_gateway( $payment->ID ) == 'coopshares_fair'){
    if ( $edd_options['coopshares_fair_one_or_multiple_FAIR'] == 1 ) {
        $message = stripslashes ( $edd_options['coopshares_fair_receipt_info'] );
    } else {
        $payment_data = edd_get_payment_meta( $payment->ID );
        $downloads = edd_get_payment_meta_cart_details( $payment->ID );
        $post_id = $downloads[0]['id'];
        $message = stripslashes ( get_post_meta( $post_id, 'coopshares_fair_post_receipt', true ));
    }
    $message = edd_do_email_tags( $message, $payment->ID );
    echo $message;
  }
}
add_action('edd_payment_receipt_after_table', 'coopshares_fair_payment_receipt_after');


////    E M A I L    ////

//Sent coopshares-fair instructions
function coopshares_fair_email_purchase_order ( $payment_id, $admin_notice = true ) {

	global $edd_options;
	add_filter( 'edd_currency_get_saved_currency', 'coopshares_fair_saved_currency' );
        add_filter( 'edd_eur_currency_filter_after', 'coopshares_fair_currency_filter' );

	$payment_data = edd_get_payment_meta( $payment_id );
	$user_id      = edd_get_payment_user_id( $payment_id );
	$user_info    = maybe_unserialize( $payment_data['user_info'] );
	$to           = edd_get_payment_user_email( $payment_id );

	if ( isset( $user_id ) && $user_id > 0 ) {
		$user_data = get_userdata($user_id);
		$name = $user_data->display_name;
	} elseif ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
		$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
	} else {
		$name = $email;
	}

	$message = edd_get_email_body_header();


	if ( $edd_options['coopshares_fair_one_or_multiple_FAIR'] == 1 ) {
		$email = stripslashes( $edd_options['coopshares_fair_body_mail'] );
		$from_email = isset( $edd_options['coopshares_fair_from_email'] ) ? $edd_options['coopshares_fair_from_email'] : get_option('admin_email');
		$subject = wp_strip_all_tags( $edd_options['coopshares_fair_subject_mail'], true );
	} else {
		$downloads = edd_get_payment_meta_cart_details( $payment_id );
		$post_id = $downloads[0]['id'];
		$email = stripslashes (get_post_meta( $post_id, 'coopshares_fair_post_body_mail', true ));
		$from_email = get_post_meta( $post_id, 'coopshares_fair_post_from_email', true );
		$subject = wp_strip_all_tags(get_post_meta( $post_id, 'coopshares_fair_post_subject_mail', true ));
	}


	$message .= edd_do_email_tags( $email, $payment_id );
	$message .= edd_get_email_body_footer();

	$from_name = get_bloginfo('name');

	$subject = edd_do_email_tags( $subject, $payment_id );

	$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$headers .= "Reply-To: ". $from_email . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=utf-8\r\n";
	$headers = apply_filters( 'edd_receipt_headers', $headers, $payment_id, $payment_data );

	if ( apply_filters( 'edd_email_purchase_receipt', true ) ) {
		wp_mail( $to, $subject, $message, $headers);//, $attachments );
	}

	if ( $admin_notice && ! edd_admin_notices_disabled( $payment_id ) ) {
		do_action( 'edd_admin_sale_notice', $payment_id, $payment_data );
	}

	remove_filter( 'edd_currency_get_saved_currency', 'coopshares_fair_saved_currency' );
        remove_filter( 'edd_eur_currency_filter_after', 'coopshares_fair_currency_filter' );

}

