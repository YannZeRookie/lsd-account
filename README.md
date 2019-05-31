Useful URLs
===========

* Micro-framework PHP : [Slim v2](http://docs.slimframework.com/)
    * [Cookies cryptés de session](http://docs.slimframework.com/sessions/cookies/)
* Système de templates PHP : [slim/twig-view](https://packagist.org/packages/slim/twig-view#1.2.0) Voir aussi [la doc Twig](http://twig.sensiolabs.org/doc/templates.html)
* Database library pour PHP, s'inspirant de Active Records: [bephp/activerecord](https://github.com/bephp/activerecord) avec [la doc de référence](https://bephp.github.io/activerecord/)
* [Library of Composer Packages, with all versions](https://packagist.org/explore/)

Installation
============

Après avoir cloné la repo et être entré dans le directory `lsd-account` :

    $ composer install

pour installer les packages utilisés par l'app.

Renommer le fichier `config.sample.php` en `config.php` dans le dossier `config/`et remplir les valeurs, comme les paramètres d'accès à la base de données.

Base de données
===============

Il faut appliquer **dans l'ordre** les instructions SQL du dossier `db/` pour construire les tables dans la base que vous aurez créée pour développer.

Quand de nouveaux scripts SQL sont livrés, il faut les appliquer. Idéalement, tout script SQL doit être idempotent : on doit pouvoir le lancer plusieurs fois.

Lancement en local
==================

Merci au mode serveur Web intégré à PHP :

    $ ./server
    PHP 7.1.16 Development Server started at Fri Feb  1 16:40:58 2019
    Listening on http://localhost:8080
    Document root is /Users/yann/dev/lsd-account/public
    Press Ctrl-C to quit.

Il n'y a plus qu'à aller sur http://localhost:8080/ et voilà !

En production
=============
Il faudra là aussi configurer le fichier `config.php`.
Renommer le fichier `htaccess` en `.htaccess` (si config Apache).
Créer un dossier `cache` à la racine et donner au serveur web les droits d'écriture dessus. Par exemple si www-data est le groupe de la hiérarchie des dossiers:

```
mkdir cache
chmod g+w cache
chmod g+s cache
```
