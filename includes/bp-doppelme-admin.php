<?php

function register_mysettings() {
    register_setting( 'doppelme-settings-group', 'page' );
	register_setting( 'doppelme-settings-group', 'doppelme_partner_id' );
	register_setting( 'doppelme-settings-group', 'doppelme_partner_key' );	
    
    register_setting( 'doppelme-settings-group', 'page' );
	register_setting( 'doppelme-settings-group', 'doppelme_allow_guest_gravatars' );
	
}
add_action( 'admin_init', 'register_mysettings' );


function bp_doppelme_admin() {
	global $bp;

    $update_message = '';
	if ( isset( $_POST['submit1'] ) && check_admin_referer('doppelme-settings') ) {
       
        if (! is_numeric($_POST['doppelme_partner_id']) ) {		
            $update_message = 'Your DoppelMe Partner ID should be numeric. Please double-check your entry.';            
        } elseif ( (int)$_POST['doppelme_partner_id'] != $_POST['doppelme_partner_id'] ) {
            $update_message = 'Your DoppelMe Partner ID doesn\'t look right. Please double-check your entry.';
            
        } else {
			
            update_option( 'doppelme_partner_id', $_POST['doppelme_partner_id'] );
            update_option( 'doppelme_partner_key', $_POST['doppelme_partner_key'] );
            update_option( 'doppelme_partner_valid', doppelme_test_permissions() );
            
            $update_message = 'Settings updated.';            
        }
	}
    
    if ( isset( $_POST['submit2'] ) && check_admin_referer('doppelme-settings') ) {
       
        
        if ( $_POST['doppelme_allow_guest_gravatars'] == "Y" ) {		
            //update_option( 'doppelme_allow_guest_gravatars', 'Y' );
            
            delete_option( 'doppelme_allow_guest_gravatars' );
            add_option( 'doppelme_allow_guest_gravatars', 'Y', '', 'yes' );
        } else {
            delete_option( 'doppelme_allow_guest_gravatars' );
            add_option( 'doppelme_allow_guest_gravatars', 'N', '', 'yes' );
            
            //update_option( 'doppelme_allow_guest_gravatars', 'N' );			            
        }
        $update_message = 'Preferences updated.';            
	}
    
    $server = $_SERVER['SERVER_ADDR'];
    if ($server == '' ) {
        $server = $_SERVER['SERVER_NAME'];
    }

	$partner_id = get_option( 'doppelme_partner_id' );
	$partner_key = get_option( 'doppelme_partner_key' );
    $partner_key = get_option( 'doppelme_partner_key' );
    $allow_gravatars = get_option( 'doppelme_allow_guest_gravatars', 'N' );
	
	
?>
	<div class="wrap">
		<h2><?php _e( 'Doppelme&trade; Avatar Admin', 'bp-doppelme' ) ?></h2>
		<br />

		<?php 
		if ( $update_message != "" ) { 
			echo "<div id='message' class='updated fade'><p>" . __( $update_message, 'bp-doppelme' ) . "</p></div>";
		}
		?>
		
		
        
        <div style="font-size: 18px;margin-top: 30px;">DoppelMe Avatar Install Status</div>
		
		<table style="width: 760px;">
		<tr><td>
        <div style="background-color: #EFEFEF; border: 1px solid #CCC; width: 450px; padding: 5px; vertical-align: top"> 
        
			<table style="width: 100%; text-align: center;">              
			<tr><td style="width: 300px;">Server IP Address</td>
				<td style="width: 150px;"><?php echo $server; ?></td>
			</tr>
					
			<tr><td>DoppelMe API Connection</td>
			<?php
			if ( doppelme_api_version() ) {
			   echo '<td style="background-color: #4D4">Success</td><td></td>';
			} else {
				echo '<td style="background-color: #D44; ">Failed</td>';
			}
			?>
			</tr>
			
			<tr><td>SOAP Module Available</td>
			<?php
			if ( extension_loaded("soap") ) {
			   echo '<td style="background-color: #4D4">Success</td>';
			} else {
				echo '<td style="background-color: #D44; ">Failed</td>';
				$error = $error .
				'<li>' .
				'This plugin requires the SOAP PHP extension. You will need to enable this in your PHP installation set-up.' .
				'</li>';
			}
			?>
			</tr>
			
			<tr><td>Simple XML Module Available</td>
			<?php
			if ( extension_loaded("SimpleXML") ) {
			   echo '<td style="background-color: #4D4">Success</td>';
			} else {
				echo '<td style="background-color: #D44; ">Failed</td>';
				$error = $error .
				'<li>' .
				'This plugin requires the SimpleXML PHP extension. You will need to enable this in your PHP installation set-up.' .
				'</li>';
			}
			?>
			</tr>
			
			
			<tr><td>Partner Key Setup</td>
			<?php
			if ( $partner_id && $partner_key ) {
			   echo '<td style="background-color: #4D4">Success</td><td></td>';
			} else {
				echo '<td style="background-color: #D44; ">Failed</td>';
				
				$error = $error .
				'<li>' .
				'You need to supply your DoppelMe PartnerID and PartnerKey. If you dont have these, visit <a href="http://partner.doppelme.com">partner.doppelme.com</a> to obtain them.' .
				'</li>';
				
			}
			?>
			</tr>
			
			<tr><td>Access Permissions</td>
			<?php
			if ( doppelme_test_permissions() == 1) {
			   echo '<td style="background-color: #4D4">Success</td><td></td>';
			} else {
				echo '<td style="background-color: #D44; ">Failed</td>';
				$error = $error .
				'<li>' .
				'You may need to double check your PartnerID and PartnerKey details. Also please ensure that your IP address is set-up correctly at <a href="http://partner.doppelme.com">partner.doppelme.com</a>.' .
				'</li>';
			}
			?>
			</tr>
			</table>
       
		</div>
		</td>
        <td style="vertical-align: top; padding-left: 10px;">
        <?php 
		if ($error != '') {
			echo '<div style="width: 300px; border: 1px dotted #FC0; background-color #FFC;">';
			echo '<ul>';
			echo $error;
			echo '</ul>';
			echo '</div>';
		} 
		?>
		</td></tr>
		</table>
        
        
		<form action="admin.php?page=bp-doppelme-settings" name="doppelme-settings-form" id="doppelme-settings-form" method="post">
        <?php settings_fields( 'doppelme-settings-group' ); ?>        
        <div style="font-size: 18px; margin-top: 30px;">DoppelMe Avatar Install Settings</div>		
        <div style="background-color: #EFEFEF; border: 1px solid #CCC; width: 450px; padding: 5px; vertical-align: top"> 
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="doppelme_partner_id"><?php _e( 'DoppelMe Partner ID', 'bp-doppelme' ) ?></label></th>
					<td>
						<input name="doppelme_partner_id" type="text" id="doppelme_partner_id" value="<?php echo attribute_escape( $partner_id ); ?>" size="30" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="doppelme_partner_key"><?php _e( 'DoppelMe Partner Key', 'bp-doppelme' ) ?></label></th>
					<td>
						<input name="doppelme_partner_key" type="text" id="doppelme_partner_key" value="<?php echo attribute_escape( $partner_key ); ?>" size="30" />
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="submit1" value="<?php _e( 'Save Settings', 'bp-doppelme' ) ?>"/>
			</p>
			<?php
			wp_nonce_field( 'doppelme-settings' );
			?>
        </div>
		</form>
        
        <form action="admin.php?page=bp-doppelme-settings" name="doppelme-prefs-form" id="doppelme-prefs-form" method="post">
        <?php settings_fields( 'doppelme-settings-group' ); ?>        
        <div style="font-size: 18px; margin-top: 30px;">DoppelMe Avatar Preferences</div>		
        <div style="background-color: #EFEFEF; border: 1px solid #CCC; width: 450px; padding: 5px; vertical-align: top"> 
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="doppelme_allow_guest_gravatars"><?php _e( 'Allow Guest Gravatars', 'bp-doppelme' ) ?></label></th>
					<td>
                        <?php if ($allow_gravatars == 'Y') { ?>
                            <input name="doppelme_allow_guest_gravatars" type="checkbox" id="doppelme_allow_guest_gravatars" value="Y" checked="checked" />
                        <?php } else {?>
                            <input name="doppelme_allow_guest_gravatars" type="checkbox" id="doppelme_allow_guest_gravatars" value="Y" />
                        <?php } ?>
					</td>
				</tr>
				
			</table>
			<p class="submit">
				<input type="submit" name="submit2" value="<?php _e( 'Save Preferences', 'bp-doppelme' ) ?>"/>
			</p>
			<?php
			wp_nonce_field( 'doppelme-settings' );
			?>
        </div>
		</form>
        
	</div>
	
	
<?php
}





function doppelme_api_version()    
{	
	$client = new SoapClient( BP_DOPPELME_SERVICE , array('trace' => 1)  ); 		       
	return($client->Version()->VersionResult);	
}
    
function doppelme_test_permissions()    
{        
		
	$DM_PARTNER_ID  = get_option('doppelme_partner_id');
	$DM_PARTNER_KEY = get_option('doppelme_partner_key');
	
	//Simple test getting details of avatar associated with UserID = 1
	//(Note that it doesnt matter whether this user has an avatar account, we
	//are just interested in the whether we get an access denied error)
	$status = GetUserDetails($DM_PARTNER_ID, $DM_PARTNER_KEY, 1, "StatusInfo");
	
	
	if ($status == '"Access denied"') {
		return 0;
	} else {
		return 1;
	}
}
?>