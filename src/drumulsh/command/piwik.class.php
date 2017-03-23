<?php

namespace drumulsh\command;
/**
 * Cette classe gère la communication avec l'API piwik
 * @author sle-merre
 */

class Piwik {

    const URL_PIWIK = 'https://piwik.domain.fr/';
    const REQUEST_ADDSITE = 'index.php?module=API&token_auth=%s&method=SitesManager.addSite&siteName=%s&%s';
    const REQUEST_SITEDELETE = 'index.php?module=API&token_auth=%s&method=SitesManager.deleteSite&idSite=%s';
    const REQUEST_GETSITEID = 'index.php?module=API&method=SitesManager.getSitesIdFromSiteUrl&token_auth=%s&url=%s&format=JSON';
    const REQUEST_USEREXISTS = 'index.php?module=API&method=UsersManager.userExists&format=JSON&token_auth=%s&userLogin=%s';
    const REQUEST_USERCREATE = 'index.php?module=API&method=UsersManager.addUser&format=JSON&token_auth=%s&userLogin=%s&password=%s&email=%s';
    const REQUEST_USERDELETE = 'index.php?module=API&method=UsersManager.deleteUser&format=JSON&token_auth=%s&userLogin=%s';
    const REQUEST_USERGET = 'index.php?module=API&method=UsersManager.getUser&format=JSON&token_auth=%s&userLogin=%s';
    const REQUEST_USERACCESS = 'index.php?module=API&method=UsersManager.setUserAccess&format=JSON&token_auth=%s&userLogin=%s&access=%s&idSites[0]=%s';
    const REQUEST_USERTOKEN = 'index.php?module=API&method=UsersManager.getTokenAuth&format=JSON&token_auth=%s&userLogin=%s&md5Password=%s';
    const ADMIN_TOKEN = "ADMINSECRETOKEN";
    const ACCESS_VIEW = 'view';

    public static $proxyEnabled = false;
    public static $proxyHost = 'yourproxy.domain.fr';
    public static $proxyPort = '9090';
    public $name;
    public $username;
    public $urls;
    public $siteId;
    public $inserted = false;
    private $userExists;
    private $token;
    private $userToken = null;
    private $password = null;
    private $piwikUrl;

    /**
     * 
     * @param string $name Nom de l'établissement (format <type>-<nom>-<commune>)
     * @param array $urlss
     */
    public function __construct($domain, $directory, $piwikUrl = self::URL_PIWIK, $token = self::ADMIN_TOKEN, $proxyEnabled = false, $proxyHost = null, $proxyPort = null) {
        //echo "*************************************************************\n";
        //echo "* Piwik API \n";
        $this->name = '[UAS] - '.$directory;
        $this->username = 'uas_'.$directory;
        $this->token = $token;
        $this->piwikUrl = $piwikUrl;
        $this->urls = array(
            sprintf('http://%s/%s', $domain, $directory),
            sprintf('https://%s/%s', $domain, $directory),
        );
        self::$proxyEnabled = $proxyEnabled;
        self::$proxyHost = $proxyHost;
        self::$proxyPort = $proxyPort;
    }

    /**
     * Renvoi l'id du site de l'utilisateur
     * @return type
     */
    public function getSiteId() {
        try {
            if (isset($this->siteId))
                return $this->siteId;
            $url = sprintf(self::URL_PIWIK . self::REQUEST_GETSITEID, $this->token, urlencode($this->urls[0]));
            $result = json_decode($this->request($url));
            if ($result && count($result) === 1) {
                $this->inserted = true;
                $this->siteId = $result[0]->idsite;
                return $result[0]->idsite;
            }
        } catch (Exception $exc) {
           throw($exc);
        }
    }

    /**
     * Renvoi l'objet utilisateur
     * @return mixed
     */
    function getUser() {
        if (isset($this->user))
            return $this->user;
        $url = sprintf(self::URL_PIWIK . self::REQUEST_USERGET, $this->token, $this->username);
        $user = json_decode($this->request($url));
        if ($user && is_array($user) && count($user) === 1) {
            $this->user = $user[0];
            return $user[0];
        }
        return false;
    }

