<?php

define ( 'BP_DOPPELME_IS_INSTALLED', 1 );
define ( 'BP_DOPPELME_VERSION', '1.5' );
define ( 'BP_DOPPELME_SITE', 'http://api.doppelme.com' );




require( dirname( __FILE__ ) . '/doppelme.php' );
require( dirname( __FILE__ ) . '/bp-doppelme-avatar.php' );

//
// The DoppelMe API object
$doppelme_api = new DoppelMe(0, '');

/**
 * bp_example_add_admin_menu()
 *
 * This function will add a WordPress wp-admin admin menu for your component under the
 * "BuddyPress" menu.
 */
function bp_doppelme_add_admin_menu() {
	global $bp;
	global $doppelme_api;

	if ( !is_super_admin() )
		return false;

	require ( dirname( __FILE__ ) . '/bp-doppelme-admin.php' );

	add_submenu_page( 'bp-general-settings', __( 'DopplelMe Admin', 'bp-doppelme' ), __( 'DoppelMe Admin', 'bp-doppelme' ), is_multisite() ? 'manage_network_options' : 'manage_options', 'bp-doppelme-settings', 'bp_doppelme_admin' );
}
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'bp_doppelme_add_admin_menu' );

?>