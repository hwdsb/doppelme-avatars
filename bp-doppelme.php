<?php
/*
Plugin Name: BP DoppelMe
Description: Integrate DoppelMe Avatars with BuddyPress
Version: 2.0
Author: r-a-y
Author URI: http://profiles.wordpress.org/r-a-y
*/

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Load BP DoppelMe when BuddyPress is loaded.
 */
function bp_doppelme_loaded() {
	buddypress()->doppelme = BP_Doppelme::init();
}
add_action( 'bp_loaded', 'bp_doppelme_loaded' );

/**
 * Doppelme.com avatar integration with BuddyPress.
 */
class BP_Doppelme {
	/**
	 * Partner ID from DoppelMe Partner account
	 *
	 * @var int
	 */
	public $partner_id  = 0;

	/**
	 * Partner key from DoppelMe Partner account
	 *
	 * @var string
	 */
	public $partner_key = '';

	/**
	 * Custom slug used for DoppelMe
	 *
	 * Used on /members/USER/profile/[change-picture]
	 *
	 * @var string
	 */
	public $slug = 'change-picture';

	/**
	 * Static initializer.
	 */
	public static function init() {
		return new self();
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// PHP Soap extension required
		// @todo add admin notice
		if ( ! class_exists( 'SoapClient' ) ) {
			return;
		}

		$this->properties();

		// add notice if missing pertinent info
		//
		// @todo add admin notice
		if ( empty( $this->partner_id ) || empty( $this->partner_key ) ) {
			return;
		}

		$this->includes();
		$this->hooks();

		// do what you feel like!
		do_action( 'bp_doppelme_loaded', $this );
	}

	/**
	 * Set class properties.
	 */
	protected function properties() {
		// Use constant if available
		if ( defined( 'BP_DOPPELME_PARTNER_ID' ) ) {
			$this->partner_id = constant( 'BP_DOPPELME_PARTNER_ID' );

		// Backward compatibility
		} else {
			$this->partner_id = bp_get_option( 'doppelme_partner_id' );
		}

		// Use constant if available
		if ( defined( 'BP_DOPPELME_PARTNER_KEY' ) ) {
			$this->partner_key = constant( 'BP_DOPPELME_PARTNER_KEY' );

		// Backward compatibility
		} else {
			$this->partner_key = bp_get_option( 'doppelme_partner_key' );
		}

		if ( defined( 'BP_DOPPELME_SLUG' ) ) {
			$this->slug = sanitize_title( constant( 'BP_DOPPELME_SLUG' ) );
		}
	}

	/**
	 * Includes.
	 */
	protected function includes() {
		if ( ! class_exists( 'DoppelMe' ) ) {
			require dirname( __FILE__ ) . '/includes/doppelme.php';
		}

		require dirname( __FILE__ ) . '/includes/functions.php';
	}

	/**
	 * Hooks.
	 */
	protected function hooks() {
		// add member subnav under "Profile"
		add_action( 'bp_xprofile_setup_nav', array( $this, 'add_subnav' ) );

		// edit xprofile toolbar nav
		add_filter( 'bp_xprofile_admin_nav', array( $this, 'edit_toolbar_nav' ) );

		// edit "Edit Member" admin menu
		add_action( 'admin_bar_menu',        array( $this, 'edit_user_admin_menu' ), 999 );
	}

	/**
	 * Manipulate BP profile sub-navigation tabs.
	 */
	public function add_subnav() {
		// Bye, bye BP avatars!
		bp_core_remove_subnav_item( 'profile', 'change-avatar' );

		// Determine user to use
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		}

		$profile_link = trailingslashit( $user_domain . buddypress()->profile->slug );

		// Create a new profile subnav item for Doppelme
		bp_core_new_subnav_item( array(
			'name'            => __( 'Change Profile Picture', 'bp-doppelme' ),
			'slug'            => $this->slug,
			'parent_url'      => $profile_link,
			'parent_slug'     => buddypress()->profile->slug,
			'screen_function' => array( 'BP_Doppelme_Screen', 'init' ),
			'position'        => 30,
			'user_has_access' => bp_core_can_edit_settings()
		) );
	}

	/**
	 * Edit BP's profile toolbar nav to use our custom slug and title.
	 *
	 * @param  array $retval The BP profile toolbar nav items
	 * @return array
	 */
	public function edit_toolbar_nav( $retval ) {
		if ( empty( $retval ) ) {
			return $retval;
		}

		$slug = buddypress()->doppelme->slug;

		foreach ( $retval as $key => $nav ) {
			if ( 'my-account-xprofile-change-avatar' === $nav['id'] ) {
				$retval[$key]['title'] = __( 'Change Profile Picture', 'bp-doppelme' );
				$retval[$key]['href']  = str_replace( "/change-avatar/", "/{$slug}/", $retval[$key]['href'] );
			}
		}

		return $retval;
	}

	/**
	 * Edit BP's "Edit Member" adminbar menu.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function edit_user_admin_menu( $wp_admin_bar ) {
		// Only show if viewing a user
		if ( ! bp_is_user() ) {
			return false;
		}

		// Don't show this menu to non site admins or if you're viewing your own profile
		if ( ! current_user_can( 'edit_users' ) || bp_is_my_profile() ) {
			return;
		}

		if ( ! bp_is_active( 'xprofile' ) ) {
			return;
		}

		if ( buddypress()->avatar->show_avatars ) {
			$wp_admin_bar->add_menu( array(
				'parent' => buddypress()->user_admin_menu_id,
				'id'     => buddypress()->user_admin_menu_id . '-change-avatar',
				'title'  => __( "Edit Profile Picture", 'bp-doppelme' ),
				'href'   => bp_get_members_component_link( 'profile', buddypress()->doppelme->slug )
			) );
		}
	}
}

/**
 * Screen handler for BP Doppelme.
 */
