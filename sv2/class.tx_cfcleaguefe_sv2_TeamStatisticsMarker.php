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

require_once(t3lib_extMgm::extPath('div') . 'class.tx_div.php');
tx_div::load('tx_rnbase_util_BaseMarker');

/**
 * Marker class for player statistics
 * 
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_sv2_TeamStatisticsMarker extends tx_rnbase_util_BaseMarker {

  function parseTemplate($srvTemplate, &$stats, &$formatter, $statsConfId, $statsMarker) {
    $configurations =& $formatter->configurations;
    // Das Template für ein Team holen
    $template = $formatter->cObj->getSubpart($srvTemplate,'###'.$statsMarker.'_TEAM###');
    
    // Es wird der TeamMarker verwendet
    $markerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_TeamMarker');
    $markerObj = new $markerClass;
    $markerObj->initLabelMarkers($formatter, $statsConfId.'team.', $statsMarker.'_TEAM');
    $markerArray = $markerObj->initTSLabelMarkers($formatter, $statsConfId, $statsMarker);
    
    $rowRoll = intval($configurations->get($statsConfId.'team.roll.value'));
    $rowRollCnt = 0;
    $parts = array();
    foreach ($stats as $teamStat) {
      try {
        $teamClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_models_team');
        $team = call_user_func(array($teamClass,'getInstance'), $teamStat['team']);
      }
      catch (Exception $e) {
        continue; // Ohne Team wird auch nix gezeigt
      }
    	$team->record = array_merge($teamStat, $team->record);
    	// Jetzt für jedes Team das Template parsen
      $parts[] = $markerObj->parseTemplate($template, $team, $formatter, $statsConfId.'team.', $linkTeam, $statsMarker.'_TEAM');
    	
    }
    // Jetzt die einzelnen Teile zusammenfügen
    $subpartArray['###'.$statsMarker.'_TEAM###'] = implode($parts, $configurations->get($statsMarker.'team.implode'));

    $markerArray['###TEAMCOUNT###'] = count($parts);
    return $formatter->cObj->substituteMarkerArrayCached($srvTemplate, $markerArray, $subpartArray);
    
  }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv2/class.tx_cfcleaguefe_sv2_TeamStatisticsMarker.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/sv2/class.tx_cfcleaguefe_sv2_TeamStatisticsMarker.php']);
}

?>