<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class pluginsutiles extends eqLogic {


  public function arrayContainsWord($str, array $arr) {
    foreach ($arr as $word) {
      // Works in Hebrew and any other unicode characters
      // Thanks https://medium.com/@shiba1014/regex-word-boundaries-with-unicode-207794f6e7ed
      // Thanks https://www.phpliveregex.com/
      // Add casse-insensible - Bison
      if (preg_match('/(?i)(?<=[\s,.:;"\']|^)' . $word . '(?=[\s,.:;"\']|$)/', $str)) return true;
    }
    return false;
  }

  public function cleanKeyWords(array $arr) {
    $arr_excluded_words = array('le', 'la', 'les', 'de', 'des', 'un', 'une', 'et', 'sur', 'vers');
    foreach ($arr_excluded_words as $word) {
      $key = array_search($word, $arr);
      if ($key !== false) {
        unset($arr[$key]);
      }
    }
    return $arr;
  }

  /*
  public function refreshMarket() {
    $url = "https://market.jeedom.com/index.php?v=d&p=market&type=plugin";
    $data = file_get_contents($url);
    @$dom = new DOMDocument();
    $dom->loadHTML($data);
    
    foreach($dom->getElementsByTagName('div') as $div) {
        $id = $div->getAttribute('data-market_id');
    	//log::add(__CLASS__, 'debug', 'id : '.$id);
      
      	if ($id != '') {
          $nb++;
          $params = $div->getElementsByTagName('span');
          $name = $params->item(0)->nodeValue;
          $dev = substr($params->item(1)->nodeValue, 4); // "Par xxxx" -> "xxxx"
          $prix = $params->item(4)->nodeValue;
          log::add(__CLASS__, 'debug', $id.' -> '.$name.' par '.$dev.' : '.$prix);
        }
      
        
        for ($i = 0; $i < 7; $i++) {
        	log::add(__CLASS__, 'debug', $i.' : '.$params->item($i)->nodeValue);
        }
        
        
    }
    log::add(__CLASS__, 'debug', 'Total : '.$nb.' pluggins');
    return;
  }
  */

  public function refreshMarket() {
    $fullrefresh = config::byKey('fullrefresh', __CLASS__);
    if ($fullrefresh == 1) { // Les mots-clefs d'au moins un des équipements a été modifié
      $timeState = null;
      $txtLog = 'Recherche des mots clefs dans la totalité des plugins du Market';
    } else {
      $timeState = 'newest';
      $txtLog = 'Recherche des mots clefs dans les nouveaux plugins du Market';
    }
    log::add(__CLASS__, 'info', $txtLog);

    $markets = repo_market::byFilter(array(
      'type' => 'plugin',
      'timeState' => $timeState,
    ));

    if (is_array($markets)) {
      config::save('fullrefresh', 0, __CLASS__);
    }

    return utils::o2a($markets); // Conversion pour exploiter des clefs dans le tableau
  }


  public function search($_markets) {
    $eqLogicName = $this->getName();
    log::add(__CLASS__, 'debug', 'START ' . $eqLogicName);

    // Récupération des mots clefs
    $cfg_keywords = $this->getConfiguration("cfg_keywords", 0);
    log::add(__CLASS__, 'info', 'Liste des mots clefs : ' . $cfg_keywords);
    $keywords = explode(';', $cfg_keywords);

    // Récupération des ID de plugins déjà trouvés et signalés
    $array_IdAlreadyFound = $this->getConfiguration("array_IdAlreadyFound");
    if (empty($array_IdAlreadyFound)) {
      log::add(__CLASS__, 'debug', 'Plugins trouvés et déjà signalés : Aucun');
      $array_IdAlreadyFound = array();
    } else {
      log::add(__CLASS__, 'debug', 'Plugins trouvés et déjà signalés : ' . json_encode($array_IdAlreadyFound));
    }

    // Récupération des historiques
    $array_historique = $this->getConfiguration("array_historique");
    if (empty($array_historique)) {
      log::add(__CLASS__, 'debug', 'Historique : Aucun');
      $array_historique = array();
    } else {
      log::add(__CLASS__, 'debug', 'Historique : ' . json_encode($array_historique));
    }

    // Récupération avertissement dans le centre de message
    $cfg_messagecenter = $this->getConfiguration("cfg_messagecenter", 0);

    $error = 1;
    $nb_found = 0;
    $nb_plugins = 0;

    foreach ($_markets as $plugin) {
      $nb_plugins++;
      $error = 0;

      $id = $plugin['id'];
      $name = $plugin['name'];
      $author = $plugin['author'];
      $cost = $plugin['cost'];
      $description = $plugin['description'];

      if ($cost == 0) {
        $cost_txt = 'Gratuit';
      } else {
        $cost_txt = $cost . '€';
      }

      if (self::arrayContainsWord($description, $keywords)) {
        $nb_found++;
        log::add(__CLASS__, 'info', 'Plugin correspondant à un mots clefs :');

        if (array_search($id, $array_IdAlreadyFound) === false) {
          $new = 'Nouveau'; // id non trouvé dans le tableau
        } else {
          $new = 'Ancien'; // id déjà présent dans le tableau
        }

        log::add(__CLASS__, 'info', '-> [' . $new . '] ' . $name . ' par ' . $author . ' (' . $cost_txt . ')');
        log::add(__CLASS__, 'info', '-> [' . $new . '] description : ' . $description);

        if ($new == 'Nouveau') {
          $array_IdAlreadyFound[] = $id; // Ajout de l'id du plugin trouvé et signalé
          $array_historique[] = array("date" => date("d/m/Y H:i"), "id" => $id, "name" => $name, "author" => $author); // Ajout dans l'historique
          if ($cfg_messagecenter == 1) {
            log::add(__CLASS__, 'info', '-> Envoi dans le centre de message');
            message::add(__CLASS__, 'Plugin disponible correspondant aux mots clefs : ' . $name . ' par ' . $author . ' (' . $cost_txt . ')');
          }
        }
      } else {
        log::add(__CLASS__, 'debug', $name . ' par ' . $author . ' (' . $cost_txt . ')');
        log::add(__CLASS__, 'debug', 'description : ' . $description);
      }
    }

    if ($error == 0) {
      log::add(__CLASS__, 'debug', 'Mise à jour des plugins signalés : ' . json_encode($array_IdAlreadyFound));
      //config::save('array_IdAlreadyFound', $array_IdAlreadyFound, __CLASS__);
      $this->setConfiguration('array_IdAlreadyFound', $array_IdAlreadyFound);
      $this->save(true);

      log::add(__CLASS__, 'debug', 'Historique : ' . json_encode($array_historique));
      //config::save('array_historique', $array_historique, __CLASS__);
      //$this->setConfiguration('array_historique', $array_historique);

      config::save('fullrefresh', 0, __CLASS__);
    }

    log::add(__CLASS__, 'info', 'Recherche terminée parmi ' . $nb_plugins . ' plugins : ' . $nb_found . ' plugins trouvé(s) et correspondant aux mots clefs');
    return $array_historique;
  }

  /*
  public function refreshFromMarket() {
    log::add(__CLASS__, 'debug', 'START');

    // Récupération des mots clefs
    $cfg_keywords = config::byKey('cfg_keywords', __CLASS__);
    log::add(__CLASS__, 'info', 'Liste des mots clefs : ' . $cfg_keywords);
    $keywords = explode(';', $cfg_keywords);

    // Récupération des ID de plugins déjà trouvés et signalés
    $array_IdAlreadyFound = config::byKey('array_IdAlreadyFound', __CLASS__);
    if (empty($array_IdAlreadyFound)) {
      log::add(__CLASS__, 'debug', 'Plugins trouvés et déjà signalés : Aucun');
      $array_IdAlreadyFound = array();
    } else {
      log::add(__CLASS__, 'debug', 'Plugins trouvés et déjà signalés : ' . json_encode($array_IdAlreadyFound));
    }

    // Récupération des historiques ID de plugins déjà trouvés et signalés
    $array_historique = config::byKey('array_historique', __CLASS__);
    if (empty($array_historique)) {
      log::add(__CLASS__, 'debug', 'Historique : Aucun');
      $array_historique = array();
    } else {
      log::add(__CLASS__, 'debug', 'Historique : ' . json_encode($array_historique));
    }


    //$keywords = self::cleanKeyWords($keywords);
    //log::add(__CLASS__, 'info', 'Liste des mots clefs clean : '.print_r($keywords, true));

    //$updated_timestamp = config::byKey('updated_timestamp', __CLASS__);

    $cfg_messagecenter = config::byKey('cfg_messagecenter', __CLASS__);

    $fullrefresh = config::byKey('fullrefresh', __CLASS__);
    if ($fullrefresh == 1) {
      $timeState = null;
      $txtLog = 'Recherche des mots clefs dans la totalité des plugins du Market';
    } else {
      $timeState = 'newest';
      $txtLog = 'Recherche des mots clefs dans les nouveaux plugins du Market';
    }
    log::add(__CLASS__, 'info', $txtLog);

    $markets = repo_market::byFilter(array(
      'type' => 'plugin',
      'timeState' => $timeState,
    ));

    $markets = utils::o2a($markets); // Conversion pour exploiter des clefs dans le tableau

    /*
    foreach($markets as $key => $item) {
    	log::add(__CLASS__, 'debug', 'key : '.$key);
        foreach($item as $k => $i) {
          log::add(__CLASS__, 'debug', 'k : '.$k);
          log::add(__CLASS__, 'debug', 'i : '.$i);
          if ($k == 'allowVersion') {
          	foreach($i as $kk => $ii) {
            	log::add(__CLASS__, 'debug', 'kk : '.$kk);
          		log::add(__CLASS__, 'debug', 'ii : '.$ii);
            }
          }
        }
    }
  

    $error = 1;
    $nb_found = 0;
    $nb_plugins = 0;

    foreach ($markets as $plugin) {
      $nb_plugins++;
      $error = 0;

      $id = $plugin['id'];
      $name = $plugin['name'];
      $author = $plugin['author'];
      $cost = $plugin['cost'];
      $description = $plugin['description'];

      if ($cost == 0) {
        $cost_txt = 'Gratuit';
      } else {
        $cost_txt = $cost . '€';
      }

      if (self::arrayContainsWord($description, $keywords)) {
        $nb_found++;
        log::add(__CLASS__, 'info', 'Plugin correspondant à un mots clefs :');

        if (array_search($id, $array_IdAlreadyFound) === false) {
          $new = 'Nouveau'; // id non trouvé dans le tableau
        } else {
          $new = 'Ancien'; // id déjà présent dans le tableau
        }

        log::add(__CLASS__, 'info', '-> [' . $new . '] ' . $name . ' par ' . $author . ' (' . $cost_txt . ')');
        log::add(__CLASS__, 'info', '-> [' . $new . '] description : ' . $description);

        if ($new == 'Nouveau') {
          $array_IdAlreadyFound[] = $id; // Ajout de l'id du plugin trouvé et signalé
          $array_historique[] = array(date("d/m/Y"), $id, $name, $author);
          if ($cfg_messagecenter == 1) {
            log::add(__CLASS__, 'info', '-> Envoi dans le centre de message');
            message::add(__CLASS__, 'Plugin disponible correspondant aux mots clefs : ' . $name . ' par ' . $author . ' (' . $cost_txt . ')');
          }
        }
      } else {
        log::add(__CLASS__, 'debug', $name . ' par ' . $author . ' (' . $cost_txt . ')');
        log::add(__CLASS__, 'debug', 'description : ' . $description);
      }
    }

    if ($error == 0) {
      //$json_IdAlreadyFound = json_encode($array_IdAlreadyFound, JSON_FORCE_OBJECT);
      log::add(__CLASS__, 'debug', 'Mise à jour des plugins signalés : ' . json_encode($array_IdAlreadyFound));
      config::save('array_IdAlreadyFound', $array_IdAlreadyFound, __CLASS__);

      log::add(__CLASS__, 'debug', 'Historique : ' . json_encode($array_historique));
      config::save('array_historique', $array_historique, __CLASS__);

      config::save('fullrefresh', 0, __CLASS__);
      //config::save('updated_timestamp', time(), __CLASS__);
    }

    log::add(__CLASS__, 'info', 'Recherche terminée parmis ' . $nb_plugins . ' plugins : ' . $nb_found . ' plugins trouvé(s) et correspondant aux mots clefs');
  }
  */

  /*     * *************************Attributs****************************** */

  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  /*     * ***********************Methode static*************************** */

  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  public static function cron5() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  */

  /*
  * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {
  }
  */

  public static function addCronCheckMarket() {
    $cron = cron::byClassAndFunction(__CLASS__, 'refreshPluginsFromMarket');
    if (!is_object($cron)) {
      $cron = new cron();
      $cron->setClass(__CLASS__);
      $cron->setFunction('refreshPluginsFromMarket');
    }
    $cron->setEnable(1);
    $cron->setDeamon(0);
    $cron->setSchedule('0 ' . rand(0, 4) . ' * * *');
    $cron->setTimeout(5);
    $cron->save();
  }

  public static function removeCronItems() {
    try {
      $crons = cron::searchClassAndFunction(__CLASS__, 'refreshPluginsFromMarket');
      if (is_array($crons)) {
        foreach ($crons as $cron) {
          $cron->remove();
        }
      }
    } catch (Exception $e) {
    }
  }

  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
    $cfg_keywords = $this->getConfiguration('cfg_keywords');
    log::add(__CLASS__, 'info', 'Modification des mots clefs : ' . $cfg_keywords);
    config::save('fullrefresh', 1, __CLASS__); // Passage à 1 du "fullrefesh" global suite au changement de la liste des mots clefs sur un équipement

    $array_historique = $this->getConfiguration('array_historique');
    if (empty($array_historique)) {
      $array_historique = array();
    }

    $array_historique[] = array("date" => date("d/m/Y H:i"), "id" => '', "name" => 'Mise à jour des mots clefs', "author" => ''); // Utilisation de l'historique un peu adapté pour informer du changement de mots-clefs
    $this->setConfiguration('array_historique', $array_historique);
    $this->save(true);
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
    $markets = pluginsutiles::refreshMarket();
    $info = $this->search($markets);
    log::add(__CLASS__, 'debug', 'setConf array_historique data ==> ' . json_encode($info));
    $this->setConfiguration('array_historique', $info);
    $this->save(true);
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
    $refresh = $this->getCmd(null, 'refresh');
    if (!is_object($refresh)) {
      $refresh = new pluginsutilesCmd();
      $refresh->setName(__('Rafraichir', __FILE__));
    }
    $refresh->setEqLogic_id($this->getId());
    $refresh->setLogicalId('refresh');
    $refresh->setType('action');
    $refresh->setSubType('other');
    $refresh->save();

    $refresh = $this->getCmd(null, 'removeHistory');
    if (!is_object($refresh)) {
      $refresh = new pluginsutilesCmd();
      $refresh->setName(__('Supprimer tout l\'historique', __FILE__));
    }
    $refresh->setEqLogic_id($this->getId());
    $refresh->setLogicalId('removeHistory');
    $refresh->setType('action');
    $refresh->setSubType('other');
    $refresh->save();
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */

  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_cfg_keywords($value) {}
  */


  /*     * **********************Getteur Setteur*************************** */
}

class pluginsutilesCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {

    /** @var pluginsutiles $eqlogic */
    $eqlogic = $this->getEqLogic();
    switch ($this->getLogicalId()) {
      case 'refresh':
        $markets = $eqlogic->refreshMarket();
        $info = $eqlogic->search($markets);
        log::add(__CLASS__, 'debug', 'setConf array_historique data ==> ' . json_encode($info));
        $eqlogic->setConfiguration('array_historique', $info);
        $eqlogic->save(true);
        break;

      case 'removeHistory':
        $eqlogic->setConfiguration('array_historique', '');
        $eqlogic->save(true);
        break;

      default:
        log::add(__CLASS__, 'debug', 'Erreur durant execute');
        break;
    }
  }

  /*     * **********************Getteur Setteur*************************** */
}
