<?php

namespace drumulsh\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Cocur\Slugify\Slugify;

/**
 * Commande principale de création de site
 * Appelle à la suite les différentes commandes nécéssaires
 *
 * @package actoulouse\dsi\usineasite\Command
 */
class generationSiteCommand extends Command
{
    /*
     * Variables par défaut
     */
    private $rootDrupalDir = '/var/www/usine';
    private $masterSiteDir = 'educ_master';
    private $directory = 'mathematiques';
    private $bddPrefix = 'uas_';
    private $bddName = 'educ_mathematiques';
    private $domaine = 'usineasite.local';
    private $url = 'http://';
    private $https = false;
    private $masterBDDIP = '127.0.0.1';
    private $slaveBDDIP = '127.0.0.1';
    private $bddLogin = 'root';
    private $bddPass = 'sa';
    private $siteLogin = 'site';
    private $sitePass = 'pass';

    protected function configure()
    {
        $this->setName('create-site')
             ->setDescription('Créer un nouveau site drupal.')
             ->setHelp("Cette commande vous permet de créer un nouveau site drupal. Vous devez posséder les identifiants de connexion à la base de données d'un super user.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setVariables($input, $output);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>Continuer la création ? (y/n)</question>', false);

        if (!$helper->ask($input, $output, $question)) {
            
            return;
        }

        /**
         * Création de la BDD par le biais de la commande bdd:dump
         */
        $filename = time().'_'.$this->masterSiteDir.'_master_full.sql';

        include_once($this->rootDrupalDir.'/sites/'.$this->masterSiteDir.'/settings.php');

        $command = $this->getApplication()->find('bdd:dump');
        $arguments = array(
            'command'        => 'bdd:dump',
            'masterBDDIP'    => $this->masterBDDIP,
            'user'           => $this->bddLogin,
            'pass'           => $this->bddPass,
            'dbname'         => $db_name, //coming from settings file
            'filename'       => $filename
        );
        $greetInput = new ArrayInput($arguments);

        try {
            $returnCode = $command->run($greetInput, $output);

            if($returnCode > 0) {
                $output->writeln('<comment>Erreur lors du dump de la base, merci de vérifier les informations ci-dessus.</comment>');
                $this->rollback(1, $output);
                throw new \Exception();
            }
        } catch(ProcessFailedException $pfe) {
            $output->writeln('<comment>Erreur lors du dump de la base, merci de vérifier les informations ci-dessous : </comment>');
            $output->writeln('<error>---------------------------------------------------------------------------------------</error>');
            $output->writeln('<error>'.$pfe->getProcess()->getErrorOutput().'</error>');
            
            $this->rollback(1, $output);
            throw new \Exception();
        }

        /**
         * Création de la nouvelle base et de l'utilisateur Mysql de connexion
         * via la commande bdd: create
         */
        $command = $this->getApplication()->find('bdd:createdb');

        $arguments = array(
            'command'        => 'bdd:createdb',
            'masterBDDIP'    => $this->masterBDDIP,
            'user'           => $this->bddLogin,
            'pass'           => $this->bddPass,
            'siteLogin'      => $this->siteLogin,
            'sitePass'       => $this->sitePass,
            'dbname'         => $this->bddName,
            'dumpfile'       => $filename
        );
        $greetInput = new ArrayInput($arguments);

        try {
            $returnCode = $command->run($greetInput, $output);

            if($returnCode > 0) {
                $output->writeln('<comment>Erreur lors de la création de la base, merci de vérifier les informations ci-dessus.</comment>');
                
                $this->rollback(2, $output);
                throw new \Exception();
            }
        } catch(ProcessFailedException $pfe) {
            $output->writeln('<comment>Erreur lors de la création de la base, merci de vérifier les informations ci-dessous : </comment>');
            $output->writeln('<error>---------------------------------------------------------------------------------------</error>');
            $output->writeln('<error>'.$pfe->getProcess()->getErrorOutput().'</error>');
            
            $this->rollback(2, $output);
            throw new \Exception();
        }

        /**
         * Copie des fichiers sur le disque
         */
        $output->writeln('<info>Copie des fichiers sur le disque.</info>');
        $process = new Process(sprintf('cp -Raf %s/sites/%s/. %s/sites/%s', $this->rootDrupalDir, $this->masterSiteDir, $this->rootDrupalDir, $this->directory));
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            $output->writeln('<comment>Erreur lors de la copie du dossier du site.</comment>');
            $this->rollback(3, $output);
            throw new \Exception();
        }

        /**
         * Création du fichier de configuration du nouveau site
         */
        $output->writeln('<info>Création du fichier de configuration du nouveau site.</info>');
        $settingsFileContent = <<<'EOT'
<?php
$db_name = '%s';
$db_master_host = '%s';
$db_master_user = '%s';
$db_master_pass = '%s';
$db_slave_host = '%s';
$db_slave_user = '%s';
$db_slave_pass = '%s';
include(dirname(__DIR__).'/main_settings.php');
EOT;

        $settingsFileContent = sprintf($settingsFileContent, $this->bddName, $this->masterBDDIP, $this->siteLogin, $this->sitePass, $this->masterBDDIP, $this->siteLogin, $this->sitePass);
        $res = file_put_contents($this->rootDrupalDir . '/sites/' .  $this->directory . '/settings.php', $settingsFileContent);

        if($res === false) {
            $output->writeln('<comment>Erreur lors de la création du fichier de configuration.</comment>');
            
            $this->rollback(4, $output);
            throw new \Exception();
        }

        /**
         * Copie des fichiers sur le disque
         */
        $process = new Process(sprintf('cd %s/sites/%s && drush vset file_public_path sites/%s/files', $this->rootDrupalDir, $this->directory, $this->directory));
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            $output->writeln('<comment>Erreur lors du vset drush</comment>');
            
            $this->rollback(5, $output);
            throw new \Exception();
        }

