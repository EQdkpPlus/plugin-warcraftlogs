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

if (!defined('EQDKP_INC')){
	header('HTTP/1.0 404 Not Found');exit;
}

class warcraftlogs extends plugin_generic {
	public $vstatus		= 'Stable';
	public $version		= '0.2.4';
	public $copyright 	= 'GodMod';

	protected static $apiLevel = 23;

	public function __construct(){
		parent::__construct();

		$this->add_dependency(array(
				'plus_version' => '2.3',
				'games'	=> array('wow', 'wowclassic')
		));
		
		$this->add_data(array (
			'name'				=> 'Warcraftlogs',
			'code'				=> 'warcraftlogs',
			'path'				=> 'warcraftlogs',
			'template_path'		=> 'plugins/warcraftlogs/templates/',
			'icon'				=> 'fa-line-chart',
			'version'			=> $this->version,
			'author'			=> $this->copyright,
			'description'		=> $this->user->lang('warcraftlogs_short_desc'),
			'long_description'	=> $this->user->lang('warcraftlogs_long_desc'),
			'homepage'			=> EQDKP_PROJECT_URL,
			'manuallink'		=> false,
			'plus_version'		=> '2.3'
		));

		// -- Register our permissions ------------------------
		// permissions: 'a'=admins, 'u'=user
		// ('a'/'u', Permission-Name, Enable? 'Y'/'N', Language string, array of user-group-ids that should have this permission)
		// Groups: 1 = Guests, 2 = Super-Admin, 3 = Admin, 4 = Member
		$this->add_permission('u', 'view',		'Y', $this->user->lang('view'),					array(2,3,4));
		$this->add_permission('a', 'settings',	'N', $this->user->lang('settings'),				array(2,3));

		// -- Hooks -------------------------------------------
		$this->add_hook('viewraid',	'warcraftlogs_viewraid_hook',	'viewraid');

		// -- Routing -------------------------------------------
		$this->routing->addRoute('warcraftlogs', 'warcraftlogs', 'plugins/warcraftlogs/page_objects');

		// -- Menu --------------------------------------------
		$this->add_menu('admin', $this->gen_admin_menu());
		$this->add_menu('main', $this->gen_main_menu());
		
		$this->add_portal_module('wcl_lastlogs');
	}

	/**
	* Define Installation
	*/
	public function pre_install(){

			// set the default config
		if (is_array($config_vars)){
			$this->config->set($this->default_config(), '', 'warcraftlogs');
		}

		// load user locations
		$this->pdh->process_hook_queue();
	}

	/**
	* Define the default config
	*/
	private function default_config(){
		return array();
	}

	/**
	* Define uninstallation
	*/
	public function pre_uninstall(){
		
	}

	/**
	* Generate the Admin Menu
	*/
	private function gen_admin_menu(){
		$admin_menu = array (array(
			'name' => $this->user->lang('warcraftlogs'),
			'icon' => 'fa-line-chart',
			1 => array (
				'link'	=> 'plugins/warcraftlogs/admin/manage_settings.php'.$this->SID,
				'text'	=> $this->user->lang('settings'),
				'check'	=> 'a_warcraftlogs_settings',
				'icon'	=> 'fa-wrench'
			)
		));
		return $admin_menu;
	}

	/**
	* gen_admin_menu
	* Generate the Admin Menu
	*/
	private function gen_main_menu(){
		$main_menu = array();
		return $main_menu;
	}
}
?>