    /**
     * Renvoi le token de l'utilisateur
     */
    function getUserToken() {
        if(!is_null($this->userToken)){
            return $this->userToken;
        }

        if ($this->getUser()) {
            $url = sprintf(self::URL_PIWIK . self::REQUEST_USERTOKEN, urlencode($this->token), urlencode($this->username), md5($this->getPassword()));

            $sz = $this->request($url);
            $json = json_decode($sz);

            $this->userToken = $json->value;

            return $this->userToken;
        }

        return null;
    }

    /**
     * Crée un utilisateur s'il n'existe pas et le renvoi
     * @return type
     */
    public function createUser() {
        if (!$this->getUserExists()) {
            echo "Piwik API : Ajout de l'utilisateur au serveur de statistiques\n";
            $url = sprintf(self::URL_PIWIK . self::REQUEST_USERCREATE, urlencode($this->token), urlencode($this->username), urlencode($this->getPassword()), isset($this->email) ? urlencode($this->email) : urlencode($this->username . '@ac-toulouse.fr')
            );
            $creation = json_decode($this->request($url));
            if (!$creation->result)
                return false;
        }else {
            echo "Piwik API : L'utilisateur existe deja\n";
        }
        return $this->getUser();
    }

    public function createSiteIfNotExists() {
        if (!$this->getSiteId()) {
            echo "Piwik API : Ajout du site sur le serveur de statistiques\n";
            $url = sprintf(self::URL_PIWIK . self::REQUEST_ADDSITE, $this->token, urlencode($this->name), $this->getUrlRequestSegment());
            $d = $this->request($url);
        } else {
            echo "Piwik API : Le site existe deja.\n";
        }

        return $this->getSiteId();
    }

    public function addUserAccess() {
        if ($this->getSiteId()) {
            echo "Piwik API : Ajout des droits a l'utilisateur\n";
            $url = sprintf(self::URL_PIWIK . self::REQUEST_USERACCESS, urlencode($this->token), urlencode($this->username), self::ACCESS_VIEW, $this->getSiteId()
            );

            $this->request($url);
        }
    }

    /**
     * Retourne true si l'user existe
     * @return bool
     */
    public function getUserExists() {
        if (isset($this->userExists)){
            return $this->userExists;
        }
        $sz = $this->request(sprintf(self::URL_PIWIK . self::REQUEST_USEREXISTS, $this->token,$this->username));

        $json = json_decode($sz);
        $this->userExists = (bool) $json->value;
        return $this->userExists;
    }

    public function deleteSite() {
        if ($this->getSiteId()) {
            echo "Piwik API : Suppression du site\n";
            $url = sprintf(self::URL_PIWIK . self::REQUEST_SITEDELETE, $this->token, $this->getSiteId());
            $this->request($url);
            return true;
        } else {
            echo "Piwik API : le site n'existe pas dans la base de donnees\n";
        }
        return false;
    }

    public function deleteUser() {
        if ($this->getUser()) {
            echo "Piwik API : Suppression de l'utilisateur\n";
            $user = $this->getUser();
            $url = sprintf(self::URL_PIWIK . self::REQUEST_USERDELETE, $this->token, urlencode($user->login));
            return $this->request($url);
        } else {
            echo "Piwik API : l'utilisateur n'est pas present dans la base de donnees\n";
        }
        return false;
    }

    public function deleteAll() {
        $this->deleteSite();
        $this->deleteUser();
    }

    private function getUrlRequestSegment() {
        $uriSegment = '';
        $i = 0;
        foreach ($this->urls as $url) {
            $uriSegment .= "&urls[$i]=" . urlencode($url);
            $i++;
        }
        return $uriSegment;
    }

    private function request($url) {
      try {
            $c = curl_init($url);
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($c, CURLOPT_SSL_VERIFYHOST, false);

            if (self::$proxyEnabled) {
                $c = curl_init($url);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($c, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt($c, CURLOPT_PROXY, self::$proxyHost.':'.self::$proxyPort);
            }

            $result = curl_exec($c);
            curl_close($c);
        } catch (Exception $exc) {
            curl_close($c);
            echo $exc->getMessage();
        }

        if($result === false) {
            throw new \Exception('Unable to get a correct response from request');
        }

        return $result;
    }

    private function getPassword() {
        if(is_null($this->password)) {
            $this->password = 'MdP!;_'.$this->username.'_';
        }
        return $this->password;
    }
}