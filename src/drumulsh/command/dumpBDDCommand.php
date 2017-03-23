<?php

namespace drumulsh\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class dumpBDDCommand extends Command
{
    private $masterBDDIP = '127.0.0.1';
    private $slaveBDDIP = '127.0.0.1';
    private $bddLogin = 'root';
    private $bddPass = 'sa';
    private $filename = 'drupal_master_full_dump';

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('bdd:dump')
            // the short description shown while running "php bin/console list"
            ->setDescription('Créer un dump de la base de données.')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("Cette commande permet de générer un fichier de dump contenant les données de la base à un instant T. Le binaire mysqldump doit être accessbile par l'utilisateur qui exécute le script.")
            ->addArgument('masterBDDIP', InputArgument::REQUIRED, 'Hôte du serveur BDD maître.')
            ->addArgument('user', InputArgument::REQUIRED, 'Utilisateur BDD maître.')
            ->addArgument('pass', InputArgument::REQUIRED, 'Password BDD maître.')
            ->addArgument('dbname', InputArgument::REQUIRED, 'Nom de la base à dumper.')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Nom du fichier pour le dump.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!is_null($input->getArgument('filename')))
        {
            $this->filename =  dirname(__FILE__).'/../../../var/dump/'.$input->getArgument('filename');
        } else {
            $this->filename = dirname(__FILE__).'/../../../var/dump/'.time().'_'.$this->filename.'sql';
        }

        $process = new Process(sprintf('mysqldump -h %s -u %s -p%s %s > '. $this->filename, escapeshellarg($input->getArgument('masterBDDIP')),
            escapeshellarg($input->getArgument('user')),
            escapeshellarg($input->getArgument('pass')),
            escapeshellarg($input->getArgument('dbname'))));

        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output->writeln('<info>La base '.$input->getArgument('dbname').' a été dumpée dans le fichier '.$this->filename.'</info>');
    }
}