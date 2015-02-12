<?php
//-------------------------------------
// DoppelMe PHP SDK 1.5.1
//-------------------------------------

define ( 'DM_SERVICE_URL', 'http://services.doppelme.com/1.5.1/PartnerService.asmx?WSDL' );


class DoppelMe
{
    protected $partnerId;
    protected $partnerKey;

    public function __construct($partnerId, $partnerKey) {
        $this->setPartnerId($partnerId);
        $this->setPartnerKey($partnerKey);
    }

    public function setPartnerId($partnerId) {
        $this->partnerId = $partnerId;
        return $this;
    }
    public function getPartnerId() {
        return $this->partnerId;
    }
    public function setPartnerKey($partnerKey) {
        $this->partnerKey = $partnerKey;
        return $this;
    }
    public function getPartnerKey() {
        return $this->partnerKey;
    }

    function _api($sMETHOD, $sPARAMS) {
        $client = new SoapClient( DM_SERVICE_URL, array('trace' => false, 'cache_wsdl' => WSDL_CACHE_BOTH ) );
        try {
            $xml = $client->__soapCall( $sMETHOD, $sPARAMS );
        } catch (SoapFault $client) {
            die( $client->getMessage() );
        } catch (Exception $e) {
            die( $sMETHOD . ': ' . $e->getMessage());
        }
        return $xml;
    }


    // Returns current api version. Works regardless of IP whitelists or credentials
    // so ideal initial test for connectivity
    public function version()
    {
        $sPARAMS = array(     );
        $result =  $this->_api("Version", array($sPARAMS) );
        return($result->VersionResult);
    }

	// Creates a new DoppelMe account for this USER_ID and returns an avatar code (of form DM123456ABC)
	// for their first avatar
	function assign_user_id($USER_ID, $USER_NAME, $SHADOW_KEY) {

		$sPARAMS  = array(
            "partner_id" 	    	=> $this->getPartnerId(),
            "partner_key" 	    	=> $this->getPartnerKey(),
			"partner_user_id" 	    => $USER_ID,
			"doppelme_username" 	=> $USER_NAME,
			"doppelme_shadow_key" 	=> $SHADOW_KEY,
			"is_test" 				=> false
			);
        $result =  $this->_api("AssignPartnerUserID", array($sPARAMS) );
        $doc = simplexml_load_string( $result->AssignPartnerUserIDResult );


		if ( $doc->StatusCode != 0 ) {
			//error message returned - perhaps invalid shadow key supplied
			return array(
				'StatusCode'    => $doc->StatusCode,
				'StatusInfo'    => strval(trim($doc->StatusInfo)),
			);
		} else	{

			//$names = array();
			foreach ($doc->DoppelMeNames->children() as $item) {
				$res = $item->attributes();
                $name = $res['name'];
				$names[] = array('key' => (string)$item , 'name' => $name);
			}

			return array(
            'DoppelMeKey'      => trim(strval($doc->DoppelMeKey)),
			'DoppelMeNames'    => $names,
			'ValidationKey'    => trim(strval($doc->ValidationKey)),
			'StatusCode'    	=> $doc->StatusCode,
			'StatusInfo'    	=> strval(trim($doc->StatusInfo)),
			);
		}
	}




