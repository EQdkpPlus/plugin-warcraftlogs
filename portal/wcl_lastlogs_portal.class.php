<?php
/*	Project:	EQdkp-Plus
 *	Package:	Warcraftlogs.com Plugin
 *	Link:		http://eqdkp-plus.eu
 *
 *	Copyright (C) 2006-2015 EQdkp-Plus Developer Team
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

if (!defined('EQDKP_INC')){
	header('HTTP/1.0 404 Not Found'); exit;
}

/*+----------------------------------------------------------------------------
 | wcl_lastlogs_portal
 +--------------------------------------------------------------------------*/
class wcl_lastlogs_portal extends portal_generic{
	
	/**
	 * Portal path
	 */
	protected static $path = 'wcl_lastlogs_portal';
	/**
	 * Portal data
	 */
	protected static $data = array(
			'name'			=> 'Warcraftlogs.com Last Logs',
			'version'		=> '0.1.0',
			'author'		=> 'GodMod',
			'contact'		=> 'https://eqdkp-plus.eu',
			'description'	=> 'Displays latest Logs from the Warcraftlogs.com',
			'lang_prefix'	=> 'wcl_',
			'multiple'		=> true,
	);
	
	protected static $apiLevel = 20;
	
	protected static $multiple = true;
	
	public function get_settings($state){		
		$settings = array(
				'output_count_limit'	=> array(
						'type'		=> 'spinner',
						'default'	=> '5',
				),
		);
		
		return $settings;
	}
	
	/**
	 * output
	 * Get the portal output
	 *
	 * @returns string
	 */
	public function output(){
		$arrReports = $this->pdc->get('plugins.warcraftlogs.reports');
		
		include_once($this->root_path.'plugins/warcraftlogs/includes/warcraftlogs_helper.class.php');
		$objHelper = register('warcraftlogs_helper');
		if($arrReports === null){
			$strGuildname = unsanitize($this->config->get('guildtag'));
			$strServername = unsanitize($this->config->get('servername'));
			$strServerregion = $this->config->get('uc_server_loc');
			$strAPIKey = $this->config->get('api_key', 'warcraftlogs');
			
			$strServername = utf8_strtolower($strServername);
			$strServername = str_replace(' ', '-', $strServername);
			$strServername = str_replace("'", '', $strServername);
			$strServername = $objHelper->remove_accents($strServername);
		
			$strReportsURL = $objHelper->get_warcraftlogsurl()."/v1/reports/guild/".rawurlencode($strGuildname)."/".$strServername."/".$strServerregion."?api_key=".$strAPIKey;
			
			$strData = register('urlfetcher')->fetch($strReportsURL);
			if($strData){
				$arrReports = json_decode($strData, true);
				
				$this->pdc->put('plugins.warcraftlogs.reports', $arrReports, 600);
			}
			
		}
		
		//Latest ontop
		//$arrReports = array_reverse($arrReports);
		
		$intMax = ($this->config('output_count_limit')) ? (int)$this->config('output_count_limit') : 5;
		
		if(is_array($arrReports)){
			$i = 0;
			
			$strOut = '<div class="table" style="width:100%">';
			foreach($arrReports as $arrReport){
				if($i == $intMax) break;
				
				$strOut .= '<div class="tr">';
				
				$strOut .= '<div class="td" style="width: 28px;"><a href="'.$objHelper->get_warcraftlogsurl()."/reports/".sanitize($arrReport['id']).'" target="_blank"><div class="user-avatar-small user-avatar-border">';
				$strOut .='<img src="https://assets.rpglogs.com/img/warcraft/zones/zone-'.sanitize($arrReport['zone']).'.png" class="user-avatar small" loading="lazy"/>';
				$strOut .= '</div></a>';
				$strOut .= '</div>';
								
				$date = $this->time->nice_date(round($arrReport['start']/1000, 0), 60*60*2*3, true);
				$strOut .= '<div class="td"><a href="'.$objHelper->get_warcraftlogsurl()."/reports/".sanitize($arrReport['id']).'" target="_blank">'.sanitize($arrReport['title'])."</a>
<div class=\"small\">".$date."</div>
</div>";
				
				$strOut .= '</div>';
				$i++;
			}
			$strOut .= "</div>";
			return $strOut;
			
		}
		
		$strOut = "Could not fetch data.";
		
		return $strOut;
	}
}

?>
