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
SELECT * FROM lsd_users WHERE discord_id=404722937183076354;
DELETE FROM lsd_users WHERE id=xxx;
DELETE FROM lsd_roles WHERE user_id=xxx;
```
* Créer une clé de connection bidon:
```
INSERT INTO lsd_login SET login_key='testkey', created_on=unix_timestamp(), discord_id='404722937183076354', discord_username='YannZeGrunt', discord_discriminator='5874';
INSERT INTO lsd_login SET login_key='testkey2', created_on=unix_timestamp(), discord_id='407273313484931073', discord_username='YannZeScorpion', discord_discriminator='1451';
```
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

Bons liens sur les packages composer
====================================
* Slim: https://packagist.org/packages/slim/slim
* Twig: https://packagist.org/packages/twig/twig
* slim/twig-view: https://packagist.org/packages/slim/twig-view
* slim/flash: https://packagist.org/packages/slim/flash


To-do
=====

* Synch lsd-account -> Discord when you change a role
X Officers should have a list of submissions to validate
X Make site pretty. We could use the "dark" look-and feel of Bootstrap? Of use our own CSS classes?
X UI to pay (adherer)
X UI to allow the Conseiller to check the adherants and payments
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
* LES VARIABLES HTML enfin ! https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/#technical-variables
* Sandbox : https://developer.paypal.com/docs/classic/lifecycle/ug_sandbox/
* Developer SandBox accounts: https://developer.paypal.com/developer/accounts/

Il faut se connecter avec le compte développeur Sandbox pour régler les boutons. Voir dans Passpack "PayPal Sandbox Développeur".

Pour payer, prendre le compte Sandbox "Paypal Sandbox Utilisateur" dans Passpack

Liste des boutons du compte : https://www.sandbox.paypal.com/fr/cgi-bin/webscr?cmd=_button-management
https://www.paypal.com/fr/cgi-bin/webscr?cmd=_button-management


Utilisation de la variable `rm` pour le mode de retour : https://www.paypal.com/mt/smarthelp/article/how-do-i-use-the-rm-variable-for-website-payments-ts1011

Idée : on peut coder les variables de retour dans la return_url


```
<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="ZRSVFM8A5MXS8">
<input type="image" src="https://www.sandbox.paypal.com/fr_FR/FR/i/btn/btn_paynow_LG.gif" border="0" name="submit" alt="PayPal, le réflexe sécurité pour payer en ligne">
<img alt="" border="0" src="https://www.sandbox.paypal.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
</form>
```

Variables de paiement en retour avec `rm=2`:
```
Array
(
    [payer_email] => yann.corno@free.fr
    [payer_id] => RXKVRQWU74WBC
    [payer_status] => UNVERIFIED
    [first_name] => Yann
    [last_name] => Corno
    [txn_id] => 8T242711RT7603255
    [mc_currency] => EUR
    [mc_gross] => 12.00
    [protection_eligibility] => INELIGIBLE
    [payment_gross] => 12.00
    [payment_status] => Pending
    [pending_reason] => unilateral
    [payment_type] => instant
    [handling_amount] => 0.00
    [shipping] => 0.00
    [item_name] => Adhésion standard
    [quantity] => 1
    [txn_type] => web_accept
    [option_name1] => Pseudo
    [option_selection1] => [LSD] YannZeRookie
    [option_name2] => UserID
    [option_selection2] => 1
    [payment_date] => 2019-11-02T13:04:56Z
    [notify_version] => UNVERSIONED
    [custom] => 3
    [verify_sign] => AZFQco7hPjTirJflnB.6NTHS3P4EAo.M2I3DW978KBQLvKU4SWENrOKg
)
```

J'ai ajouté une notify_url dans le code du bouton. C'est appelé en POST. -> abandonné, c'est mieux de le faire dans les paramètres IPN

ENFIN la doc qu'il me manquait ! https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/formbasics/?mark=notify_url%20post#instant-payment-notification--notify_url

Doc sur le protocole IPN (la call-back de validation de transaction) :
- https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNIntro/
- IPN Listener : https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNImplementation/

Gestion de l'IPN :
- https://www.sandbox.paypal.com/cgi-bin/customerprofileweb?cmd=_profile-ipn-notify
- https://www.paypal.com/cgi-bin/customerprofileweb?cmd=_profile-ipn-notify
- Notre url de notification: http://account.scorpions-du-desert.com/adherer/ipn


Historique des transactions IPN :
- https://www.sandbox.paypal.com/fr/cgi-bin/webscr?cmd=_display-ipns-history
- https://www.paypal.com/fr/cgi-bin/webscr?cmd=_display-ipns-history


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
- X Poster une notification privée dans Discord quand sa candidature est acceptée
- X Liste des cotisations PayPal
- X Cotisation à finir avec les vrais boutons de prod
- X N'importe quel Scorpion devrait pouvoir consulter la fiche d'un autre joueur (en mode readonly)
- X Paginer la liste des utilisateurs
- X Dans la liste, afficher les Pseudos VB et vérifier la recherche par pseudo VB
- X Bug: les Conseillers ne doivent pas voir le total des transactions
- X Page profil joueur : les roles devraient être des check boxes, pas des radio buttons !
- X Améliorer de la mise en page de la page profil: adhérant and CM à part
- X Le robot ne doit plus envoyer de message dans le canal courant
- X Mettre à jour le pseudo Discord à chaque connexion, et bien prendre le pseudo Discord lié au serveur (aka le Nickname) et pas celui du compte. Voir https://discordapp.com/developers/docs/resources/guild#guild-member-object et https://discordapp.com/developers/docs/resources/guild#get-guild-member
- X Respecter la même logique de contraintes concernant les Notes d'un utilisateur
- X Comme c'est le robot qui va mettre à jour les rôles, on va perdre le logging. Du coup il va falloir en refaire un.
- X Message d'accueil du robot à la première connexion
- Changer la page de cotisation sur accueil et forum pour rediriger vers le mini-site
- X Changer le message d'accueil dans le forum pour le processus d'inscription
- X Indiquer le total d'utilisateurs trouvés dans la liste des utilisateurs
- X basculer PayPal : activer l'IPN
- X basculer PayPal : retirer les pages VB, mettre à jour les liens pages d'accueil
- X Bug: AngeDechu (id=41) est officier d'une section archivée => encore considérée officier. Solution validée par le Conseil: ne pas importer les officiers des sections archivées.
- X Quand on archive une Section, il faut dégrader les officiers
- X Export tab file
- X Faire la page des membres pour le site d'accueil
- X Des Sections qui ont des Rôles associés : il faut mettre cela en database et pas hard-codé en UsersController.php:452
- X Après inscription et synchro VB, il n'y a pas de synchro vers Discord
- X Avoir un système de recrutement contrôllé pour les Sections qui ont une phase de recrutement propre à elles
- Virer tout le code de patch que j'avais fait dans VB pour l'inscription
- Permettre d'aller directemenent à une page après connexion (paramètre returnto à /login)
- Synchro mini-site -> VB à faire + authentification (?)
- Autres notes persos:
  - Jhynx n'a pas eu l'option de connexion à VB -> essayer de comprendre pourquoi
  - Sur un mobile, l'affichage des tableaux ne marche pas du tout.

2020-11-11: Passage à Slim 3 et Twig 3
======================================

Une tonne de changements mais je commence à mieux comprendre Composer !

La tentative de passer à Slim 4 fut un échec à cause de Twig. Je suis resté à Slim 3 en attendant mieux. C'est toujours ça de fait...

