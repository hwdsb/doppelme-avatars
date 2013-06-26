<?php
//
// Place a DoppelMe avatar wherever an avatar is used within the site
//
function bp_doppelme_get_avatar($avatar, $params = '')
{       

    $DOPPELME_ID = get_user_meta( $params['item_id'], 'doppelme_key', true);
    if (! $DOPPELME_ID || $DOPPELME_ID == '') {
        $DOPPELME_ID = "BLANK";
    }
    
    $size = $params['width'];
    
    $width = $params['height'];
    $height= $params['width'];
    
    
    if ($params['object'] == 'group') {
        return $avatar;
    } elseif ($params['type'] == 'thumb') {
        return str_replace(preg_replace('/.*src=["|\'](.*?)["|\'].*/i', "$1", $avatar ), BP_DOPPELME_SITE . '/' . $size . '/' . $DOPPELME_ID . '/crop.png', $avatar);
    } elseif ($height == 100 && $width == 100) {
        return str_replace(preg_replace('/.*src=["|\'](.*?)["|\'].*/i', "$1", $avatar ), BP_DOPPELME_SITE . '/' . $size . '/' . $DOPPELME_ID . '/crop.png', $avatar);        
    } else {    
        return str_replace(preg_replace('/.*src=["|\'](.*?)["|\'].*/i', "$1", $avatar), BP_DOPPELME_SITE . '/75/' . $DOPPELME_ID . '/avatar.png?canvas_width=200' , $avatar);           
        //return $avatar . json_encode($params);
    }

}

//
// Place a DoppelMe avatar wherever an avatar is used within the site
//
function doppelme_get_avatar($avatar, $params = '', $size = '32')
{   
    
    $email = '';
    if ( is_numeric($params) ) {
        
        $user_id = (int) $params;    
        
    } elseif ( is_string($params) )  {
        if ( $user = get_user_by_email( $params ) ) {
            $user_id = $user->ID;	
        }
    } elseif ( is_object($params)  ) {
    
        $user_id = (int) $params->user_id;
        $email = $params->comment_author_email;               
    }
    
    if ($user_id == 0 && $email != '') {
        //no id - use email hash to locate guest avatar
        $DOPPELME_ID = 'EH' . md5( strtolower($email) );
        
        //awful mess for multi-site options - is there anything better?
        switch_to_blog(1);
        $USE_GRAVATAR  = get_option( 'doppelme_allow_guest_gravatars', 'Y' );
        restore_current_blog();
        if ($USE_GRAVATAR == 'Y') { 
            $AVATAR_SOURCE = BP_DOPPELME_SITE . "/{$size}/{$DOPPELME_ID}/crop.png?d=gravatar&amp;s={$size}";
        } else {
            $AVATAR_SOURCE = BP_DOPPELME_SITE . "/{$size}/{$DOPPELME_ID}/crop.png";
        }
        
    } else {
        $DOPPELME_ID = get_user_meta( $user_id, 'doppelme_key', true);
        if (! $DOPPELME_ID || $DOPPELME_ID == '') {
            $DOPPELME_ID = "BLANK";
        }    
        $AVATAR_SOURCE = BP_DOPPELME_SITE . "/{$size}/{$DOPPELME_ID}/crop.png";
    }   

    

    return "<img alt='' src='" . $AVATAR_SOURCE ."' class='avatar avatar-{$size}' height='{$size}' width='{$size}' />";               
}


add_filter("get_avatar",  'doppelme_get_avatar', 10, 4);
add_filter("bp_core_fetch_avatar",  'bp_doppelme_get_avatar', 10, 4);


/*
function bp_doppelme_avatar_activity() {
	global $bp;

	if (function_exists( 'bp_activity_add') ) {
	
		$user_id = apply_filters ('xprofile_new_avatar_user_id', $bp->displayed_user->id );
		$userlink = bp_core_get_userlink( $user_id );
		bp_activity_add(
			array(
			'user_id' => $user_id,
			'action' => apply_filters( 'xprofile_new_avatar_action', sprintf( __( '%s updated their avatar', 'buddypress'), $userlink), $user_id),
			'component' => 'profile',
			'type' => 'new_avatar'
			)
		);
	}
}
add_action( 'xprofile_avatar_uploaded', 'bp_doppelme_avatar_activity');
*/


