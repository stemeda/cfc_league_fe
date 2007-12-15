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

require_once(t3lib_extMgm::extPath('cal').'model/class.tx_cal_phpicalendar_model.php');

/**
 * A model for the calendar.
 *
 * @author Rene Nitzsche
 */
class tx_cfcleaguefe_models_match_calevent extends tx_cal_phpicalendar_model {

  var $location;
  var $isException;
  var $category;
  var $_match;
  
  
  function tx_cfcleaguefe_models_match_calevent(&$controller, &$match, $isException, $serviceKey){
  	$this->tx_cal_model($controller, $serviceKey);		
  	$this->createEvent($match, $isException);
  	$this->isException = $isException;
  }
  
  /**
   * Wir überschreiben die Methode der Basisklasse, damit wir die eigenen Marker verwenden können.
   */
  function fillTemplate($subpartName){
    $file = $this->cObj->fileResource($this->conf['view.']['cfc_league_events.']['template']);
    if ($file == '') {
    	return '<h3>cal: no match template file found:</h3>'.$this->conf['view.']['cfc_league_events.']['template'];
    }

    $template = $this->cObj->getSubpart($file, $subpartName);
    if(!$template){
      return 'could not find the '.$subpartMarker.' subpart-marker in view.cfc_league_events.template: '.$this->conf['view.']['cfc_league_events.']['template'];
    }

    $configurations = tx_div::makeInstance('tx_rnbase_configurations');
    $configurations->init($this->conf, $this->cObj, 'cfc_league_fe', 'cfc_league_fe');
    $this->formatter = &$configurations->getFormatter();

    $markerArray = $this->formatter->getItemMarkerArrayWrapped($this->_match->record, 'view.cfc_league_events.match.', 0, 'MATCH_');

    $teamMarkerClass = tx_div::makeInstanceClassName('tx_cfcleaguefe_util_TeamMarker');
    $teamMarker = new $teamMarkerClass;

    $template = $teamMarker->parseTemplate($template, $this->_match->getHome(), $this->formatter, 'view.cfc_league_events.match.home.', null, 'MATCH_HOME');
    $template = $teamMarker->parseTemplate($template, $this->_match->getGuest(), $this->formatter, 'view.cfc_league_events.match.guest.', null, 'MATCH_GUEST');

    return $this->formatter->cObj->substituteMarkerArrayCached($template, $markerArray);

//t3lib_div::debug($markerArray, 'mdl_event');

  }
  
  function createEvent(&$match){
    $this->_match = $match;

    $row = $match->record;
    $this->setType($this->serviceKey);
    $this->setUid($row['uid']);
    $this->setStarttime($row['date']);
    $this->setEndtime($row['date'] + (60*105));
    $this->setTitle('Fussball');
    $this->setSubheader($row['short']);
    $this->setImage($row['image']);
    $this->setDescription($row['bodytext']);
    if($row['title']){
      $this->setCategory($row['title']);
    }
    $this->setLocation($row['stadium']);
  }
  
  /**
    * Returns the headerstyle name
    */
   function getHeaderStyle(){
   	return $this->conf['view.']['cfc_league_events.']['headerStyle'];
   }
   
   /**
    * Returns the bodystyle name
    */
   function getBodyStyle(){
   	return $this->conf['view.']['cfc_league_events.']["bodyStyle"];	
   }


 
  function getSubheader(){
  	return $this->subheader;
  }
  
  function setSubheader($s){
  	$this->subheader = $s;
  }
  
  function getImage(){
  	return $this->image;
  }
  
  function setImage($s){
  	$this->image = $s;
  }
  
  function getUntil(){
  	return 0;	
  }
  
  function getCategory(){
  	return $this->category;
  }
  
  function setCategory($cat){
  	$this->category = $cat;
  }
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_match_calevent.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfc_league_fe/models/class.tx_cfcleaguefe_models_match_calevent.php']);
}
?>