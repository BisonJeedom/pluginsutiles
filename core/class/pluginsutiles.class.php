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

  public function checkChangesSinceLastRecord($_id, $_olditem, $_newitem) {
    foreach ($_olditem[$_id] as $key => $value) {
      //log::add(__CLASS__, 'info', '$_olditem : ' . $key . ' => ' . $value);
      //log::add(__CLASS__, 'info', '$_newitem : ' . $key . ' => ' . $_newitem[$key]);
      if ($_newitem[$key] != $value) {
        return 1;
      }
    }
    return 0;
  }


  public function refreshMarket() {
    $fullrefresh = config::byKey('fullrefresh', __CLASS__);
    if ($fullrefresh == 1) { // Les mots-clefs d'au moins un des équipements a été modifié
      $timeState = null;
      $txtLog = 'Recherche dans la totalité des plugins du Market';
    } else {
      $timeState = 'newest';
      $txtLog = 'Recherche dans les nouveaux plugins du Market uniquement';
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
    $cfg_keywords = $this->getConfiguration("cfg_keywords", null);
    log::add(__CLASS__, 'info', 'Liste des mots clefs : ' . $cfg_keywords);
    $keywords = array_filter(explode(';', $cfg_keywords));

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
    $cfg_notif = $this->getConfiguration("cfg_notif", 0);

    $error = 1;
    $nb_found = 0;
    $nb_plugins = 0;

    foreach ($_markets as $plugin) {
      // log::add(__CLASS__, 'debug', 'receive plugin =>' . json_encode($plugin));
      $nb_plugins++;
      $error = 0;

      $id = $plugin['id'];
      $name = $plugin['name'];
      $author = $plugin['author'];
      $cost = $plugin['cost'];
      $realcost = $plugin['realcost'];
      $description = $plugin['description'];
      $utilisation = $plugin['utilization'];
      $beta = ($plugin['status']['beta'] ?? '' == "1") ?? false;
      $stable = ($plugin['status']['stable'] ?? '' == "1") ?? false;
      $private = $plugin['private'] == "1";

      if ($cost == 0) {
        $cost_txt = 'Gratuit';
      } else {
        $cost_txt = $cost . '€';
      }

      $pluginAvailable = false;
      if (count($keywords) > 0) {  // on fait la recherche si seulement on a 1 mot clé
        if ($this->getConfiguration('cfg_checkStableOnly', 1) && $beta && !$stable) {
          // log::add(__CLASS__, 'warning', 'ask for Stable Only but Beta version');
          continue;
        }

        if ($this->getConfiguration('cfg_checkBetaOnly', 1) && $beta && $stable) {
          // log::add(__CLASS__, 'warning', 'ask for Beta Only but Stable version');
          continue;
        }

        if ($this->getConfiguration('checkName', 1) && self::arrayContainsWord($name, $keywords)) {
          // log::add(__CLASS__, 'warning', 'one key found in *NAME*');
          $pluginAvailable = true;
        }

        if ($this->getConfiguration('checkDescription', 1) && self::arrayContainsWord($description, $keywords)) {
          // log::add(__CLASS__, 'warning', 'one key found in *DESC*');
          $pluginAvailable = true;
        }

        if ($this->getConfiguration('checkUtilisation', 0) && self::arrayContainsWord($utilisation, $keywords)) {
          // log::add(__CLASS__, 'warning', 'one key found in *UTILISATION*');
          $pluginAvailable = true;
        }

        if ($this->getConfiguration('checkAutor', 0) && self::arrayContainsWord($author, $keywords)) {
          // log::add(__CLASS__, 'warning', 'one key found in *AUTHOR*');
          $pluginAvailable = true;
        }

        if ($this->getConfiguration('cfg_checkAllStable', 0) && $beta && $stable) {
          // log::add(__CLASS__, 'warning', '*Stable Only*');
          $pluginAvailable = true;
        }

        if ($this->getConfiguration('cfg_checkAllBeta', 0) && $beta && !$stable) {
          // log::add(__CLASS__, 'warning', '*Beta Only*');
          $pluginAvailable = true;
        }
      }

      $item_detail = array(
        "date" => date("d/m/Y H:i"),
        "id" => $id, "name" => $name,
        "author" => $author, "private" => $private,
        "beta" => $beta, "stable" => $stable,
        "cost" => $cost, "realcost" => $realcost
      ); // Ajout dans l'historique


      if ($this->getConfiguration('checkDiscount', 0) && ($realcost != $cost)) {
        log::add(__CLASS__, 'info', 'Plugin en promo :' . $name);
        $array_IdAlreadyFound[$id] = array("private" => $private, "beta" => $beta, "stable" => $stable, "realcost" => $realcost); // Ajout de l'id du plugin trouvé et signalé
        $array_historique[] = array_merge($item_detail, array("discount" => true));
      }

      if ($pluginAvailable) {
        $nb_found++;
        log::add(__CLASS__, 'info', 'Plugin correspondant aux critères :');

        if (!array_key_exists($id, $array_IdAlreadyFound)) {
          $new = 'Nouveau'; // id non trouvé dans le tableau
        } else {
          if ($this->getConfiguration('cfg_checkChanges', 0)) {
            if (self::checkChangesSinceLastRecord($id, $array_IdAlreadyFound, $item_detail)) {;
              $new = 'Nouveau'; // changements pour cet id (private, beta, stable, realcost)
            }
          }
          $new = 'Ancien'; // id déjà présent dans le tableau
        }

        if ($beta && !$stable) {
          $msg_version = ' [Beta] ';
        } elseif ($beta && $stable) {
          $msg_version = ' [Stable] ';
        } else {
          $msg_version = '';
        }

        log::add(__CLASS__, 'info', '-> [' . $new . '] ' . $name . ' par ' . $author . $msg_version . '(' . $cost_txt . ')');
        log::add(__CLASS__, 'info', '-> [' . $new . '] description : ' . $description);
        log::add(__CLASS__, 'info', '-> [' . $new . '] utilisation : ' . $utilisation);

        if ($new == 'Nouveau') {
          $array_IdAlreadyFound[$id] = array("private" => $private, "beta" => $beta, "stable" => $stable, "realcost" => $realcost); // Ajout de l'id du plugin trouvé et signalé
          $array_historique[] = $item_detail;

          $msg = 'Plugin disponible correspondant aux critères : ' . $name . ' par ' . $author . $msg_version . '(' . $cost_txt . ')';
          if ($cfg_messagecenter == 1) {
            log::add(__CLASS__, 'info', '-> Envoi dans le centre de message');
            message::add(__CLASS__, $msg);
          }

          if ($cfg_notif == 1) {
            $notifParam = $this->getConfiguration("action_notif", array());
            foreach ($notifParam as $notif) {
              $notifCmdId = str_replace('#', '', $notif['cmd'] ?? '');

              /** @var cmd $notifObj */
              $notifObj = cmd::byId($notifCmdId);
              if (!is_object($notifObj)) {
                log::add(__CLASS__, 'error', 'La commande de notification n\'existe pas');
                continue;
              }
              $notifDetail = $item_detail;
              $notifDetail['defaultMsg'] = $msg;
              $titleNotif = $this->replaceCustomData($notif['options']['title'] ?? 'Nouveaux plugins trouvés !', $notifDetail);
              $msgNotif = $this->replaceCustomData($notif['options']['message'] ?? $msg, $notifDetail);
              $notifObj->execCmd(array('title' => $titleNotif, 'message' => $msgNotif));
            }
          }
        }
      } else {
        log::add(__CLASS__, 'debug', $name . ' par ' . $author . ' (' . $cost_txt . ')');
        log::add(__CLASS__, 'debug', 'description : ' . $description);
        log::add(__CLASS__, 'debug', 'utilisation : ' . $utilisation);
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

    log::add(__CLASS__, 'info', 'Recherche terminée parmi ' . $nb_plugins . ' plugins : ' . $nb_found . ' nouveaux plugins trouvé(s) et correspondant aux critères');
    return $array_historique;
  }

  public function refreshPluginsFromMarket() {
    $markets = pluginsutiles::refreshMarket();
    /** @var pluginsutiles $eqLogic */
    foreach (eqLogic::byType('pluginsutiles') as $eqLogic) {
      if ($eqLogic->getIsEnable()) {
        $info = $eqLogic->search($markets);
        log::add(__CLASS__, 'debug', 'setConf array_historique data ==> ' . json_encode($info));
        $eqLogic->setConfiguration('array_historique', $info);
        $eqLogic->save(true);
      }
    }
  }

  public function replaceCustomData(string $data, array $plugin = array()) {

    $arrResearch = array('#eqId#', '#eqName#', '#msg#', '#author#', '#name#', '#cost#');
    $arrReplace = array($this->getId(), $this->getName(), $plugin['defaultMsg'], $plugin['author'], $plugin['name'], $plugin['cost']);

    return str_replace($arrResearch, $arrReplace, $data);
  }

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
    $cfg_keywords = $this->getConfiguration('cfg_keywords', null);
    $cfg_keywords_prev = $this->getConfiguration('cfg_keywords_previous', null);

    if ($cfg_keywords_prev == $cfg_keywords  && $cfg_keywords  == null) {
      log::add(__CLASS__, 'debug', 'keyword null - initialisation');
      config::save('fullrefresh', 1, __CLASS__); // Passage à 1 du "fullrefesh" global suite au changement de la liste des mots clefs sur un équipement
      return;
    }

    if ($cfg_keywords_prev != $cfg_keywords) {
      log::add(__CLASS__, 'info', 'Modification des mots clefs : >' . $cfg_keywords . '<');
      log::add(__CLASS__, 'info', 'Anciens mots clefs : >' . $cfg_keywords_prev . '<');

      config::save('fullrefresh', 1, __CLASS__); // Passage à 1 du "fullrefesh" global suite au changement de la liste des mots clefs sur un équipement

      $array_historique = array(array("date" => date("d/m/Y H:i"), "id" => '', "name" => 'Mise à jour des mots clefs')); // Utilisation de l'historique un peu adapté pour informer du changement de mots-clefs
      $this->setConfiguration('cfg_keywords_previous', $cfg_keywords);

      // if keyword update, then refresh market check
      $markets = pluginsutiles::refreshMarket();
      $info = $this->search($markets);
      log::add(__CLASS__, 'debug', 'setConf array_historique data ==> ' . json_encode($info));
      $this->setConfiguration('array_historique', array_merge($array_historique, $info));
      $this->save(true);
    } else {
      // log::add(__CLASS__, 'info', 'AUCUN chgt de keys');
    }
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
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
        log::add('pluginustiles', 'debug', 'setConf array_historique data ==> ' . json_encode($info));
        $eqlogic->setConfiguration('array_historique', $info);
        $eqlogic->save(true);
        break;

      case 'removeHistory':
        $eqlogic->setConfiguration('array_historique', '');
        $eqlogic->setConfiguration('array_IdAlreadyFound', '');
        $eqlogic->setConfiguration('cfg_keywords_previous', null);
        $eqlogic->save(true);
        break;

      default:
        log::add('pluginustiles', 'debug', 'Erreur durant execute');
        break;
    }
  }

  /*     * **********************Getteur Setteur*************************** */
}