function bp_doppelme_profile_page() {
    global $bp; 
		
    $user = $bp->loggedin_user;
    $profile_link = $bp->loggedin_user->domain . $bp->profile->slug . '/';
    $doppelme_key = get_user_meta($user->id, 'doppelme_key');
            
    bp_core_remove_subnav_item( 'profile', 'change-avatar'); 
    
    if ($doppelme_key != "") {
        //add Edit tab
        bp_core_new_subnav_item( array( 'name' => __( 'Edit Avatar', 'bp-doppelme' ), 'slug' => 'doppelme-avatar', 'parent_url' => $profile_link, 'parent_slug' => $bp->profile->slug, 'screen_function' => 'show_avatar_engine', 'position' => 50 ));
    } else {
        //add Create tab
        bp_core_new_subnav_item( array( 'name' => __( 'Create Avatar', 'bp-doppelme' ), 'slug' => 'doppelme-avatar', 'parent_url' => $profile_link, 'parent_slug' => $bp->profile->slug, 'screen_function' =>  'show_avatar_engine', 'position' => 50 ));
    }
}
add_action('xprofile_setup_nav', 'bp_doppelme_profile_page'); 


function bp_plugin_load_template_filter( $found_template, $templates ) {
    //Only filter the template location when we're on the bp-plugin component pages.
   // if ( !bp_is_current_component( 'bp-plugin' ) )
   //     return $found_template;
    global $bp;
    foreach ( (array) $templates as $template ) {
        if ( file_exists( STYLESHEETPATH . '/' . $template ) )
            $filtered_templates[] = STYLESHEETPATH . '/' . $template;
        elseif( file_exists(TEMPLATEPATH . '/' . $template ) )
            $filtered_templates[] = TEMPLATEPATH . '/' . $template;
        elseif ($bp->current_action == 'change-avatar') {
            //$filtered_templates[] = BP_PLUGIN_DIR . '/bp-themes/bp-default/' . $template;
            $filtered_templates[] = BP_PLUGIN_DIR . '../doppelme-avatars/templates/' . $template;
        }
    } 
    $found_template = $filtered_templates[0]; 
    return apply_filters( 'bp_plugin_load_template_filter', $found_template );
}
 
add_filter( 'bp_located_template', 'bp_plugin_load_template_filter', 10, 2 );


function show_avatar_engine()
{
    add_action( 'bp_template_title', 'doppelme_avatar_engine_title' );
    add_action( 'bp_template_content', 'doppelme_avatar_engine_content' );
    bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}
add_action('xprofile_screen_change_avatar','show_avatar_engine');