        echo $process->getOutput();

        /*
         * Actualisation du fichier sites.php
         *
         */
        $command = $this->getApplication()->find('settings:sites');
        $arguments = array(
            'command'        => 'settings:sites',
            'rootDrupalDir'    => $this->rootDrupalDir,
            'domaine'     => $this->domaine
        );
        $greetInput = new ArrayInput($arguments);

        try {
            $returnCode = $command->run($greetInput, $output);

            if($returnCode > 0) {
                $output->writeln('<comment>Erreur lors de la création du fichier sites.php</comment>');
                
                $this->rollback(6, $output);
                throw new \Exception();
            }
        } catch(ProcessFailedException $pfe) {
            $output->writeln('<comment>Erreur lors de la création du fichier sites.php</comment>');
            $output->writeln('<error>---------------------------------------------------------------------------------------</error>');
            $output->writeln('<error>'.$pfe->getProcess()->getErrorOutput().'</error>');
            
            $this->rollback(6, $output);
            throw new \Exception();
        }

        /*
         * Actualisation du fichier .htaccess
         *
         */
        $command = $this->getApplication()->find('settings:htaccess');
        $arguments = array(
            'command'        => 'settings:htaccess',
            'rootDrupalDir'    => $this->rootDrupalDir,
            'domain'           => $this->domaine,
            'https'            => $this->https ? 'y':'n'
        );
        $greetInput = new ArrayInput($arguments);

        try {
            $returnCode = $command->run($greetInput, $output);

            if($returnCode > 0) {
                $output->writeln('<comment>Erreur lors de la création du fichier .htaccess</comment>');
                
                $this->rollback(7, $output);
                throw new \Exception();
            }
        } catch(ProcessFailedException $pfe) {
            $output->writeln('<comment>Erreur lors de la création du fichier .htaccess</comment>');
            $output->writeln('<error>---------------------------------------------------------------------------------------</error>');
            $output->writeln('<error>'.$pfe->getProcess()->getErrorOutput().'</error>');
            
            $this->rollback(7, $output);
            throw new \Exception();
        }

        $output->writeln('<info>Site crée avec succès.</info>');