class BP_Doppelme_Screen {
	/**
	 * Init code.
	 */
	public static function init() {
		self::validate();

		$key = bpdpl_get_user_key();

		if ( empty( $key ) ) {
			$content = 'new';
		} else {
			// See http://partner.doppelme.com/integration_avatars.php
			// Doesn't quite work at the moment...
			if ( 'EH' === substr( $key, 0, 2 ) ) {
				$content = 'pic_from_email';
			} else {
				$content = 'edit';
				add_action( 'bp_template_title', array( __CLASS__, 'title_edit' ) );
			}
		}

		add_action( 'bp_template_content', array( __CLASS__, "content_{$content}" ) );

		bp_core_load_template( 'members/single/plugins' );
	}

	/**
	 * Validate POST or GET submissions.
	 */
	protected static function validate() {
		// create new doppelme account
		if ( ! empty( $_POST['create'] ) ) {
			if ( ! wp_verify_nonce( $_POST['bpdpl-new'], 'bpdpl_new_account' ) ) {
				wp_die( 'Oops!  Looks like something went wrong there.' );
			}

			$api_call = bpdpl_api()->create_user_account( bp_displayed_user_id(), buddypress()->displayed_user->userdata->user_nicename );

			// error
			if ( empty( $api_call ) ) {
				bp_core_add_message( __( 'There was an error creating your new Doppelme account.  Please try again.', 'bp-doppelme' ), 'error' );

			// success
			} else {
				bp_core_add_message( __( 'Congratulations!  You can now start customizing your profile picture.', 'bp-doppelme' ) );
				bp_update_user_meta( bp_displayed_user_id(), 'doppelme_key', $api_call );
			}

			bp_core_redirect( bp_displayed_user_domain() . buddypress()->profile->slug . '/' . buddypress()->doppelme->slug . '/' );
			die();

		// use existing doppelme account
		} elseif ( ! empty( $_POST['existing'] ) ) {
			if ( ! wp_verify_nonce( $_POST['bpdpl-existing'], 'bpdpl_existing_account' ) ) {
				wp_die( 'Oops!  Looks like something went wrong there.' );
			}

			// See http://partner.doppelme.com/integration_avatars.php
			bp_update_user_meta( bp_displayed_user_id(), 'doppelme_key', 'EH' . md5( strtolower( bp_get_displayed_user_email() ) ) );

			// Now, mirror the DoppelMe pic
			$move_avatar = bpdpl_mirror_avatar();

			if ( ! empty( $move_avatar['error'] ) ) {
				bp_core_add_message( sprintf( __( 'Profile picture could not be mirrored.  Error was: %s', 'buddypress' ), $move_avatar['error'] ), 'error' );

			}

			bp_core_redirect( bp_displayed_user_domain() . buddypress()->profile->slug . '/' . buddypress()->doppelme->slug . '/' );
			die();

		// saving avatar changes from Doppelme
		} elseif ( ! empty( $_GET['saved'] ) ) {
			$move_avatar = bpdpl_mirror_avatar();

			if ( ! empty( $move_avatar['error'] ) ) {
				bp_core_add_message( sprintf( __( 'Profile picture could not be mirrored.  Error was: %s', 'buddypress' ), $move_large['error'] ), 'error' );

			} else {
				do_action( 'xprofile_avatar_uploaded' );
				bp_core_add_message( __( 'Your profile picture has been successfully updated.', 'bp-doppelme' ) );
			}

			bp_core_redirect( bp_displayed_user_domain() . buddypress()->profile->slug . '/' . buddypress()->doppelme->slug . '/' );
			die();

		// disconnect DoppelMe
		} elseif ( ! empty( $_GET['bpdpl-disconnect'] ) ) {
			if ( ! wp_verify_nonce( $_GET['bpdpl-disconnect'], 'bpdpl_disconnect' ) ) {
				wp_die( 'Oops!  Looks like something went wrong there.' );
			}

			bp_delete_user_meta( bp_displayed_user_id(), 'doppelme_key' );

			// delete existing avatar
			bp_core_delete_existing_avatar( array(
				'item_id' => $user_id
			) );

			bp_core_redirect( bp_displayed_user_domain() . buddypress()->profile->slug . '/' . buddypress()->doppelme->slug . '/' );
			die();
		}
	}

