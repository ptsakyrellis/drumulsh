<?php

namespace drumulsh\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class generationHtaccessCommand extends Command
{
    private $rootDrupalDir = '/var/www/usine';
    private $domain = 'usineasite.local';
    private $https = false;

    protected function configure()
    {
        $this->setName('settings:htaccess')
             ->setDescription('Génère le fichier .htaccess')
             ->setHelp("Cette commande permet de générer un fichier .htaccess permettant le routing des différents sites académiques.")
             ->addArgument('rootDrupalDir', InputArgument::REQUIRED, 'Dossier racine drupal (défaut : /var/www/usine)')
             ->addArgument('domain', InputArgument::REQUIRED, 'Domaine de l\'usine a sites (défaut : usineasite.local)')
             ->addArgument('https', InputArgument::OPTIONAL, 'Forcer le https (y/n) (défaut : n');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->rootDrupalDir = $input->getArgument('rootDrupalDir');
        $this->domain = $input->getArgument('domain');
        $this->https = (null != $input->getArgument('https') && $input->getArgument('https') == 'y') ? true : false;

        $rewriteRules = " ";
        $sites = array_filter(scandir($this->rootDrupalDir  . '/sites'), function($file) { return is_dir($this->rootDrupalDir . '/sites/'.$file); });

        usort($sites, array("drumulsh\command\generationHtaccessCommand", "sortSitesList"));

        foreach($sites as $site) {
            if($site != '.' && $site != '..' && $site != 'default' && $site != 'educ_master' && $site != 'all')
                $rewriteRules.= "   RewriteRule ^$site(.*) $1 [L,QSA]".PHP_EOL;
        }

        if($this->https) {
            $rewriteDomainHTTPSLB = PHP_EOL.'RewriteCond %{HTTP_HOST} !^'.str_replace('.', '\.',$this->domain).'$'.PHP_EOL
                                    .'RewriteRule (.*) https://'.str_replace('.', '\.',$this->domain).'/$1 [L,R=301]'.PHP_EOL
                                    .'RewriteCond %{HTTP:X-Forwarded-Proto} !https'.PHP_EOL.'RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]'.PHP_EOL;
        }

        $htaccessModel = file_get_contents(dirname(__FILE__).'/../../../app/config/.htaccess');
        $htaccess = str_replace('### BEGIN REWRITE SITES ###', '### BEGIN REWRITE SITES ###'.PHP_EOL.$rewriteRules, $htaccessModel);

        if(isset($rewriteDomainHTTPSLB)) {
            $htaccess = str_replace('### BEGIN REWRITE HTTPS DOMAIN ###', '### BEGIN REWRITE HTTPS DOMAIN ###'.PHP_EOL.$rewriteDomainHTTPSLB, $htaccess);
        }

        $res = file_put_contents($this->rootDrupalDir . '/.htaccess' , $htaccess, LOCK_EX);

        if($res === false) {
            $output->writeln('<info>Erreur lors de la création du fichier htaccess.</info>');
            throw new \Exception();
        }

        $output->writeln('<info>Fichier .htaccess généré avec succès.</info>');
    }

    /**
     * La liste des sites copiée dans le .htaccess pour faire le rewriting
     * peut entraîner des erreurs si deux sites contiennent une chaine commune matchant le même pattern
     * Ex :
     *
     * RewriteRule ^lettres(.*) $1 [L,QSA]
     * RewriteRule ^lettres-histoire-geographie(.*) $1 [L,QSA]
     *
     * Dans le cas ci-dessus les urls des deux sites vont passer uniquement dans la première règle de rewriting.
     */
     private static function sortSitesList($a, $b) {
        $pos = strpos($a, $b);

        if ($pos === false) {
            return strcmp($a, $b);
        } else {
            if($pos === 0) {
                if(strlen($a) > strlen($b)) {
                    return -1;
                } else if (strlen($a) === strlen($b)) {
                    return 0;
                } else {
                    return 1;
                }
            }
        }

        return strcmp($a, $b);
    }
}