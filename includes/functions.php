<?php

/**
 * Wrapper function to call DoppelMe's API functions.
 */
function bpdpl_api() {
	if ( empty( buddypress()->doppelme->api ) ) {
		buddypress()->doppelme->api = new DoppelMe( buddypress()->doppelme->partner_id, buddypress()->doppelme->partner_key );
	}

	return buddypress()->doppelme->api;
}

/**
 * Get a user's DoppelMe key.
 *
 * Required to fetch the user's correct DoppelMe profile picture.
 *
 * @param int $user_id The user ID to grab the DoppelMe key for.
 */
function bpdpl_get_user_key( $user_id = 0 ) {
	if ( 0 === $user_id ) {
		$user_id = bp_displayed_user_id();
	}

	return bp_get_user_meta( $user_id, 'doppelme_key', true );
}

/**
 * Mirrors a profile pic from DoppelMe and saves it locally.
 *
 * This is designed to prevent pinging DoppelMe for the avatar.  Mirrors both
 * full size and thumbnail size versions of the DoppelMe avatar.
 *
 * @param  int   $user_id The user ID to fetch the avatar for.
 * @return array On success, returns an associative array of file attributes. On failure, 
 *               returns the upload error handler - array( 'error' => $message )
 */
function bpdpl_mirror_avatar( $user_id = 0 ) {
	if ( 0 === $user_id ) {
		$user_id = bp_displayed_user_id();
	}

	// require WP's file functions
	require_once ABSPATH . '/wp-admin/includes/file.php';

	// delete existing avatar
	bp_core_delete_existing_avatar( array(
		'item_id' => $user_id
	) );

	// set up some variables we'll need
	$user_key   = bpdpl_get_user_key( $user_id );
	$full_size  = bp_core_avatar_full_width();
	$thumb_size = bp_core_avatar_thumb_width();

	// download doppelme avatars
	$temp_avatar_full  = download_url( apply_filters( 'bpdpl_get_avatar_full_url', "http://www.doppelme.com/75/{$user_key}/avatar.png?canvas_width=200" ) );
	$temp_avatar_thumb = download_url( apply_filters( 'bpdpl_get_avatar_thumb_url', "http://www.doppelme.com/{$thumb_size}/{$user_key}/crop.png" ) );

	// setup the parameters to move the avatars
	$hash = wp_hash( $temp_avatar_full . time() );

	$avatar_full = array(
		'name'     => "{$hash}-bpfull.png",
		'type'     => 'image/png',
		'tmp_name' => $temp_avatar_full,
		'error'    => 0,
		'size'     => filesize( $temp_avatar_full ),
	);

	$avatar_thumb = array(
		'name'     => "{$hash}-bpthumb.png",
		'type'     => 'image/png',
		'tmp_name' => $temp_avatar_thumb,
		'error'    => 0,
		'size'     => filesize( $temp_avatar_thumb ),
	);

	$overrides = array(
		// tells WordPress to not look for the POST form fields since we downloaded
		// the files externally
		'test_form' => false,
	);

	// add BP's avatar upload directory filter
	add_filter( 'upload_dir', 'xprofile_avatar_upload_dir', 10, 0 );

	// "move it, move it"
	$move_full  = wp_handle_sideload( $avatar_full,  $overrides );
	$move_thumb = wp_handle_sideload( $avatar_thumb, $overrides );

	// remove BP's avatar upload directory filter
	remove_filter( 'upload_dir', 'xprofile_avatar_upload_dir', 10, 0 );

	// remove temp files
	@unlink( $temp_avatar_full );
	@unlink( $temp_avatar_thumb );

	return $move_full;
}

/**
 * Output the disconnection URL.
 *
 * @see bpdl_get_the_disconnection_url()
 */
function bpdpl_the_disconnection_url() {
	echo bpdpl_get_the_disconnection_url();
}
	/**
	 * Return the disconnection URL.
	 *
	 * This is used to remove a user's DoppelMe key, which will remove the
	 * user's connection to DoppelMe.
	 *
	 * @return string
	 */
	function bpdpl_get_the_disconnection_url() {
		return wp_nonce_url( bp_get_requested_url(), 'bpdpl_disconnect', 'bpdpl-disconnect' );
	}