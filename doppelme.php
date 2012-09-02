<?php

/*
Plugin Name: DoppelMe Avatars
Plugin URI: http://www.doppelme.com
Description: Allow your users to create their own avatars within your BuddyPress installation.
Version: 1.00
Author: DoppelMe
License: GPL2
*/


//initialisation (only activate if buddypress installed)
function bp_doppelme_init() {
	
	require( dirname( __FILE__ ) . '/includes/bp-doppelme-core.php' );
    
}
add_action( 'bp_include', 'bp_doppelme_init' );


/*

// Put setup procedures to be run when the plugin is activated 
function bp_doppelme_activate() {
	global $wpdb;

	//require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	//update_site_option( 'bp-example-db-version', BP_EXAMPLE_DB_VERSION );
}
register_activation_hook( __FILE__, 'bp_doppelme_activate' );




// On deacativation, clean up anything your component has added. 
function bp_doppelme_deactivate() {
	//do  nothing
}
register_deactivation_hook( __FILE__, 'bp_doppelme_deactivate' );

*/
?>