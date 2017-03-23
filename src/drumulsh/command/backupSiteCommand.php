<?php

namespace drumulsh\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class backupSiteCommand extends Command
{
    private $rootDrupalDir = '/var/www/usine';
    private $masterBDDIP = '127.0.0.1';
    private $slaveBDDIP = '127.0.0.1';
    private $bddLogin = 'root';
    private $bddPass = 'sa';
    private $filename = 'drupal_dump';
    private $sitesList = array();

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('backup-site')
            // the short description shown while running "php bin/console list"
            ->setDescription('Créer backup d\'un site.')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("Cette commande permet de backup un site. Le binaire mysqldump doit être accessible par l'utilisateur qui exécute le script.")
            ->addArgument('rootDrupalDir', InputArgument::REQUIRED, 'Racine drupal (défaut : /var/www/usine)')
            ->addArgument('sitename', InputArgument::OPTIONAL, 'Nom du fichier pour le dump.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!is_null($input->getArgument('rootDrupalDir')))
        {
            $this->rootDrupalDir = $input->getArgument('rootDrupalDir');
        }

        // a t on un nom de site ?
        if(!is_null($input->getArgument('sitename')))
        {
            $this->sitesList[] = $input->getArgument('sitename');
        } else {
            // sinon récupéréer tous les sites
            include_once($this->rootDrupalDir.'/sites/sites.php');
            $this->sitesList = $sites;
        }

        // on boucle sur la liste des sites
        foreach ($this->sitesList as $siteName){
            include_once($this->rootDrupalDir.'/sites/'.$siteName.'/settings.php');

            $output->writeln('<info>'.$siteName.' : démarrage de la sauvegarde.</info>');

            // passer le site en mode maintenance
            $command = sprintf('cd %s/sites/%s && drush vset maintenance_mode 1', $this->rootDrupalDir, $siteName);
            exec($command);

            // dumper la base de données + tar
            if(!is_dir(dirname(__FILE__).'/../../../var/backup/'.date('Y-m-d'))) {
                $output->writeln('<info>Creating backup dir for today</info>');
                passthru(sprintf('mkdir '.dirname(__FILE__).'/../../../var/backup/'.date('Y-m-d')));
            }

            passthru(sprintf('cd %s/sites/%s && drush sql-dump --gzip > '.dirname(__FILE__).'/../../../var/backup/%s/%s.tgz',$this->rootDrupalDir, $siteName, date('Y-m-d'), $siteName));

            /**
             * Backup des fichiers sur le disque
             */
            $output->writeln('<info>Copie des fichiers sur le disque.</info>');
            $process = new Process(sprintf('cd %s/sites/%s && tar -zcvf '.dirname(__FILE__).'/../../../var/backup/%s/%s.tgz .', $this->rootDrupalDir, $siteName, date('Y-m-d'),$siteName.'_files'));
            $process->run();

            // désactiver le site en mode maintenance
            $command = sprintf('cd %s/sites/%s && drush vset maintenance_mode 0', $this->rootDrupalDir, $siteName);
            exec($command);

            $output->writeln('<info>'.$siteName.' sauvegardé avec succès.</info>');
        }
    }
}