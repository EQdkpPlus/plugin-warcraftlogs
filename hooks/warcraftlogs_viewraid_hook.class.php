<?php
/*	Project:	EQdkp-Plus
 *	Package:	Warcraftlogs.com Plugin
 *	Link:		http://eqdkp-plus.eu
 *
 *	Copyright (C) 2006-2017 EQdkp-Plus Developer Team
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Affero General Public License as published
 *	by the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Affero General Public License for more details.
 *
 *	You should have received a copy of the GNU Affero General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('EQDKP_INC'))
{
  header('HTTP/1.0 404 Not Found');exit;
}


/*+----------------------------------------------------------------------------
  | warcraftlogs_viewraid_hook
  +--------------------------------------------------------------------------*/
if (!class_exists('warcraftlogs_viewraid_hook')){
	class warcraftlogs_viewraid_hook extends gen_class{
		/* List of dependencies */

		/**
		* usersettings_update
		* Do the hook 'usersettings_update'
		*
		* @return array
		*/
		public function viewraid($data){
			$raid_id = $data['raid_id'];
			
			$strEventname = $this->pdh->get('raid', 'event_name', array($raid_id));
			$strEventTime = $this->pdh->get('raid', 'date', array($raid_id));
			
			$strGuildname = unsanitize($this->config->get('guildtag'));
			$strServername = unsanitize($this->config->get('servername'));
			
			$strServername = utf8_strtolower($strServername);
			$strServername = str_replace(' ', '-', $strServername);
			$strServername = str_replace("'", '', $strServername);
			include_once($this->root_path.'plugins/warcraftlogs/includes/warcraftlogs_helper.class.php');
			$objHelper = register('warcraftlogs_helper');
			$strServername = $objHelper->remove_accents($strServername);
			
			$strServerregion = $this->config->get('uc_server_loc');
			
			$date = date("Y-m-d", $strEventTime);			
			$strEventDay = strtotime($date);
			//Fetch Data from warcraftlogs.com
			
			$strAPIKey = $this->config->get('api_key', 'warcraftlogs');
			
			$strCachekey = md5($strEventDay*1000);
			
			$arrEventnames = preg_split('/(\s|&|\+)/', $strEventname);
			foreach($arrEventnames as $key => $val){
				$strNormalEventname = utf8_strtolower($val);
				// Removes special chars.
				$strNormalEventname = preg_replace('/[^A-Za-z0-9\-]/', '', $strNormalEventname);
				$arrEventnames[$key] = $strNormalEventname;
			}
			 

			$arrData = $this->pdc->get('plugins.warcraftlogs.reports.'.$strCachekey);
			if($arrData === null){
				$strReportsURL = $objHelper->get_warcraftlogsurl()."/v1/reports/guild/".rawurlencode($strGuildname)."/".$strServername."/".$strServerregion."?start=".($strEventDay*1000)."&end=".(($strEventDay+24*3600)*1000)."&api_key=".$strAPIKey;
				$strData = register('urlfetcher')->fetch($strReportsURL);
				if($strData){
					$arrData = json_decode($strData, true);
					
					$this->pdc->put('plugins.warcraftlogs.reports.'.$strCachekey, $arrData, 600);
				}
			}
			
			$strOutputReportLinks = "";
			$arrGlobalFights = false;
			
			if(is_array($arrData)){
				//try to find a match based on event name
				$arrMatches = array();
				
				foreach($arrData as $arrLogInfos){
					$strID = $arrLogInfos['id'];
					
					$strNormalTitle = utf8_strtolower($arrLogInfos['title']);
					$strNormalTitle = preg_replace('/[^A-Za-z0-9\-]/', '', $strNormalTitle);
					
					foreach($arrEventnames as $key => $val){
						if(strpos($strNormalTitle, $val) !== false){
							$arrMatches[] = $arrLogInfos;
						}
					}
				}

				if(count($arrMatches) === 0) $arrMatches = $arrData;
						
				foreach($arrMatches as $arrLogInfos){
					$strID = $arrLogInfos['id'];
										
					$strOutputReportLinks .= '<a href="'.$objHelper->get_warcraftlogsurl().'/reports/'.$strID.'"><i class="fa fa-external-link fa-lg"></i> warcraftlogs.com</a><br/>';
					
					//Fetch Fights
					$strFightData = $this->pdc->get('plugins.warcraftlogs.fights.'.$strID);
					if($strFightData === null){
						$strFightData = register('urlfetcher')->fetch($objHelper->get_warcraftlogsurl()."/v1/report/fights/".$strID."?api_key=".$strAPIKey);
						if($strFightData){
							$arrFightData = json_decode($strFightData, true);
							
							$arrFights = $arrFightData['fights'];
							$arrGlobalFights = array();
							
							foreach($arrFights as $arrMyFightData){
								$duration = $arrMyFightData['end_time'] - $arrMyFightData['start_time'];
								$duration = gmdate("H:i:s", ($duration /1000));
								
								
								$arrGlobalFights[] = array (
										'boss' => (int)$arrMyFightData['boss'],
										'name' => $arrMyFightData['name'],
										'kill' => (strlen($arrMyFightData['kill'])),
										'id'	=> $strID,
										'fightid' => (int)$arrMyFightData['id'],
										'info' => round($arrMyFightData['bossPercentage']/100, 0).'% P'.$arrMyFightData['lastPhaseForPercentageDisplay'].', '.$duration,
										'duration' => $duration,
								);
							}
						}
						
						$this->pdc->put('plugins.warcraftlogs.fights.'.$strID, $arrGlobalFights, 3600);
					} else {
						$arrGlobalFights = $strFightData;
					}
				}	
			}
				
			
			if(strlen($strOutputReportLinks)){
					$this->tpl->add_listener('viewraid_fieldset', '<dl>
				<dt><label>'.$this->user->lang('wcl_log').'</label></dt>
				<dd>'.$strOutputReportLinks.'
				</dd>
			</dl>');				
			}
			
			
			if($arrGlobalFights && is_array($arrGlobalFights)){
				
				$strBosses = "";
				$i = 0;
				$arrTries = array();
				foreach($arrGlobalFights as $key => $arrFightData){
					if($arrFightData['boss'] == 0) continue;
					$i++;
					$arrTries[$arrFightData['boss']]++;
					
					$strBosses .= '<div style="margin-bottom:5px;" class="col3"><a href="'.$objHelper->get_warcraftlogsurl().'/reports/'.sanitize($arrFightData['id']).'#fight='.sanitize($arrFightData['fightid']).'"><img src="https://dmszsuqyoe6y6.cloudfront.net/img/warcraft/bosses/'.sanitize($arrFightData['boss']).'-icon.jpg" style="max-height:22px;" loading="lazy"/> '.sanitize($arrFightData['name']).' ('.$this->user->lang('wcl_tries').' '.$arrTries[$arrFightData['boss']].') '.(($arrFightData['kill']) ? "<i class='fa fa-check icon-color-green'></i>" : "<i class='fa fa-times icon-color-red'></i>").'</a> '.(($arrFightData['kill']) ? $arrFightData['duration'] : $arrFightData['info']).'</div>';
				}
				
				$this->tpl->add_listener('viewraid_beforetables', '<div class="tableHeader warcraftlogs">
	<h2>'.$this->user->lang('wcl_fights').' <span class="bubble">'.count($arrGlobalFights).'</span></h2>
	<div class="grid">
	'.$strBosses.'
</div></div>');
				
			}
		}
		
	}
}
?>