	/**
	 * Content block that shows when a user doesn't have a DoppelMe key yet.
	 *
	 * Excuse the inline CSS styles!
	 */
	public static function content_new() {
	?>

		<h3 style="float:left;"><?php _e( 'Create a New Profile Picture with DoppelMe', 'bp-doppelme' ); ?></h3>

		<img alt="<?php _e( 'Example of a Doppelme profile picture', 'bp-doppelme' ); ?>" title="<?php _e( 'Example of a Doppelme profile picture', 'bp-doppelme' ); ?>" src="http://doppelme.com/images/examples/fade6.gif" style="float:right;border:0;box-shadow:none;" />

		<p style="clear:left;"><?php _e( 'With <a href="http://www.doppelme.com">DoppelMe</a>, you can create a cool, graphical likeness of yourself for use as your profile picture on this site.', 'bp-doppelme' ); ?></p>

		<p><?php _e( 'Get started by clicking on the <strong>Create</strong> button below:', 'bp-doppelme' ); ?></p>

		<form action="<?php echo esc_url( bp_get_requested_url() ); ?>" class="standard-form" method="post">
			<?php wp_nonce_field( 'bpdpl_new_account', 'bpdpl-new' ); ?>
			<input type="submit" name="create" value="<?php _e( 'Create &raquo;', 'bp-doppelme' ); ?>" />
		</form>

		<hr style="margin-left:0;" />

		<h3><?php _e( 'Or Use Your Existing Profile Picture from DoppelMe', 'bp-doppelme' ); ?></h3>

		<p><?php _e( 'If you already use DoppelMe, you can use your DoppelMe profile picture on this site as well.', 'bp-doppelme' ); ?></p>

		<p><?php _e( 'If you select this option, we will use your email address to try and find an existing DoppelMe picture.', 'bp-doppelme' ); ?></p>

		<form action="<?php echo esc_url( bp_get_requested_url() ); ?>" class="standard-form" method="post">
			<?php wp_nonce_field( 'bpdpl_existing_account', 'bpdpl-existing' ); ?>
			<input type="submit" name="existing" value="<?php _e( 'Submit', 'bp-doppelme' ); ?>" style="margin-top:1em;" />
		</form>

	<?php
	}

	/**
	 * Add a title when a user is editing their DoppelMe profile pic.
	 */
	public static function title_edit() {
		_e( 'Make Changes to Your Profile Picture', 'bp-doppelme' );
	}

	/**
	 * Content block allowing a user to edit their DoppelMe profile pic.
	 */
	public static function content_edit() {
		// See http://partner.doppelme.com/integration_creator.php
		$host           = is_ssl() ? 'https' : 'http';
		$partner_id     = buddypress()->doppelme->partner_id;
		$validation_key = bpdpl_api()->get_user_validation_key( bp_displayed_user_id() );
		$doppelme_key   = bpdpl_get_user_key();
		$callback       = urlencode( bp_get_requested_url() . '?saved=1' );

		// fudge lang for DoppelMe's custom locale abbreviation
		$lang = apply_filters( 'bpdpl_locale', strtoupper( substr( get_locale(), 0, 2 ) ) );
		switch ( $lang ) {
			case 'RU' :
				$lang = 'RS';
				break;

			case 'SK' :
				$lang = 'SI';
				break;
		}

		// DoppelMe supports only a few languages
		// See http://partner.doppelme.com/integration_creator.php
		//
		// Default to English if not matching these locales
		if ( false === in_array( $lang, array( 'DA', 'NL', 'RS', 'SI' ) ) ) {
			$lang = 'EN';
		}

		$iframe_url = '%s://api.doppelme.com/partner/partner_validate.asp?pid=%d&puid=%s&validkey=%s&doppelmekey=%s&callback=%s&lang=%s';
		$iframe_url = sprintf( $iframe_url, $host, $partner_id, bp_displayed_user_id(), $validation_key, $doppelme_key, $callback, $lang );
	?>

		<div style="width:640px;height:400px;">
			<iframe src="<?php echo esc_url( $iframe_url ); ?>" style="width:640px;height:400px;border:0;border-collapse:collapse;" frameborder="0" marginwidth="0" marginheight="0" scrolling="no" vspace="5" hspace="0"></iframe>
		</div>

		<a href="<?php bpdpl_the_disconnection_url(); ?>"><?php _e( 'Remove DoppelMe', 'bp-doppelme' ); ?></a>
	<?php
	}

	/**
	 * Content block for a user with an already-associated DoppelMe avatar.
	 */
	public static function content_pic_from_email() {
	?>

		<p><?php _e( 'We have used your email address to fetch your DoppelMe profile picture.', 'bp-doppelme' ); ?></p>
		<p><?php _e( 'If the profile picture does not look correct, click on the <strong>Create</strong> button to get started on creating a brand-new profile picture.', 'bp-doppelme' ); ?></p>

		<form action="<?php echo esc_url( bp_get_requested_url() ); ?>" class="standard-form" method="post">
			<?php wp_nonce_field( 'bpdpl_new_account', 'bpdpl-new' ); ?>
			<input type="submit" name="create" value="<?php _e( 'Create &raquo;', 'bp-doppelme' ); ?>" />
		</form>

	<?php
	}

}
