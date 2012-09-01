<?php

define ( 'BP_DOPPELME_IS_INSTALLED', 1 );
define ( 'BP_DOPPELME_VERSION', '1.5' );
define ( 'BP_DOPPELME_SERVICE', 'http://services.doppelme.com/partnerservice.asmx?WSDL' );
define ( 'BP_DOPPELME_SITE', 'http://api.doppelme.com' );


//if ( file_exists( dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' ) )
//	load_textdomain( 'bp-doppelme', dirname( __FILE__ ) . '/bp-doppelme/languages/' . get_locale() . '.mo' );




require( dirname( __FILE__ ) . '/bp-doppelme-api.php' );
require( dirname( __FILE__ ) . '/bp-doppelme-avatar.php' );

/**
 * bp_example_add_admin_menu()
 *
 * This function will add a WordPress wp-admin admin menu for your component under the
 * "BuddyPress" menu.
 */
function bp_doppelme_add_admin_menu() {
	global $bp;

	if ( !is_super_admin() )
		return false;

	require ( dirname( __FILE__ ) . '/bp-doppelme-admin.php' );

    
	add_submenu_page( 'bp-general-settings', __( 'DopplelMe Admin', 'bp-doppelme' ), __( 'DoppelMe Admin', 'bp-doppelme' ), is_multisite() ? 'manage_network_options' : 'manage_options', 'bp-doppelme-settings', 'bp_doppelme_admin' );
}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'bp_doppelme_add_admin_menu' );




/*
function bp_doppelme_setup_nav() {
	global $bp;

	bp_core_new_nav_item( array(
		'name' => __( 'Example', 'bp-example' ),
		'slug' => $bp->example->slug,
		'position' => 80,
		'screen_function' => 'bp_example_screen_one',
		'default_subnav_slug' => 'screen-one'
	) );

	$example_link = $bp->loggedin_user->domain . $bp->example->slug . '/';

	bp_core_new_subnav_item( array(
		'name' => __( 'Screen One', 'bp-example' ),
		'slug' => 'screen-one',
		'parent_slug' => $bp->example->slug,
		'parent_url' => $example_link,
		'screen_function' => 'bp_example_screen_one',
		'position' => 10
	) );

	bp_core_new_subnav_item( array(
		'name' => __( 'Screen Two', 'bp-example' ),
		'slug' => 'screen-two',
		'parent_slug' => $bp->example->slug,
		'parent_url' => $example_link,
		'screen_function' => 'bp_example_screen_two',
		'position' => 20,
		'user_has_access' => bp_is_my_profile() // Only the logged in user can access this on his/her profile
	) );
	
	bp_core_new_subnav_item( array(
		'name' => __( 'Example', 'bp-example' ),
		'slug' => 'example-admin',
		'parent_slug' => $bp->settings->slug,
		'parent_url' => $bp->loggedin_user->domain . $bp->settings->slug . '/',
		'screen_function' => 'bp_example_screen_settings_menu',
		'position' => 40,
		'user_has_access' => bp_is_my_profile() // Only the logged in user can access this on his/her profile
	) );
}
add_action( 'bp_setup_nav', 'bp_example_setup_nav' );
*/

?>