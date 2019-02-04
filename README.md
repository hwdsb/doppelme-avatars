# doppelme-avatars

[DoppelMe avatars](http://doppelme.com/) integration with BuddyPress.

This plugin completely replaces the upload avatar functionality within a BuddyPress community with the DoppelMe Avatar creator. 

You'll need to register your website via this link:<br>
https://partner.doppelme.com/register.php

Then whitelist the IP address your site is hosted on.

Once registered, get your partner ID and key and place it in your `wp-config.php` file:

```
define( 'BP_DOPPELME_PARTNER_ID',  'XXX' );
define( 'BP_DOPPELME_PARTNER_KEY', 'XXX' );
```

If you do not see the DoppelMe creator, ensure that PHP on your server is compiled with [SOAP](https://secure.php.net/manual/en/soap.installation.php).
