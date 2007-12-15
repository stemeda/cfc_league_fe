<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Rene Nitzsche (rene@system25.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

// Die Datenbank-Klasse
require_once(t3lib_extMgm::extPath('rn_base') . 'util/class.tx_rnbase_util_DB.php');
require_once(t3lib_extMgm::extPath('rn_base') . 'model/class.tx_rnbase_model_base.php');


/**
 * Model für ein Team.
 */
class tx_cfcleaguefe_models_team extends tx_rnbase_model_base {
  var $_players;
  var $_coaches;
  var $_supporters;
  var $_club;
  /** Array with loaded team instances */
  private static $instances;

  function getTableName(){return 'tx_cfcleague_teams';}

  /**
   * Liefert den Namen des Teams
   * @param $confId die TS-Config für den Teamdatensatz
   */
  function getNameWrapped($formatter, $confId = 'team.') {
    return $formatter->wrap($this->record['name'], $confId . 'teamName.');
  }

  /**
   * Liefert den Verein des Teams als Objekt
   * @return Verein als Objekt oder 0
   */
  function getClub() {
    if(!$this->record['club']) return 0;
    if(!isset($this->_club)) {
      $className = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_club');
      $_club = new $className($this->record['club']);
    }
    return $_club;
  }

  /**
   * Liefert die Trainer des Teams in der vorgegebenen Reihenfolge als Profile. Der
   * Key ist die laufende Nummer und nicht die UID!
   */
  function getCoaches() {
    if(is_array($this->_coaches))
      return $this->_coaches;
    $this->_coaches = $this->_getTeamMember('coaches');
    return $this->_coaches;
  }

  /**
   * Liefert die Betreuer des Teams in der vorgegebenen Reihenfolge als Profile. Der
   * Key ist die laufende Nummer und nicht die UID!
   */
  function getSupporters() {
    if(is_array($this->_supporters))
      return $this->_supporters;
    $this->_supporters = $this->_getTeamMember('supporters');
    return $this->_supporters;
  }

  /**
   * Liefert die Spieler des Teams in der vorgegebenen Reihenfolge als Profile. Der
   * Key ist die laufende Nummer und nicht die UID!
   */
  function getPlayers() {
    if(is_array($this->_players))
      return $this->_players;
    $this->_players = $this->_getTeamMember('players');
    return $this->_players;
  }


  /**
   * Liefert das Logo des Teams. Es ist entweder das zugeordnete Logo des Teams oder 
   * das Logo des Vereins.
   * @param tx_rnbase_util_FormatUtil $formatter
   * @param string $confId
   */
  function getLogo(&$formatter, $confId) {
    $image = false;
    // Hinweis: Die TCA-Definition ist im Team und im Club verschieden. Im Team ist es eine 1-n Relation
    // Und im Club eine n-m-Beziehung. Daher muss der Zugriff unterschiedlich erfolgen.
    // Grund dafür gibt es keinen...

    // Vorrang hat das Teamlogo
    if($this->record['dam_logo']) {
      $damPics = tx_dam_db::getReferencedFiles('tx_cfcleague_teams', $this->uid, 'relation_field_or_other_ident');
      if(list($uid, $filePath) = each($damPics['files'])) {
        // Das Bild muss mit einem alternativen cObj erzeugt werden, damit Gallerie nicht aktiviert wird
//        $image = $formatter->getDAMImage($filePath, 'matchreport.logo.', 'cfc_league', 'cObjLogo');
        $image = $formatter->getDAMImage($filePath, $confId, 'cfc_league', 'cObjLogo');
      }
    }
    if(!$image) {
      // Wir suchen den Verein
      $club = $this->getClub();
      // Ist ein Logo vorhanden?
      if(is_object($club) && $club->record['dam_logo']) {
        $damPics = tx_dam_db::getReferencedFiles('tx_cfcleague_club', $club->uid, 'dam_images');
        if(list($uid, $filePath) = each($damPics['files'])) {
          // Das Bild muss mit einem alternativen cObj erzeugt werden, damit Gallerie nicht aktiviert wird
          $image = $formatter->getDAMImage($filePath, $confId, 'cfc_league', 'cObjLogo');
        }
      }
    }
    // Es ist kein Logo vorhanden    
    if(!$image) {
//      $conf = $this->_configurations->get('matchreport.logo.noLogo_stdWrap.');
      $conf = $formatter->configurations->get($confId . 'noLogo_stdWrap.');
//      $image = $formatter->configurations->getCObj(0)->stdWrap('', $conf);
//if($this->isDummy())
//  t3lib_div::debug($conf, 'tx_cfcleaguefe_models_team');
      $image = $formatter->dataStdWrap($this->record, '', $confId . 'noLogo_stdWrap.');
    }
    return $image;
  }

  /**
   * Liefert true, wenn für das Team eine Einzelansicht verlinkt werden kann.
   */
  function hasReport() {
    return intval($this->record['link_report']);
  }

  /**
   * Returns cached instances of teams
   *
   * @param int $teamUid
   * @return tx_cfcleaguefe_models_team
   */
  static function getInstance($teamUid) {
    $uid = intval($teamUid);
    if(!$uid) throw new Exception('Team uid expected. Was: >' . $teamUid . '<', -1);
    if(! self::$instances[$uid]) {
      $className = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_team');
      self::$instances[$uid] = new $className($teamUid);
    }
    return self::$instances[$uid];
  }
  /**
   * Liefert Mitglieder des Teams als Array. Teammitglieder sind Spieler, Trainer und Betreuer.
   * Die gefundenen Profile werden sortiert in der Reihenfolge im Team geliefert.
   * @column Name der DB-Spalte mit den gesuchten Team-Mitgliedern
   */
  function _getTeamMember($column) {
    if(strlen(trim($this->record[$column])) > 0 ) {
      $what = '*';
      $from = 'tx_cfcleague_profiles';
      $options['where'] = 'uid IN (' .$this->record[$column] . ')';
      $options['wrapperclass'] = 'tx_cfcleaguefe_models_profile';

      $rows = tx_rnbase_util_DB::doSelect($what,$from,$options,0);
      return $this->sortPlayer($rows, $column);
    }
    return array();
  }

  /**
   * Sortiert die Personen (Spieler/Trainer) entsprechend der Reihenfolge im Team
   * @param $profiles array of tx_cfcleaguefe_models_profile
   */
  function sortPlayer($profiles, $recordKey = 'players') {
    $ret = array();
    if(strlen(trim($this->record[$recordKey])) > 0 ) {
      if(count($profiles)) {
        // Jetzt die Spieler in die richtige Reihenfolge bringen
        $uids = t3lib_div::intExplode(',', $this->record[$recordKey]);
        $uids = array_flip($uids);
        foreach($profiles as $player) {
          $ret[$uids[$player->uid]] = $player;
        }
      }
    }
    else {
      // Wenn keine Spieler im Team geladen sind, dann wird das Array unverändert zurückgegeben
      return $profiles;
    }
    return $ret;
  }

  /**
   * Check if team is a dummy for free_of_match.
   *
   * @return boolean
   */
  function isDummy(){
    return intval($this->record['dummy']) != 0;
  }
  /**
   * Return all teams by an array of uids.
   * @param mixed $teamIds
   * @return array of tx_cfcleaguefe_models_team
   */
  function getTeamsByUid($teamIds) {
    if(!is_array($teamIds)) {
      $teamIds = t3lib_div::intExplode(',',$teamIds);
    }
    if(!count($teamIds))
      return array();
    $teamIds = implode($teamIds, ',');
    $what = tx_cfcleaguefe_models_team::getWhat();
    $from = 'tx_cfcleague_teams';
    $options['where'] = 'tx_cfcleague_teams.uid IN (' . $teamIds . ') ';
    $options['wrapperclass'] = 'tx_cfcleaguefe_models_team';

    return tx_rnbase_util_DB::doSelect($what,$from,$options,0);
  }

  /**
   * Returns Teams by competition and club. This method can be used static.
   * TODO: Als static deklarieren
   */
  function getTeams($competitionIds, $clubIds) {
    $competitionIds = implode(t3lib_div::intExplode(',',$competitionIds), ',');
    $clubIds = implode(t3lib_div::intExplode(',',$clubIds), ',');

    $what = tx_cfcleaguefe_models_team::getWhat();
    $from = Array('
       tx_cfcleague_teams 
         JOIN tx_cfcleague_competition ON FIND_IN_SET( tx_cfcleague_teams.uid, tx_cfcleague_competition.teams )', 
         'tx_cfcleague_teams');

    $options['where'] = 'tx_cfcleague_teams.club IN (' . $clubIds . ') AND ';
    $options['where'] .= 'tx_cfcleague_competition.uid IN (' . $competitionIds . ') ';
    $options['wrapperclass'] = 'tx_cfcleaguefe_models_team';

    return tx_rnbase_util_DB::doSelect($what,$from,$options,0);

/*
SELECT tx_cfcleague_teams.uid, tx_cfcleague_teams.name, tx_cfcleague_competition.uid AS comp_uid, tx_cfcleague_competition.name AS comp_name, tx_cfcleague_competition.teams AS comp_teams
FROM tx_cfcleague_teams
JOIN tx_cfcleague_competition ON FIND_IN_SET( tx_cfcleague_teams.uid, tx_cfcleague_competition.teams )
WHERE tx_cfcleague_teams.club =1
AND tx_cfcleague_competition.uid =1
*/
  }

  /**
   * Liefert alle Spalten des Teams
   * TODO: Über TCA dynamisch gestalten
   */
  function getWhat() {
    return '
      tx_cfcleague_teams.uid, tx_cfcleague_teams.name, tx_cfcleague_teams.short_name, tx_cfcleague_teams.dummy,
      tx_cfcleague_teams.coaches, tx_cfcleague_teams.players, tx_cfcleague_teams.club,
      tx_cfcleague_teams.dam_images, tx_cfcleague_teams.comment, tx_cfcleague_teams.link_report
    ';
  }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_team.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_team.php']);
}

?>