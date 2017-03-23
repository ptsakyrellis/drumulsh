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
/**
 * Commande de suppression d'un site
 */
class suppressionSiteCommand extends Command
{
    private $rootDrupalDir = '/var/www/usine';
    private $sitename = null;
    private $domaine = 'usineasite.local';

    protected function configure()
    {
        $this->setName('delete-site')
            ->setDescription('Supprime un site drupal.')
            ->setHelp("Cette commande permet de supprimer un site drupal. Attention : Cette manipulation n'est pas réversible. La suppression de la base de donnée associée doit être réalisée manuellement.")
            ->addArgument('sitename', InputArgument::REQUIRED, 'Nom du site drupal à supprimer.')
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

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>Vous aller supprimer le site '.$this->sitename.' et supprimer le dossier '
                        .$this->rootDrupalDir.'/sites/'.$this->sitename.PHP_EOL.'Continuer la suppression ? Cette action n\'est pas réversible. (y/n)</question>', false);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Fin du script sans suppression de site.</info>');
            return;
        }

        if(is_dir($this->rootDrupalDir.'/sites/'.$this->sitename)) {
            $process = new Process(sprintf('cd %s && rm -rf %s', $this->rootDrupalDir.'/sites', $this->sitename));
            $process->run();

            // executes after the command finishes
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        } else {
            $output->writeln('<info>Le dossier du site avait déjà été supprimé. Reprise de la suppression...</info>');
        }


        $command = $this->getApplication()->find('settings:sites');
        $arguments = array(
            'command'        => 'settings:settings',
            'rootDrupalDir'  => $this->rootDrupalDir,
            'domaine'        => $this->domaine
        );
        $greetInput = new ArrayInput($arguments);

        try {
            $returnCode = $command->run($greetInput, $output);

            if($returnCode > 0) {
                $output->writeln('<info>Erreur lors de la création du fichier sites.php</info>');
                throw new \Exception();
            }
        } catch(ProcessFailedException $pfe) {
            $output->writeln('<info>Erreur lors de la création du fichier sites.php</info>');
            $output->writeln('<error>---------------------------------------------------------------------------------------</error>');
            $output->writeln('<error>'.$pfe->getProcess()->getErrorOutput().'</error>');
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
            'domain'          => $this->domaine
        );
        $greetInput = new ArrayInput($arguments);

        try {
            $returnCode = $command->run($greetInput, $output);

            if($returnCode > 0) {
                $output->writeln('<info>Erreur lors de la création du fichier .htaccess</info>');
                $output->writeln('<info>Fin du script sans création de site.</info>');
                throw new \Exception();
            }
        } catch(ProcessFailedException $pfe) {
            $output->writeln('<info>Erreur lors de la création du fichier .htaccess</info>');
            $output->writeln('<error>---------------------------------------------------------------------------------------</error>');
            $output->writeln('<error>'.$pfe->getProcess()->getErrorOutput().'</error>');
            throw new \Exception();
        }

        $output->writeln('<info>Le site a bien été supprimé.</info>');
    }
}