<?php

namespace drumulsh\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Question\Question;
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
    private $analyticsDomain = 'piwik.local';
    private $adminToken = 'secretoken';
    private $proxyEnabled = false;
    private $proxyHost = null;
    private $proxyPort = '9090';

    protected function configure()
    {
        $this->setName('piwik:delete-site')
            ->setDescription('Supprimer un site de la plateforme Piwik')
            ->setHelp("Cette commande permet de supprimer les statistiques piwik pour le site  donné.")
            ->addArgument('sitename', InputArgument::REQUIRED, 'Nom du site ')
            ->addArgument('rootDrupalDir', InputArgument::OPTIONAL, 'Racine drupal (défaut : /var/www/usine)')
            ->addArgument('domaine', InputArgument::OPTIONAL, 'Nom de domaine racine (défaut: usineasite.local)')
            ->addArgument('domainePiwik', InputArgument::OPTIONAL, 'Nom de domaine piwik (défaut: piwik.local)');
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

        if(!is_null($input->getArgument('domainePiwik')))
        {
            $this->analyticsDomain = $input->getArgument('domainePiwik');
        }

        $this->sitename = $input->getArgument('sitename');

        $this->setVariables($input, $output);

        $output->writeln('<info>Démarrage du setup Piwik...</info>');

        // Setup classe piwik avec toutes ces infos
        $piwik = new \drumulsh\command\Piwik($this->domaine, $this->sitename, $this->analyticsDomain, $this->adminToken, $this->proxyEnabled, $this->proxyHost, $this->proxyPort);


        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>Vous allez supprimer le setup piwik pour '.$this->domaine.'/'.$this->sitename.'. Continuer ? (y/n)</question>', false);

        if (!$helper->ask($input, $output, $question)) {

            return;
        }

        $piwik->deleteUser();
        $piwik->deleteSite();

        $output->writeln('<info>Site supprimé avec succès</info>');
    }

    /**
     * Questionner l'utilisateur pour initialiser les valeurs des variables
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function setVariables(InputInterface $input, OutputInterface $output) {
        $output->writeln('<info>--------------------------------------</info>');
        $output->writeln('<info>Suppression d\'un site de la console piwik</info>');
        $output->writeln('<info>--------------------------------------</info>');

        $helper = $this->getHelper('question');
        $question = new Question('<question>Entrez l\' API token de l\'administrateur de votre plateforme piwik (défaut: secrettoken) : </question>', 'secrettoken');
        $this->adminToken = $helper->ask($input, $output, $question);

        $question = new Question('<question>Etes vous derriere un proxy (y/n) ?(défaut: n) : </question>', 'n');

        if($helper->ask($input, $output, $question) == 'y') {
            $this->proxyEnabled = true;
        } else {
            $this->proxyEnabled = false;
        }

        $question = new Question('<question>Host proxy ?(défaut: null) : </question>', 'null');
        $this->proxyHost = $helper->ask($input, $output, $question);

        $question = new Question('<question>Port proxy ?(défaut: 9090) : </question>', '9090');
        $this->proxyPort = $helper->ask($input, $output, $question);
    }
}

