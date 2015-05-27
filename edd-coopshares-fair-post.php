<?php

function coopshares_fair_text_callback ( $args, $post_id ) {
	$value = get_post_meta( $post_id, $args['id'], true );
	if ( $value != "" ) {
		$value = get_post_meta( $post_id, $args['id'], true );
	}else{
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$output = "<tr valign='top'> \n".
		" <th scope='row'> " . $args['name'] . " </th> \n" .
		" <td><input type='text' class='regular-text' id='" . $args['id'] . "'" .
		" name='" . $args['id'] . "' value='" .  $value   . "' />\n" .
		" <label for='" . $args['id'] . "'> " . $args['desc'] . "</label>" .
		"</td></tr>";

	return $output;
}

function coopshares_fair_rich_editor_callback ( $args, $post_id ) {
	$value = get_post_meta( $post_id, $args['id'], true );
	if ( $value != "" ) {
		$value = get_post_meta( $post_id, $args['id'], true );
	}else{
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}
	$output = "<tr valign='top'> \n".
		" <th scope='row'> " . $args['name'] . " </th> \n" .
		" <td>";
		ob_start();
		wp_editor( stripslashes( $value ) , $args['id'], array( 'textarea_name' => $args['id'] ) );
	$output .= ob_get_clean();

	$output .= " <label for='" . $args['id'] . "'> " . $args['desc'] . "</label>" .
		"</td></tr>\n";

	return $output;
}


/**
 * Updates when saving post
 *
 */
function coopshares_fair_edd_wp_post_save( $post_id ) {

	if ( ! isset( $_POST['post_type']) || 'download' !== $_POST['post_type'] ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return $post_id;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;

	$fields = coopshares_fair_wp_edd_fields();

	foreach ($fields as $field) {
		if( isset($_REQUEST[$field['id']]) ) update_post_meta( $post_id, $field['id'],  $_REQUEST[$field['id']] );
	}
}
add_action( 'save_post', 'coopshares_fair_edd_wp_post_save' );


/**
 * Display sidebar metabox in saving post
 *
 */
function coopshares_fair_edd_wp_print_meta_box ( $post ) {

	if ( get_post_type( $post->ID ) != 'download' ) return;

	?>
	<div class="wrap">
		<div id="tab_container">
			<table class="form-table">
				<?php
					$fields = coopshares_fair_wp_edd_fields();
					foreach ($fields as $field) {
						if ( $field['type'] == 'text'){
							echo coopshares_fair_text_callback( $field, $post->ID );
						}elseif ( $field['type'] == 'rich_editor' ) {
							echo coopshares_fair_rich_editor_callback( $field, $post->ID ) ;
						}
					}
				?>

			</table>
		</div><!-- #tab_container-->
	</div><!-- .wrap -->
	<?php
}

function coopshares_fair_edd_wp_show_post_fields ( $post) {

	add_meta_box( 'coopshares_fair_'.$post->ID, __( "CoopShares-Fair Settings", 'edd-coopshares-fair'), "coopshares_fair_edd_wp_print_meta_box", 'download', 'normal', 'high');

}
add_action( 'submitpost_box', 'coopshares_fair_edd_wp_show_post_fields' );

function coopshares_fair_wp_edd_fields () {

	$coopshares_fair_gateway_post_settings = array(
		array(
			'id' => 'coopshares_fair_post_FAIR',
			'name' => __( 'Coopshares-FIF post FAIR Address', 'edd-coopshares-fair' ),
			'desc' => __( 'The Faircoin Address for this getmethod post', 'edd-coopshares-fair' ),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'coopshares_fair_post_receipt',
			'name' => __( 'Coopshares-FIF post Receipt', 'edd-coopshares-fair' ),
			'desc' => __('The html to add in the Receipt (success) page', 'edd-coopshares-fair'),// . '<br/>' . edd_get_emails_tags_list()  ,
			'type' => 'rich_editor'
		),
		//
		array(
			'id' => 'coopshares_fair_post_from_email',
			'name' => __( 'Coopshares-FIF post Email From', 'edd-coopshares-fair' ),
			'desc' => __( 'The remitent email for this post comunication', 'edd-coopshares-fair' ),
			'type' => 'text',
			'size' => 'regular',
			'std'  => get_bloginfo( 'admin_email' )
		),
		array(
			'id' => 'coopshares_fair_post_subject_mail',
			'name' => __( 'Coopshares-FIF post Email Subject', 'edd-coopshares-fair' ),
			'desc' => __( 'The subject for this post emails (can use email tags)', 'edd-coopshares-fair' ),//  . '<br/>' . edd_get_emails_tags_list(),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'coopshares_fair_post_body_mail',
			'name' => __( 'Coopshares-FIF post Email Body', 'edd-coopshares-fair' ),
			'desc' => __('The body of this post emails (can use the email tags below)', 'edd-coopshares-fair') . '<br/>' . edd_get_emails_tags_list()  ,
			'type' => 'rich_editor',
		),
	);

	return $coopshares_fair_gateway_post_settings;
}
