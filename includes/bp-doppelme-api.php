<?php



//-------------------------------------
//DoppelMe Functions
//-------------------------------------

function TestSetup() {
	
	$client = new SoapClient( BP_DOPPELME_SERVICE , array('trace' => 1)  ); 	
	
	echo "\n\nCurrent Version:\n";
	var_dump($client->Version()->VersionResult);		
}


function PartnerServiceCall($sURL, $sPARAMS) {
	
	$client = new SoapClient( BP_DOPPELME_SERVICE ); 
	$xml = $client->__soapCall( $sURL, $sPARAMS );
	
	return $xml;
}

// Creates a new DoppelMe account for this USER_ID and returns an avatar code (of form DM123456ABC)
// for their first avatar
function CreateDoppelMeAccount($DM_PARTNER_ID, $DM_PARTNER_KEY, $USER_ID, $AVATAR_NAME) {
    $sURL		= "CreateDoppelMeAccount";	        
    $sDATA		= array("partner_id" 	=> $DM_PARTNER_ID ,
                "partner_key" 	        => $DM_PARTNER_KEY ,
                "doppelme_name"         => urlencode($AVATAR_NAME),
                "partner_user_id"       => $USER_ID ,
                "referral_id" 	        => "0",
                "callback_url" 	        => "not needed",
                "is_test" 	            => false
                );				            
    $result = PartnerServiceCall($sURL, array($sDATA) );				            
    $xmlDoc = simplexml_load_string( $result->CreateDoppelMeAccountResult );
                    
    $sDOPPELME_KEY	    = $xmlDoc->DoppelMeKey;
    $sVALIDATION_KEY    = $xmlDoc->ValidationKey;
    $sSTATUS		    = $xmlDoc->StatusInfo;
    $DOPPELME_NAMES     = $xmlDoc->DoppelMeNames;
                   
    $sDOPPELME_KEY      = trim($sDOPPELME_KEY) . '';
    $sVALIDATION_KEY    = trim($sVALIDATION_KEY) . '';
    $sSTATUS            = trim($sSTATUS) . '';
                   
    if ($sSTATUS == '"User already exists."') {                
       //there is already a DoppelMe account associated with this USER_ID
       //so we just return the first avatar of that account
       $sDOPPELME_KEY = $DOPPELME_NAMES->DoppelMeName;
       $sDOPPELME_KEY = trim($sDOPPELME_KEY) . '';
    }
    
    if (  $sDOPPELME_KEY == "" || $sDOPPELME_KEY == "N/A") {
        //There appears to have been a problem creating the account
        return '';
    } else {
        return trim($sDOPPELME_KEY) . '';
    }
}

// Each time a user edits their avatar, we need to obtain a validation key
// to manage the handshake process between the editor and the avatar API
function GetUserValidationKey($DM_PARTNER_ID, $DM_PARTNER_KEY, $USER_ID) {
    $key = GetUserDetails($DM_PARTNER_ID, $DM_PARTNER_KEY, $USER_ID, "ValidationKey"); 
    if ($key == 'DummyValidationKey') {
        $key = '';
    }
    return $key;
}



// Retrieve information about a users avatar account
function GetUserDetails($DM_PARTNER_ID, $DM_PARTNER_KEY, $USER_ID, $TAG) {
    
    $sURL	= "GetDetailsFromPartnerUserID";	
    $sDATA  = array(
            "partner_id" 	    => $DM_PARTNER_ID,
            "partner_key" 	    => $DM_PARTNER_KEY, 
            "partner_user_id"   => $USER_ID,
            "is_test"		    => false		);


    $xml = PartnerServiceCall($sURL, array($sDATA) );				    
    $xmlDoc = simplexml_load_string( $xml->GetDetailsFromPartnerUserIDResult );
    
    $sSTATUS_CODE    = $xmlDoc->StatusCode;
    $sSTATUS_INFO    = $xmlDoc->StatusInfo;
    $sVALIDATION_KEY = $xmlDoc->ValidationKey;
    $sCOINS          = $xmlDoc->Coins;
    $sALLOW_PRANK    = $xmlDoc->AllowPrank;
    
    if ($TAG == "Coins") {
        return trim($sCOINS) . '';
    } elseif ($TAG == "AllowPrank") {
        return trim($sALLOW_PRANK) . '';
    } elseif ($TAG == "StatusInfo") {
        return trim($sSTATUS_INFO) . '';
    } else {        
        return trim($sVALIDATION_KEY) . '';
    }
}

// Resets a users avatar. If DefaultClothing has been set, then the avatar
// will be dressed in them, otherwise will be left naked.
// Requires DoppelMe Service 1.3.0 or higher
function ResetUserClothing($DM_PARTNER_ID, $DM_PARTNER_KEY, $USER_ID) {


    $sURL	= "ResetDoppelMe";	
    $sDATA  = array(
            "partner_id" 	    => $DM_PARTNER_ID,
            "partner_key" 	    => $DM_PARTNER_KEY, 
            "partner_user_id"   => $USER_ID,
            "is_test"		    => false		);


    $xml = PartnerServiceCall($sURL, array($sDATA) );				    
    $xmlDoc = simplexml_load_string( $xml->ResetDoppelMe );
    
    $sSTATUS_CODE    = $xmlDoc->StatusCode;
    $sSTATUS_INFO    = $xmlDoc->StatusInfo;

    
    return trim($sSTATUS_INFO) . '';
}



?>