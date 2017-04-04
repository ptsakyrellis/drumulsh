<?php

// src/actoulouse/dsi/usineasite/generationSiteCommand.php
namespace drumulsh\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class createDbAndUserCommand extends Command
{
    private $masterBDDIP = '127.0.0.1';
    private $slaveBDDIP = '127.0.0.1';
    private $bddLogin = 'root';
    private $bddPass = 'sa';

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('bdd:createdb')
            // the short description shown while running "php bin/console list"
            ->setDescription('Créer la nouvelle base de donnée pour un site donné et créer l\'utilisateur mysql associé.')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("Cette commande permet de générer un fichier de dump contenant les données de la base à un instant T. Le binaire mysqldump doit être accessible par l'utilisateur 
                       qui exécute le script.")
            ->addArgument('masterBDDIP', InputArgument::REQUIRED, 'Hôte du serveur BDD maître.')
            ->addArgument('user', InputArgument::REQUIRED, 'Utilisateur BDD maître.')
            ->addArgument('pass', InputArgument::REQUIRED, 'Password BDD maître.')
            ->addArgument('siteLogin', InputArgument::REQUIRED, 'Login nouvel utilisateur.')
            ->addArgument('sitePass', InputArgument::REQUIRED, 'Password nouvel utilisateur.')
            ->addArgument('dbname', InputArgument::REQUIRED, 'Nom de la base à créer.')
            ->addArgument('dumpfile', InputArgument::REQUIRED, 'Fichier de données à insérer dans la nouvelle base.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Création de la nouvelle base de données '.$input->getArgument('dbname').'</info>');

        $process = new Process(sprintf('mysql -h %s -u %s -p%s -e "CREATE DATABASE %s;"', escapeshellarg($input->getArgument('masterBDDIP')),
            escapeshellarg($input->getArgument('user')),
            escapeshellarg($input->getArgument('pass')),
            $input->getArgument('dbname')));

        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        echo $process->getOutput();

        $output->writeln('<info>Création de l\'utilisateur mysql : '.$input->getArgument('siteLogin').'</info>');

        $process = new Process(sprintf('mysql -h %s -u %s -p%s -e "GRANT ALL ON %s.* TO \'%s\'@%s IDENTIFIED BY \'%s\'; GRANT CREATE USER ON *.* TO \'%s\'@%s;"', escapeshellarg($input->getArgument('masterBDDIP')),
            escapeshellarg($input->getArgument('user')),
            escapeshellarg($input->getArgument('pass')),
            $input->getArgument('dbname'),
            $input->getArgument('siteLogin'), escapeshellarg($input->getArgument('masterBDDIP')), $input->getArgument('sitePass'), $input->getArgument('siteLogin'), escapeshellarg($input->getArgument('masterBDDIP'))));

        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        echo $process->getOutput();

        $output->writeln('<info>Importation des données de référence </info>');

        $process = new Process(sprintf('mysql -h %s -u %s -p%s %s < %s', escapeshellarg($input->getArgument('masterBDDIP')),
            escapeshellarg($input->getArgument('user')),
            escapeshellarg($input->getArgument('pass')),
            $input->getArgument('dbname'), escapeshellarg(dirname(__FILE__).'/../../../var/dump/'.$input->getArgument('dumpfile'))));

        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        echo $process->getOutput();
    }
}
