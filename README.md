**Drupal Multisite Shell Application (drumulsh)**

## Objectifs

L'objectif de ce projet est de fournir une application console utilitaire pour administrer un setup Drupal 7 multi-sites. 

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
 * Backuper un ou l'ensemble des sites
 
 Les sites crées seront accessibles via des url de la form http://domaine.ext/nom-du-site ou https://domaine.ext/nom-du-site,
 et cela sans création de liens symboliques. 

## Pré-requis

### Pré-requis généraux

 * Vous devez avoir une première instance Drupal (branche Drupal 7.x) configurée avant de pouvoir utiliser cet outil.
 * PHP v. >= 5.6
 * Accès à la ligne de commande mysql
 * Composer installé (mode global)
 * Avoir un accès à un super utilisateur de votre base de données
 * Drush installé
 * Apache avec mod_rewrite activé

### Pré-requis particuliers

 * Si vous voulez utiliser les tâches Piwik, vous devez au préalable avoir une instance de Piwik configurée et accessible.  

## Installation

Cloner le dépot à l'endroit désiré. Vous pouvez stocker cet utilitaire dans n'importe quel dosser, cela n'a pas d'importance.

On évitera néanmoins de l'installer, pour des raisons de sécurité, sous la racine www du serveur web. 

Rendez vous dans le dossier cloné et lancez la commande suivante :

`composer install`

`composer dump-autoload -o`

Puis vous pouvez utiliser l'application avec :   

`./drumulsh`

Vous pouvez obtenir de l'aide sur les commandes en utilisant : 

`./drumulsh commande -h`

![Console Screenshot](doc/screenshot/mainconsole.jpg?raw=true "Screenshot console")

## Configuration de l'environnement

Pour configurer votre environnement, deux possibilités s'offrent à vous : 
* entrer les paramètres en option de chaque commande
* [TODO] créer un fichier de configuration dans lequel la console ira piocher les informations nécéssaires

## Utilisation de la console

### Créer un nouveau site

`drumulsh create-site`

![create-site Screenshot](doc/screenshot/create-site.jpg?raw=true "Screenshot create-site")

La commande effectue les taches suivantes : 
* transformation du nom du site en _slug_ valide
* backup de la base de données source
* création d'une nouvelle base de données et d'un utilisateur
* importation de la base source
* copie des fichiers uploadés du site initial
* création du fichier de configuration du nouveau site
* regénération du fichier sites.php
* regénération du fichier .htaccess

### Supprimer un site existant

`drumulsh delete-site <sitename> [rootDrupalDir] [domaine]`

La commande effectue les taches suivantes : 
* Suppression des fichiers du site 
* [TODO] drop de la base de données associée
* [TODO] drop du user mysql associé

### Regénérer le fichier .htaccess

`drumulsh settings:htaccess <rootDrupalDir> <domaine> [https]`

La commande effectue les taches suivantes : 
* Regénération du fichier .htaccess d'après le modèle 

### Regénérer le fichier sites.php

 `drumulsh settings:sites [rootDrupalDir] [domaine]`
 
 La commande effectue les taches suivantes : 
 * regénération du fichier sites.php à partir des dossier présents dans le sous-dossier sites
 
### Réaliser un dump de la base de données

`drumulsh bdd:dump <bddIP> <user> <pass> <dbname> [filename]`

### Synchroniser un site entre deux environnements

`drumulsh sync-site <sourceenv> <destenv> <sitename> [drupalRootDir]`

Les différents environnements sont à définir dans _drupalRootDir\sites\all\drush_. 

Par exemple, fichier **preprod.aliases.drushrc.php** : 
 
```
$aliases['docu.preprod'] = array(
  'root' => '/var/www',
  'uri' => 'portaildrupal.fr/docu',
  'remote-host' => 'portaildrupal.fr',
  'remote-user' => 'root'
);
  
$aliases['maths.preprod'] = array(
    'root' => '/var/www',
    'uri' => 'portaildrupal.fr/maths',
    'remote-host' => 'portaildrupal.fr',
    'remote-user' => 'root'
);
```

La commande effectue les taches suivantes : 
* backup + synchronisation base de données
* backup + synchronisation des fichiers

Remarque : on ne peut synchroniser entre deux environnements distants de la machine sur laquelle on lance cette commande (limitation rsync). Il faut qu'au moins un des deux environnements (source ou destination soit l'environnement local).

### Ajouter le suivi Piwik à un site

`drumulsh piwik:set-site <sitename> [rootDrupalDir] [domaine] [domainePiwik]`

La commande effectue les taches suivantes : 
* Création du site sur piwik si absent
* Création d'un user piwik n'ayant les droits que sur ce site
* Activation du module drupal piwik_reports
* Configuration du module drupal piwik_reports (api key, url)

### Supprimer le suivi Piwik d'un site

`drumulsh piwik:delete-site <sitename> [rootDrupalDir] [domaine] [domainePiwik]`

La commande effectue les taches suivantes : 
* Suppression du user piwik associé
* Suppression du site piwik associé

### Backuper un site

`drumulsh backup-site [rootDrupalDir] [sitename]`

La commande effectue les taches suivantes : 
* dump de la base de données du site + tar gz
* dump des fichiers du site + tar gz

### Backuper tous les sites

`drumulsh backup-site [rootDrupalDir]`

* backup de chaque base de données + tar gz
* backup de chaque répertoire de fichiers + tar gz

###License
Copyright (c) 2017 Philippe Tsakyrellis

Licensed under the MIT license
