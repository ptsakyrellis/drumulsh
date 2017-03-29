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

Les différents environnements sont à définir dans _drupalRootDir\sites\all\drush_. (Lire à ce sujet la documentation sur les aliases de sites drush : https://github.com/drush-ops/drush/blob/master/examples/example.aliases.drushrc.php )

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

### Licence

**Copyright (c) 2017 Ministère de l'Education Nationale** - Rectorat de Toulouse - DSI - Développements académiques - dsi-da_AT_ac-toulouse.fr

* texte complet de la licence CeCILL-B : http://www.cecill.info/licences/Licence_CeCILL-B_V1-fr.html
* contributeur :
Philippe Tsakyrellis philippe.tsakyrellis_AT_ac-toulouse.fr
* librairies utilisées : La liste des librairies utilisées par ce logiciel se trouve dans le fichier [composer.json](composer.json). Les librairies sont distribuées sous leur licences respectives. 

Ce logiciel est régi par la licence CeCILL-B soumise au droit français et
respectant les principes de diffusion des logiciels libres. Vous pouvez
utiliser, modifier et/ou redistribuer ce programme sous les conditions
de la licence CeCILL-B telle que diffusée par le CEA, le CNRS et l'INRIA 
sur le site http://www.cecill.info et dont la description peut être lue sur http://www.cecill.info/licences/Licence_CeCILL-B_V1-fr.html.

En contrepartie de l'accessibilité au code source et des droits de copie,
de modification et de redistribution accordés par cette licence, il n'est
offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
seule une responsabilité restreinte pèse sur l'auteur du programme,  le
titulaire des droits patrimoniaux et les concédants successifs.

A cet égard  l'attention de l'utilisateur est attirée sur les risques
associés au chargement,  à l'utilisation,  à la modification et/ou au
développement et à la reproduction du logiciel par l'utilisateur étant 
donné sa spécificité de logiciel libre, qui peut le rendre complexe à 
manipuler et qui le réserve donc à des développeurs et des professionnels
avertis possédant  des  connaissances  informatiques approfondies.  Les
utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
logiciel à leurs besoins dans des conditions permettant d'assurer la
sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
à l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 