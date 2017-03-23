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
use Symfony\Component\Console\Input\InputArgument;

/**
 * Commande de synchronisation de site
 * Appelle à la suite les différentes commandes nécéssaires à la synchronisation
 * de deux sites depuis un environnement source vers un environnement cible.
 *
 * Les environnements et les aliases doivent au préalable avoir été décrits dans des
 * fichiers sous DRUPAL_ROOT\sites\all\drush
 *
 * @see https://github.com/drush-ops/drush/blob/master/examples/example.aliases.drushrc.php
 *
 * @package actoulouse\dsi\usineasite\Command
 */
class syncSiteCommand extends Command
{
    protected function configure()
    {
        $this->setName('sync-site')
            ->setDescription('Synchroniser un site entre deux environnements.')
            ->setHelp("Cette commande vous permet de synchroniser un site entre deux environnements. Un échange de clés SSH doit avoir eu lieu entre la source et la destination au préalable.")
            ->addArgument('sourceenv', InputArgument::REQUIRED, 'Environnement source (dev,preprod,prod)')
            ->addArgument('targetenv', InputArgument::REQUIRED, 'Environnement destination (dev,preprod,prod)')
            ->addArgument('sitename', InputArgument::REQUIRED, 'Nom du site à synchroniser')
            ->addArgument('localDrupalFolder', InputArgument::OPTIONAL, 'Répertoire local drupal - Defaut : /var/www/usine');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rootDrupalDir = (null != $input->getArgument('localDrupalFolder')) ? $input->getArgument('localDrupalFolder') : '/var/www/usine';

        /*
         * Verification de la cible : si c'est la prod
         * demander à l'utilisateur sa confirmation
         * @TODO: Vérouiller après mise en prod le déploiement vers la prod
         */
        if ($input->getArgument('targetenv') == 'prod') {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('<question>Vous etes sur le point d\'écraser des données de production. Continuer la création ? (y/n)</question>', false);

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Tout ça pour ça !</comment>');
                exit();
            }
        }

        // Drush sql-sync command from source to target
        // @see : https://drushcommands.com/drush-8x/sql/sql-sync/
        passthru(sprintf('cd %s && drush sql-sync @%s.%s @%s.%s',
            escapeshellarg($rootDrupalDir),
            escapeshellarg($input->getArgument('sitename')),
            escapeshellarg($input->getArgument('sourceenv')),
            escapeshellarg($input->getArgument('sitename')),
            escapeshellarg($input->getArgument('targetenv'))));

        // Drush rsync command from source to target
        // @see : https://drushcommands.com/drush-8x/core/core-rsync/
        // @see : https://doc.ubuntu-fr.org/rsync
        passthru(sprintf('cd %s && drush rsync @%s.%s:%%files @%s.%s:%%files',
            escapeshellarg($rootDrupalDir),
            escapeshellarg($input->getArgument('sitename')),
            escapeshellarg($input->getArgument('sourceenv')),
            escapeshellarg($input->getArgument('sitename')),
            escapeshellarg($input->getArgument('targetenv'))));
    }
}