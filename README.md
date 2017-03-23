**Drupal Multisite Shell Application (drumulsh)**

## Objectifs

L'objectif de ce projet est de fournir une application console utilitaire pour administrer un setup Drupal multi-sites. 

## Fonctionnalités 

Les fonctionnalités de cette ligne de commande sont les suivantes : 

 * Créer un nouveau site
 * Générer le fichier contant la liste des sites
 * Générer le fichier .htaccess correspondant
 * Supprimer un site
 * Faire un dump de la base de données
 * Synchroniser un site entre deux environnements
 * Ajouter le site pour un suivi avec Piwik
 * Supprimer un site du suivi Piwik
 
 Les sites crées seront accessibles via des url de la form http://domaine.ext/nom-du-site ou https://domaine.ext/nom-du-site,
 et cela sans création de liens symboliques. 

## Pré-requis

### Pré-requis généraux

 * Vous devez avoir une première instance Drupal (branche Drupal 7.x) configurée avant de pouvoir utiliser cet outil.
 * PHP v. >= 5.6
 * Accès à la ligne de commande mysql
 * Composer installé
 * Avoir un accès à un super utilisateur de votre base de données
 * Drush installé
 * Apache avec mod_rewrite activé

### Pré-requis particuliers

 * Si vous voulez utiliser les tâches Piwik, vous devez au préalable avoir une instance de Piwik configurée et accessible.  

## Installation

Cloner le dépot à l'endroit désiré. Vous pouvez stocker cet utilitaire dans n'importe quel dosser, cela n'a pas d'importance.  

Rendez vous dans le dossier et lancez la commande suivante :

`composer install`

`composer dump-autoload -o`

## Configuration de l'environnement

Pour configurer votre environnement, deux possibilités s'offrent à vous : 
* entrer les paramètres en option de chaque commande
* créer un fichier de configuration dans lequel la console ira piocher les informations nécéssaires

## Utilisation de la console

### Créer un nouveau site

`drumulsh create-site`

### Supprimer un site existant

`drumulsh delete-site`

### Regénérer le fichier .htaccess

`drumulsh settings:htaccess`

### Regénérer le fichier sites.php

 `drumulsh settings:sites`
 
### Réaliser un dump de la base de données

`drumulsh settings:htaccess`

### Synchroniser un site entre deux environnements

`drumulsh settings:htaccess`

### Ajouter le suivi Piwik à un site

`drumulsh settings:htaccess`