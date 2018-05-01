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

// EQdkp required files/vars
define('EQDKP_INC', true);
define('IN_ADMIN', true);
define('PLUGIN', 'warcraftlogs');

$eqdkp_root_path = './../../../';
include_once($eqdkp_root_path.'common.php');

class warcraftlogsSettings extends page_generic {

	/**
	* Constructor
	*/
	public function __construct(){
		// plugin installed?
		if (!$this->pm->check('warcraftlogs', PLUGIN_INSTALLED))
			message_die($this->user->lang('warcraftlogs_not_installed'));

		$handler = array(
			'save' => array('process' => 'save', 'csrf' => true, 'check' => 'a_warcraftlogs_settings'),
		);
		parent::__construct('a_warcraftlogs_settings', $handler);

		$this->process();
	}

	private $arrData = false;

	public function save(){
		$objForm				= register('form', array('um_settings'));
		$objForm->langPrefix	= 'um_';
		$objForm->validate		= true;
		$objForm->add_fieldsets($this->fields());
		$arrValues				= $objForm->return_values();

		if($objForm->error){
			$this->arrData		= $arrValues;
		}else{
			// update configuration
			$this->config->set($arrValues, '', 'warcraftlogs');
			// Success message
			$messages[]			= $this->user->lang('um_saved');
			$this->display($messages);
		}
	}

	private function fields(){
		$arrFields = array(
			'general' => array(
				'api_key' => array(
					'type'		=> 'text',
					'value'		=> $this->config->get('api_key',	'warcraftlogs'),
					'required' => true,
				),
			),
		);
		return $arrFields;
	}

	public function display($messages=array()){
		// -- Messages ------------------------------------------------------------
		if ($messages){
			foreach($messages as $name)
				$this->core->message($name, $this->user->lang('warcraftlogs'), 'green');
		}

		// get the saved data
		$arrValues		= $this->config->get_config('warcraftlogs');
		if ($this->arrData !== false) $arrValues = $this->arrData;

		// -- Template ------------------------------------------------------------
		// initialize form class
		$objForm				= register('form', array('um_settings'));
		$objForm->reset_fields();
		$objForm->lang_prefix	= 'wcl_';
		$objForm->validate		= true;
		$objForm->use_fieldsets	= true;
		$objForm->add_fieldsets($this->fields());

		// Output the form, pass values in
		$objForm->output($arrValues);

		$this->core->set_vars(array(
			'page_title'	=> $this->user->lang('warcraftlogs').' '.$this->user->lang('settings'),
			'template_path'	=> $this->pm->get_data('warcraftlogs', 'template_path'),
			'template_file'	=> 'admin/manage_settings.html',
			'page_path'			=> [
					['title'=>$this->user->lang('menu_admin_panel'), 'url'=>$this->root_path.'admin/'.$this->SID],
					['title'=>$this->user->lang('wcl_breadcrumb_settings'), 'url'=>' '],
			],
			'display'		=> true
	  ));
	}

}
registry::register('warcraftlogsSettings');
?>