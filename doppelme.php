<?php

/*
Plugin Name: DoppelMe Avatars
Plugin URI: http://www.doppelme.com
Description: Allow your users to create their own avatars within your BuddyPress installation.
Version: 1.03
Author: DoppelMe
License: GPL2
*/


//initialisation (only activate if buddypress installed)
function bp_doppelme_init() {
	
	require( dirname( __FILE__ ) . '/includes/bp-doppelme-core.php' );
    
}
add_action( 'bp_include', 'bp_doppelme_init' );
?>