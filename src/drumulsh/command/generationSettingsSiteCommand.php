<?php

namespace drumulsh\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class generationSettingsSiteCommand extends Command
{
    private $rootDrupalDir = '/var/www/usine';
    private $domaine = 'usineasite.local';

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('settings:sites')
            // the short description shown while running "php bin/console list"
            ->setDescription('Génère le fichier de configuration sites/sites.php')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("Cette commande permet de générer un fichier de settings pour un site donné.")
            ->addArgument('rootDrupalDir', InputArgument::OPTIONAL, 'Dossier racine drupal (défaut : /var/www/usine)')
            ->addArgument('domaine', InputArgument::OPTIONAL, 'Nom de domaine racine (defaut: usineasite.local');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!is_null($input->getArgument('rootDrupalDir'))) {
            $this->rootDrupalDir = $input->getArgument('rootDrupalDir');
        }
        if(!is_null($input->getArgument('domaine'))) {
            $this->domaine = $input->getArgument('domaine');
        }

        $sitesList = "<?php ".PHP_EOL;
        $sites = array_filter(scandir($this->rootDrupalDir . '/sites'), function($file) { return is_dir($this->rootDrupalDir . '/sites/'.$file); });

        foreach($sites as $site) {
            if($site != '.' && $site != '..' && $site != 'default' && $site != 'educ_master' && $site != 'all')
                $sitesList.= '$sites[\''.$this->domaine.'.'.$site.'\'] = \''.$site.'\';'.PHP_EOL;
        }

        $res = file_put_contents($this->rootDrupalDir . '/sites/sites.php', $sitesList);

        if($res === false) {
            $output->writeln('<info>Erreur lors de la création du fichier de configuration.</info>');
            $output->writeln('<info>Fin du script sans création de site .</info>');
            throw new \Exception();
        }

        $output->writeln('<info>Fichier sites.php généré avec succès.</info>');
    }
}