        /**
         * Vidage du cache pour terminer
         */
        $process = new Process(sprintf('cd %s/sites/%s && drush cc all', $this->rootDrupalDir, $this->directory));
        $process->run();
    }


    /**
     * Questionner l'utilisateur pour initialiser les valeurs des variables
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function setVariables(InputInterface $input, OutputInterface $output) {
        $output->writeln('<info>--------------------------------------</info>');
        $output->writeln('<info>Création d\'un nouveau site drupal</info>');
        $output->writeln('<info>--------------------------------------</info>');
        $slugify = new Slugify();

        $helper = $this->getHelper('question');
        $question = new Question('<question>Entrez le répertoire racine de drupal (défaut: /var/www/usine) : </question>', '/var/www/usine');
        $this->rootDrupalDir = $helper->ask($input, $output, $question);
        $question = new Question('<question>Entrez le répertoire du nouveau site ou le nom de la matière (défaut: mathématiques) : </question>', 'mathematiques');
        $this->directory = $slugify->slugify($helper->ask($input, $output, $question));
        $this->bddName = $this->bddPrefix . str_replace('-','', substr($this->directory, 0, 12)).'_'.substr(uniqid('', true), -3);
        $question = new Question('<question>Entrez le nom de domaine (défaut: usineasite.local) : </question>', 'usineasite.local');
        $this->domaine = $helper->ask($input, $output, $question);
        $question = new Question('<question>Voulez vous forcer les url en https (y/n) ? (défaut: n) : </question>', 'false');
        $this->https = ($helper->ask($input, $output, $question) == 'y') ? true : false;
        $this->url = $this->https ? 'https://' . $this->domaine . '/' . $this->directory : 'http://' . $this->domaine . '/' . $this->directory;
        $question = new Question('<question>IP du serveur de base de données maître (défaut: 127.0.0.1) : </question>', '127.0.0.1');
        $this->masterBDDIP = $helper->ask($input, $output, $question);
        $question = new Question('<question>IP du serveur de base de données esclave (défaut: 127.0.0.1) : </question>', '127.0.0.1');
        $this->slaveBDDIP = $helper->ask($input, $output, $question);
        $question = new Question('<question>Utilisateur BDD (défaut: root) : </question>', 'root');
        $this->bddLogin = $helper->ask($input, $output, $question);
        $question = new Question('<question>Mot de passe utilisateur BDD (défaut: sa) : </question>', 'sa');
        $this->bddPass = $helper->ask($input, $output, $question);
        $question = new Question('<question>Entrez le répertoire du site template (défaut: educ_master) : </question>', 'educ_master');
        $this->masterSiteDir = $helper->ask($input, $output, $question);

        $this->siteLogin = 'u_'.substr($this->directory, 0,5).'_'.bin2hex(random_bytes(4));
        $this->sitePass = bin2hex(random_bytes(6));

        // Affichage des variables saisies par l'utilisateur
        $output->writeln('<comment>------------------------------</comment>');
        $output->writeln('<comment>Paramètres de création du site</comment>');
        $output->writeln('<comment>------------------------------</comment>');
        $output->writeln('<comment>Répertoire du nouveau site : '.$this->directory.'</comment>');
        $output->writeln('<comment>Future url du site : '.$this->url.'</comment>');
        $output->writeln('<comment>Master BDD IP : '.$this->masterBDDIP.'</comment>');
        $output->writeln('<comment>Slave BDD IP : '.$this->slaveBDDIP.'</comment>');
        $output->writeln('<comment>User bdd : '.$this->bddLogin.'</comment>');
        $output->writeln('<comment>Site à dupliquer : '.$this->masterSiteDir.'</comment>');
    }

    /**
     * Rollback de la création de site en cas d'erreur en cours de route
     *
     */
    private function rollback($step, $output){
        // exécution de la commande delete-site

        // erreur au dump
        if($step >= 1) {
            // rien a faire, car rien n'a fonctionné !
        }

        // Erreur a la création de la base, reste à supprimer le dump
        if($step >= 2) {
            // pour l'instant la manoeuvre est manuelle dans tous les cas
        }

        // Erreur à la copie des fichiers, ou a la génération du htaccess ou settings
        if($step >= 3) {
            $command = $this->getApplication()->find('delete-site');
            $arguments = array(
                'command'        => 'delete-site',
                'sitename'         => $this->directory,
                'rootDrupalDir'    => $this->rootDrupalDir,
                'domaine'          => $this->domaine
            );
            $greetInput = new ArrayInput($arguments);

            try {
                $returnCode = $command->run($greetInput, $output);

                if($returnCode > 0) {
                    $output->writeln('<comment>Erreur lors de la suppression des fichiers du site copiés.</comment>');
                    throw new \Exception();
                }
            } catch(ProcessFailedException $pfe) {
                $output->writeln('<comment>Erreur lors de la suppression des fichiers du site copiés.</comment>');
                throw new \Exception();
            }
        }

        $output->writeln('<info>Site rollbacké avec succès.</info>');
    }
}