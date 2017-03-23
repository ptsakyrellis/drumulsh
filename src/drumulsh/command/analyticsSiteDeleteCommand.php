<?php

namespace drumulsh\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\ArrayInput;
use actoulouse\dsi\usineasite\Piwik;
/**
 * Commande d'activation des statistiques
 */
class analyticsSiteDeleteCommand extends Command
{
    private $rootDrupalDir = '/var/www/usine';
    private $sitename = null;
    private $domaine = 'usineasite.local';

    protected function configure()
    {
        $this->setName('piwik:delete-site')
            ->setDescription('Supprimer un site de la plateforme Piwik')
            ->setHelp("Cette commande permet de supprimer les statistiques piwik pour le site  donné.")
            ->addArgument('sitename', InputArgument::REQUIRED, 'Nom du site ')
            ->addArgument('rootDrupalDir', InputArgument::OPTIONAL, 'Racine drupal (défaut : /var/www/usine)')
            ->addArgument('domaine', InputArgument::OPTIONAL, 'Nom de domaine racine (défaut: usineasite.local)');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!is_null($input->getArgument('rootDrupalDir')))
        {
            $this->rootDrupalDir = $input->getArgument('rootDrupalDir');
        }

        if(!is_null($input->getArgument('domaine')))
        {
            $this->domaine = $input->getArgument('domaine');
        }

        $this->sitename = $input->getArgument('sitename');

        $output->writeln('<info>Démarrage du setup Piwik...</info>');
        $piwik = new Piwik($this->domaine, $this->sitename);


        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>Vous allez supprimer le setup piwik pour '.$this->domaine.'/'.$this->sitename.'. Continuer ? (y/n)</question>', false);

        if (!$helper->ask($input, $output, $question)) {

            return;
        }

        $piwik->deleteUser();
        $piwik->deleteSite();

        $output->writeln('<info>Site supprimé avec succès</info>');
    }
}