function doppelme_avatar_engine_title()
{
    //echo '<h4>' . __('DoppelMe Avatar', 'bp-doppelme') . '</h4>';
}
	
	
//
// Avatar Creation/Editor Engine
//
function doppelme_avatar_engine_content()
{

    global $bp;
	$doppelme_api = new DoppelMe(0, '');
    
    $user = $bp->loggedin_user; 
    
    $doppelme_api->setPartnerId(get_option('doppelme_partner_id'));
	$doppelme_api->setPartnerKey(get_option('doppelme_partner_key'));
            
    $sDOPPELME_KEY = get_user_meta($user->id, "doppelme_key", true);
    
    if ( $_POST['retrieve'] ) {
        
        $Username  = $_POST['username'];
        $Shadowkey = $_POST['shadowkey'];
    
        if ( $Username != '' && $Shadowkey != '') {
			$result = $doppelme_api->assign_user_id($user->id, $Username, $Shadowkey);
            if ( $result['StatusCode'] != 0 ) {
                //error message returned - perhaps invalid shadow key supplied				
                die();
            } else	{
				
				$sDOPPELME_KEY = $result['DoppelMeKey'];							
                if ( $sDOPPELME_KEY != "" and $sDOPPELME_KEY != "N/A" ) {
                    update_user_meta( $user->id, "doppelme_key", $sDOPPELME_KEY , true ); 
                }
                
                //loop through available avatars (user may have more than one) and format them, offering
                //user to select which one he would like to use on your site
				$sSELECT = '';
				foreach ($result['DoppelMeNames'] as $item) {
                    
					$sKey = $item['key'];
					$sName = $item['name'];
                    
                    if ($sKey == $sDOPPELME_KEY) {
                        $sSELECT = $sSELECT  . "<td><img src=" . BP_DOPPELME_SITE . "/50/" . urlencode($sKey) . "/cropb.png><br/>"; 
                        $sSELECT = $sSELECT  . $sName . "<br/><input type=radio name=doppelme_key value=\"" . $sKey . "\" checked></td>";
                    } else {
                        $sSELECT = $sSELECT  . "<td><img src=" . BP_DOPPELME_SITE . "/50/" . urlencode($sKey) . "/cropb.png><br/>" .
                        $sName . "<br/><input type=radio name=doppelme_key value=\"" . $sKey . "\" ></td>";
                    }
                }
				$sSELECT = "<table><tr>" . $sSELECT . "</tr><tr><td colspan=" . count($result['DoppelMeNames']) . " style=\"text-align: center;\"><input type=submit name=\"Select &raqno;\"></td></tr></table>";				                                
            }		
        } 
     
    } elseif ($_POST['create_new'] && $sDOPPELME_KEY == '' ) {            
		$result = $doppelme_api->create_user_account($user->id, $user->userdata->user_nicename);		
        //$sDOPPELME_KEY = $result['DoppelMeKey'] ;
        $sDOPPELME_KEY = $result;
        if (  $sDOPPELME_KEY != '' ) {                        
            update_user_meta( $user->id, "doppelme_key", $sDOPPELME_KEY , true );                
        }                                 
    }
    
    $sDOPPELME_KEY = get_user_meta($user->id, "doppelme_key", true);
    
    if ( get_option('doppelme_partner_valid') == 0 ) {
        //invalid install keys
        ?>
        <table cellspacing="10" style="width: 720px;">
        <tr>
        <td style="background-color: #EFEFEF; border: 1px solid #CCC; width: 300px; padding: 5px; vertical-align: top"> 
        
            <div style="font-size: 1.3em; margin-bottom: 15px;">DoppelMe&trade; Avatar </div>
            
            <div style="text-align: center;">
                The DoppelMe Avatar system is currently switched off. 
            </div>           
        </td>
        </tr>
        </table>
        
    <?php
    } elseif ($sSELECT != "") {
    ?>
        <table cellspacing="10" style="width: 720px;">
        <tr>
        <td style="background-color: #EFEFEF; border: 1px solid #CCC; width: 300px; padding: 5px; vertical-align: top"> 
        
            <div style="font-size: 1.3em; margin-bottom: 5px;">Choose Your DoppelMe&trade; Avatar</div>
            Choose which of your existing avatars you want to use on this site
            <div style="text-align: center;">
            <form  method="post" action ="<?php echo $bp->loggedin_user->domain . $bp->profile->slug . '/change-avatar/'; ?>">
                <?php echo $sSELECT; ?> 
            </form>
            </div>           
        </td>
        </tr>
        </table>
      
    <?php
    
    } elseif ( $_POST['doppelme_key'] ) {
    
        $sDOPPELME_KEY = $_POST['doppelme_key'];
        $sDOPPELME_KEY = trim($sDOPPELME_KEY) . '';		
        
        delete_user_meta( $user->id, "doppelme_key");
        update_user_meta( $user->id, "doppelme_key", $sDOPPELME_KEY);             
        
        
        echo '<div style="text-align: center;">';
        echo '<h4>Your Avatar Has Been Selected</h4>';
        echo '<img src="http://www.doppelme.com/75/' . $sDOPPELME_KEY . '/avatar.png?canvas_width=400">';
        echo '<br/><br/>';            
        echo '</div>';

    } elseif ($sDOPPELME_KEY == "") {
        
    ?>			
     	
        <table cellspacing="10" style="width: 720px;">
        <tr>
        <td style="background-color: #EFEFEF; border: 1px solid #CCC; width: 300px; padding: 5px; vertical-align: top"> 
            
                <div style="font-size: 1.3em; margin-bottom: 5px;">Create A New DoppelMe&trade; Avatar</div>
                Create a new avatar to use on this site.
                <div style="text-align: center;">
                <form  method="post" action ="<?php echo $bp->loggedin_user->domain . $bp->profile->slug . '/change-avatar/'; ?>">
                    <input type="submit"  name="create_new" value="Create &raquo;" >
                </form>
                </div>           
        </td>
        <td>&nbsp;</td>
        <td style="background-color: #EFEFEF; border: 1px solid #CCC; width: 400px; padding: 5px; vertical-align: top">  
        
                <div style="font-size: 1.3em; margin-bottom: 5px;">Already Have A DoppelMe&trade; Avatar?</div>
                If you already have a DoppelMe avatar that you would like to use on this site, 
                you can link it to your account here. (You can find your shadow key from the <strong>settings</strong>
                page on the <a href="http://www.doppelme.com">DoppelMe Website</a>.)
                <div style="text-align: center;">
                <form  method="post" action ="<?php echo $bp->loggedin_user->domain . $bp->profile->slug . '/change-avatar/'; ?>">
                    <table cellspacing="0" cellpadding="0" style="width: 350px;">
                    <tr><td>DoppelMe username	</td><td> <input type="text" name="username" ></td></tr>
                    <tr><td>DoppelMe shadow key	</td><td> <input type="text" name="shadowkey" ></td></tr>
                    <tr><td colspan="2" style="text-align: center;">
                        <input type="submit"  name="retrieve" value="Retrieve Avatar &raquo;" >
                    </td></tr>
                    </table>
                </form>
                </div>            
        </td>
        </tr>
        </table>
            
    <?php
    } elseif ($_POST['create_new'] && $sDOPPELME_KEY != "") { 
    
        
        echo '<div style="text-align: center;">';
        echo '<h4>Your Avatar Has Been Created</h4>';
        echo '<img src="http://www.doppelme.com/75/' . $sDOPPELME_KEY . '/avatar.png?canvas_width=400">';
        echo '<br/><br/>';
        echo '<a href="' . $bp->loggedin_user->domain . $bp->profile->slug . '/change-avatar/">Now lets add some clothes!</a>';
        echo '</div>';
        
    } elseif ($_GET['view'] && $sDOPPELME_KEY != "") { 
        
        echo '<div style="text-align: center;">';
        echo '<h4>Your Avatar Has Been Updated</h4>';
        echo '<img src="http://www.doppelme.com/75/' . $sDOPPELME_KEY . '/avatar.png?canvas_width=400">';
        echo '<br/><br/>';            
        echo '</div>';
		
				  
		do_action('xprofile_avatar_uploaded');
          
    } else {
    
        // User has an avatar - so lets allow them to edit it
        //  
        $sLANG = "EN";
        $sCALLBACK_URL = $bp->loggedin_user->domain . $bp->profile->slug . '/change-avatar/?view=saved';    
		$sVALIDATION_KEY = $doppelme_api->get_user_validation_key($user->id);
        
        if ($sVALIDATION_KEY != '') {
            $iframe_url = 	BP_DOPPELME_SITE . "/partner/partner_validate.asp?stripped=yes&pid=" . get_option('doppelme_partner_id') . 
            "&puid=" . $user->id . "&validkey=" . $sVALIDATION_KEY . "&doppelmekey=" . $sDOPPELME_KEY . "&lang=" . $sLANG . "&callback=" . urlencode($sCALLBACK_URL);
            
            $engine = 
            '<iframe src="' . $iframe_url . '" ' . 
            'style="overflow:hidden;width:660px;height:450px;border: 0;border-collapse: collapse;margin: 0 auto;" ' . 
            'frameborder="0" marginwidth="0" marginheight="0" scrolling="no" vspace="5" hspace = "0"></iframe>';
            
        
        } else {
            //this users avatar is invalid - they are not the owner
            $engine = '<div style="text-align: center; padding: 30px">Invalid avatar assigned to this account</div>';
        }
        
        ?>           
        <div style="height: 450px;margin: 0 auto;text-align: center; width: 660px; border: 1px solid #CCC">		
        <?php echo $engine; ?>
        </div>
        <?php
    }
}

?>