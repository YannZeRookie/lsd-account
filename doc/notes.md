Notes
=====

avatar:"15e8639f1fe7edf3199aafe955061452"
discriminator:"9646"
id:"381178649480658948"
username:"[LSD] YannZeRookie"

avatars/{user_id}/{user_avatar}.png
https://cdn.discordapp.com/avatars/381178649480658948/15e8639f1fe7edf3199aafe955061452.png

Totor0: 233116506332856320
#4664
https://cdn.discordapp.com/avatars/233116506332856320/37d7f00b580a6f4fd09641f2d06f4027.png

URL avatar = https://cdn.discordapp.com/avatars/<user_id>/<avatar_id>.png
Voir https://discordapp.com/developers/docs/reference#image-formatting

Faire des requêtes à l'API à la main
====================================

```
http https://discordapp.com/api/users/233116506332856320 'Authorization: Bot <bot_token>'
```

{
    "avatar": "37d7f00b580a6f4fd09641f2d06f4027",
    "discriminator": "4664",
    "id": "233116506332856320",
    "username": "TotorO"
}

To-do
=====

* Sign-up flow
* Synch lsd-account -> Discord when you change a role
* Officers should have a list of submissions to validate
* Make site pretty. We could use the "dark" look-and feel of Bootstrap? Of use our own CSS classes?
* UI to pay (adherer)
* UI to allow the Conseiller to check the adherants and payments
* Move to https : https://certbot.eff.org/
* Totor0: When you create or edit a section, it would be great to have a quick look-up UI to add Officers. And remove them too.


PayPal links
============
* Docs: https://developer.paypal.com/docs/
* IPN: https://developer.paypal.com/docs/classic/products/instant-payment-notification/
* https://stackoverflow.com/questions/39441562/paypal-button-return-url-usage
* https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&no_shipping=1&item_name=Citation Building&amount=110.50&currency_code=USD&return=<returnURL>&cancel=<cancelURL>&business=<seller.paypal@email.com>
* Pour un parametre custom: send: mettre dans le champ `custom` et on le récupère dans `cm`
    * Voir dans https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNandPDTVariables/#transaction-and-notification-related-variables
    * et https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNandPDTVariables/#pdt-specific-variables
* LES VARIABLES, enfin ! https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/#technical-variables
* Sandbox : https://developer.paypal.com/docs/classic/lifecycle/ug_sandbox/
* Developer SandBox accounts: https://developer.paypal.com/developer/accounts/
* 


```
<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="ZRSVFM8A5MXS8">
<input type="image" src="https://www.sandbox.paypal.com/fr_FR/FR/i/btn/btn_paynow_LG.gif" border="0" name="submit" alt="PayPal, le réflexe sécurité pour payer en ligne">
<img alt="" border="0" src="https://www.sandbox.paypal.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
</form>
```