	// Creates a new DoppelMe account for this USER_ID and returns an avatar code (of form DM123456ABC)
	// for their first avatar
	function create_user_account($USER_ID, $AVATAR_NAME) {

		$sPARAMS  = array(
            "partner_id" 	    => $this->getPartnerId(),
            "partner_key" 	    => $this->getPartnerKey(),
			"doppelme_name"     => urlencode($AVATAR_NAME),
			"partner_user_id"   => $USER_ID ,
			"referral_id" 	    => "0",
			"callback_url" 	    => "not needed",
			"is_test" 	        => false
			);
        $result =  $this->_api("CreateDoppelMeAccount", array($sPARAMS) );
        $doc = simplexml_load_string( $result->CreateDoppelMeAccountResult );

		$sDOPPELME_KEY	    = $doc->DoppelMeKey;
		$sVALIDATION_KEY    = $doc->ValidationKey;
		$sSTATUS		    = $doc->StatusInfo;
		$DOPPELME_NAMES     = $doc->DoppelMeNames;

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

    // Returns some details about a users account. The user_id passed in should be the
    // user_id that has been assigned to the user by the partner (i.e. it is a partner userid)
    function get_user_details($user_id) {
        $sPARAMS  = array(
            "partner_id" 	    => $this->getPartnerId(),
            "partner_key" 	    => $this->getPartnerKey(),
            "partner_user_id"   => $user_id,
            "is_test"		    => false		);
        $result =  $this->_api("GetDetailsFromPartnerUserID", array($sPARAMS) );
        $doc = simplexml_load_string( $result->GetDetailsFromPartnerUserIDResult );
        return array(
            'StatusCode'    => $doc->StatusCode,
            'StatusInfo'    => strval($doc->StatusInfo),
            'ValidationKey' => strval($doc->ValidationKey),
            'DoppelMeCoins' => intval($doc->DoppelMeCoins),
            'DoppelMeKey'   => strval($doc->DoppelMeKey),
            'Registered'    => $doc->Registered,
            'AllowPrank'    => $doc->AllowPrank,
            );
    }

	// Helper function to return a validation key used for handshake process
	// used between the editor and the avatar API
	function get_user_validation_key($user_id) {
        $details =  $this->get_user_details($user_id);
        return $details['ValidationKey'];
	}

	// Helper function to check access permissions
	function check_has_permissions() {
        $details =  $this->get_user_details('1');
		return 0 != strcmp(strtoupper($details['StatusInfo']), '"ACCESS DENIED"');
	}


    // Purchases item for user (but does not assign to avatar)
    function purchase_item($user_id, $item_id) {
        $sPARAMS  = array(
            "partner_id" 	    => $this->getPartnerId(),
            "partner_key" 	    => $this->getPartnerKey(),
            "partner_user_id"   => $user_id,
            "item_id"           => $item_id,
            "is_test"		    => false		);
        $result =  $this->_api("PurchaseItem", array($sPARAMS) );
        $doc = simplexml_load_string( $result->PurchaseItemResult );
        return array(
            'StatusCode'    => $doc->StatusCode,
            'StatusInfo'    => strval(trim($doc->StatusInfo)),
        );
    }


    // Assigns item to user's avatar (purchasing if necessary)
    function assign_item($user_id, $doppelme_key, $item_id, $colour, $purchase) {
        $sPARAMS  = array(
            "partner_id" 	    => $this->getPartnerId(),
            "partner_key" 	    => $this->getPartnerKey(),
            "partner_user_id"   => $user_id,
            "doppelme_key"      => $doppelme_key,
            "item_id"           => $item_id,
            "colour"            => $colour,
            "purchase"          => $purchase,
            "is_test"		    => false		);
        $result =  $this->_api("AssignItem", array($sPARAMS) );
        $doc = simplexml_load_string( $result->AssignItemResult );
        return array(
            'StatusCode'    => $doc->StatusCode,
            'StatusInfo'    => strval(trim($doc->StatusInfo)),
        );
    }

    // Assigns coins to user's account (amount of coinds will be debited from partner account)
    function assign_coins($user_id, $num_coins) {
        $sPARAMS  = array(
            "partner_id" 	    => $this->getPartnerId(),
            "partner_key" 	    => $this->getPartnerKey(),
            "partner_user_id"   => $user_id,
            "num_coins"         => $num_coins,
            "is_test"		    => false		);
        $result =  $this->_api("AssignCoins", array($sPARAMS) );

        $doc = simplexml_load_string( $result->AssignCoinsResult );
        return array(
            'StatusCode'    => $doc->StatusCode,
            'StatusInfo'    => strval(trim($doc->StatusInfo)),
        );
    }


    // Sets doppelme avatar gender. $gender should be M or F
    function set_doppelme_gender($user_id, $doppelme_key, $gender) {
        $sPARAMS  = array(
            "partner_id" 	    => $this->getPartnerId(),
            "partner_key" 	    => $this->getPartnerKey(),
            "partner_user_id"   => $user_id,
            "doppelme_key"      => $doppelme_key,
            "gender"            => $gender,
            "is_test"		    => false		);
        $result =  $this->_api("SetDoppelMeGender", array($sPARAMS) );

        $doc = simplexml_load_string( $result->SetDoppelMeGenderResult );
        return array(
            'StatusCode'    => $doc->StatusCode,
            'StatusInfo'    => strval(trim($doc->StatusInfo)),
        );
    }

    // Sets doppelme avatar hair colour. Colour should be 6 character hex RGB representation (e.g. FFFFFF for white)
    function set_doppelme_hair_colour($user_id, $doppelme_key, $colour) {
        $sPARAMS  = array(
            "partner_id" 	    => $this->getPartnerId(),
            "partner_key" 	    => $this->getPartnerKey(),
            "partner_user_id"   => $user_id,
            "doppelme_key"      => $doppelme_key,
            "colour"            => $colour,
            "is_test"		    => false		);
        $result =  $this->_api("SetDoppelMeHairColour", array($sPARAMS) );

        $doc = simplexml_load_string( $result->SetDoppelMeHairColourResult );
        return array(
            'StatusCode'    => $doc->StatusCode,
            'StatusInfo'    => strval(trim($doc->StatusInfo)),
        );
    }
    // Sets doppelme avatar eye colour. Colour should be 6 character hex RGB representation (e.g. FFFFFF for white)
    function set_doppelme_eye_colour($user_id, $doppelme_key, $colour) {
        $sPARAMS  = array(
            "partner_id" 	    => $this->getPartnerId(),
            "partner_key" 	    => $this->getPartnerKey(),
            "partner_user_id"   => $user_id,
            "doppelme_key"      => $doppelme_key,
            "colour"            => $colour,
            "is_test"		    => false		);
        $result =  $this->_api("SetDoppelMeEyeColour", array($sPARAMS) );

        $doc = simplexml_load_string( $result->SetDoppelMeEyeColourResult );
        return array(
            'StatusCode'    => $doc->StatusCode,
            'StatusInfo'    => strval(trim($doc->StatusInfo)),
        );
    }
    // Sets doppelme avatar skin colour. Colour should be 6 character hex RGB representation (e.g. FFFFFF for white)
    function set_doppelme_skin_colour($user_id, $doppelme_key, $colour) {
        $sPARAMS  = array(
            "partner_id" 	    => $this->getPartnerId(),
            "partner_key" 	    => $this->getPartnerKey(),
            "partner_user_id"   => $user_id,
            "doppelme_key"      => $doppelme_key,
            "colour"            => $colour,
            "is_test"		    => false		);
        $result =  $this->_api("SetDoppelMeSkinColour", array($sPARAMS) );

        $doc = simplexml_load_string( $result->SetDoppelMeSkinColourResult );
        return array(
            'StatusCode'    => $doc->StatusCode,
            'StatusInfo'    => strval(trim($doc->StatusInfo)),
        );
    }

	// Resets a users avatar. If DefaultClothing has been set, then the avatar
	// will be dressed in them, otherwise will be left naked.
	// Requires DoppelMe Service 1.3.0 or higher
	function reset_avatar($USER_ID) {
		$sPARAMS  = array(
            "partner_id" 	    => $this->getPartnerId(),
            "partner_key" 	    => $this->getPartnerKey(),
			"partner_user_id"   => $USER_ID,
			"is_test"		    => false		);

		$result =  $this->_api("ResetDoppelMe", array($sPARAMS) );
        $doc = simplexml_load_string( $result->ResetDoppelMeResult );

		$sSTATUS_CODE    = $doc->StatusCode;
		$sSTATUS_INFO    = $doc->StatusInfo;

		return array(
            'StatusCode'    => $doc->StatusCode,
            'StatusInfo'    => strval(trim($doc->StatusInfo)),
        );
	}

    /**
     * Grab the parameters of the user's Doppelme avatar
     *
     * Missing from 1.5.1 SDK.
     */
    function get_avatar_parameters($user_id, $doppelme_key) {
        $sPARAMS  = array(
            "partner_id" 	    => $this->getPartnerId(),
            "partner_key" 	    => $this->getPartnerKey(),
            "partner_user_id"   => $user_id,
            "doppelme_key"      => $doppelme_key,
            "is_test"		    => false		);
        $result =  $this->_api("GetAvatarParameters", array($sPARAMS) );

        $doc = simplexml_load_string( $result->GetAvatarParametersResult );
        return array(
            'StatusCode'    => $doc->StatusCode,
            'StatusInfo'    => strval(trim($doc->StatusInfo)),
        );
    }
}
?>