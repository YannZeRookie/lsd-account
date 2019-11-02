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

Database
========

Rappel pour utiliser la base MySQL de développement sur mon Mac :
* Lancer la VM « LSD VB ». L’IP est 192.168.5.51 mappé sur vb.local
* Pour se connecter : `mysql -h 192.168.5.51 -u lsd_www -p lsd_www`
avec le mdp ‘toto'

Notes de Data Model
===================

* Role::kVisiteur, Role::kInvite, et Role::kScorpion sont exclusifs.
* Role::kMembre et Role::kOfficier sont exclusifs.

Pour simuler une première connection
====================================
Par exemple pour YannZeGrunt (discord_id=404722937183076354 #5874) :
* Commenter la ligne de `$connect_force_user` dans `config.php`
* Détruire le record de l'utilisateur s'il y en a déjà un :<br>
```
DELETE FROM lsd_users WHERE id=6;
DELETE FROM lsd_roles WHERE user_id=6;
```
* Créer une clé de connection bidon:<br>
`INSERT INTO lsd_login SET login_key='testkey', created_on=unix_timestamp(), discord_id='404722937183076354', discord_username='YannZeGrunt', discord_discriminator='5874'; `
* Se déconnecter du site: http://localhost:8080/logout
* Lancer le flow: http://localhost:8080/login/testkey

Faire des requêtes à l'API à la main
====================================

```
http https://discordapp.com/api/users/233116506332856320 'Authorization: Bot <bot_token>'
{
    "avatar": "37d7f00b580a6f4fd09641f2d06f4027",
    "discriminator": "4664",
    "id": "233116506332856320",
    "username": "TotorO"
}
```

    YannZeRookie: 381178649480658948
    YannZeGrunt: 404722937183076354
    YannZeScorpion: 407273313484931073

```
http https://discordapp.com/api/users/381178649480658948 'Authorization: Bot <bot_token>'
{
    "avatar": "15e8639f1fe7edf3199aafe955061452",
    "discriminator": "9646",
    "id": "381178649480658948",
    "username": "[LSD] YannZeRookie"
}

http https://discordapp.com/api/users/404722937183076354 'Authorization: Bot <bot_token>'
{
    "avatar": null,
    "discriminator": "5874",
    "id": "404722937183076354",
    "username": "YannZeGrunt"
}

-- Create a fake login key:
INSERT INTO lsd_login SET login_key='test', created_on=unix_timestamp(), discord_id='404722937183076354', discord_username='YannZeGrunt', discord_discriminator='5874', discord_avatar=null;

http://localhost:8080/login/test

```

To-do
=====

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

Il faut se connecter avec le compte développeur Sandbox pour régler les boutons. Voir dans Passpack "PayPal Sandbox Développeur".

Pour payer, prendre le compte Sandbox "Paypal Sandbox Utilisateur" dans Passpack

Liste des boutons du compte : https://www.sandbox.paypal.com/fr/cgi-bin/webscr?cmd=_button-management




```
<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="ZRSVFM8A5MXS8">
<input type="image" src="https://www.sandbox.paypal.com/fr_FR/FR/i/btn/btn_paynow_LG.gif" border="0" name="submit" alt="PayPal, le réflexe sécurité pour payer en ligne">
<img alt="" border="0" src="https://www.sandbox.paypal.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
</form>
```

Meeting du 2019-07-22
=====================

- X inscription : pouvoir préciser la Section qu'on veut rejoindre (ou aucune)
- X Ajouter les liens (ou le texte expandable ?) vers la Charte et les RIs
- X pouvoir entrer des aliases (multiples) pour chaque Section
- X pouvoir changer ses aliases (multiples) pour chaque Section (les officiers doivent pouvoir aussi)
- X ajouter une zome de commentaires destinée aux Officiers et Conseillers dans la fiche de chaque joueur
- X ajouter une page "ressources" pour les Officiers et + avec quelques liens utiles, comme par exemple vers les assets du Google Drive LSD
- X Permettre aux Scorpions de se relier à leur compte VB, que ce soit lors de la création du compte venant de Discord qu'après coup
- X Polishing "dark"
- X Up synchro vers Discord quand on change un rôle (débrayable)
- X Poster une notification dans Discord quand il y a une candidature
- Autres notes persos:
  - Bug: quand on passe un utilisateur en Visiteur, ça ne mets pas à jour Discord comme il faut. Ça ne semble marcher qu'avec le role Invité
  - N'importe qui devrait pouvoir consulter la fiche d'un autre joueur
  - Poster une notification privée dans Discord quand sa candidature est acceptée
  - Cotisation à finir avec les vrais boutons de prod + CRUD des cotisations
  - Paginer la liste des utilisateurs

