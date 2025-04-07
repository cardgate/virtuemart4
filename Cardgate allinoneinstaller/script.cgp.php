<?php
/**
 * CardGate script file
 *
 * This file is executed during install/upgrade and uninstall
 *
 * @author Richard Schoots
 * @package CardGate
 */

defined ('_JEXEC') or die('Restricted access');

defined ('DS') or define('DS', DIRECTORY_SEPARATOR);

$max_execution_time = ini_get('max_execution_time');
if((int)$max_execution_time<120) {
	@ini_set( 'max_execution_time', '120' );
}
$memory_limit = (int) substr(ini_get('memory_limit'),0,-1);
if($memory_limit<128)  @ini_set( 'memory_limit', '128M' );

if (!class_exists( 'VmConfig' )) {
    $path = JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php';
    if(file_exists($path)){
        require($path);
    } else {
        $app = JFactory::getApplication();
        $app->enqueueMessage('VirtueMart Core is not installed, please install VirtueMart again, or uninstall the AIO component by the joomla extension manager');
        return false;
    }
}

VmConfig::loadConfig();
if(!class_exists('vmText')) require(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'vmtext.php');

// hack to prevent defining these twice in 1.6 installation
if (!defined ('_CGP_SCRIPT_INCLUDED')) {
//    include(VMPATH_ADMIN.'/components/com_virtuemart/helpers/vrequest.php');
	define('_CGP_SCRIPT_INCLUDED', TRUE);
	
	/**
	 * CardGate custom installer class
	 */
	class com_cgpInstallerScript {

		public function preflight () {
                    //We want disable the redirect in the installation process
			if(version_compare(JVERSION,'1.6.0','ge') and version_compare(JVERSION,'3.0.0','le')) {

				$this->_db = JFactory::getDbo();
				$q = 'SELECT extension_id FROM #__extensions WHERE `type` = "component" AND `element` = "com_cgp" ';
				$this->_db ->setQuery($q);
				$extensionId = $this->_db->loadResult();
				if($extensionId){
					$q = 'DELETE FROM `#__menu` WHERE `component_id` = "'.$extensionId.'" AND `client_id`="1" ';
					$this->_db -> setQuery($q);
					$this->_db -> execute();
				}
				/*else {
					$q = 'DELETE FROM `#__menu` WHERE `menutype` = "main" AND `type` = "component" AND `client_id`="1"
						AND `link`="%option=com_cgp%" )';
				}*/

			}
		}

		public function install () {
		//	$this->cgpInstall();
		}

		public function discover_install () {
		//	$this->cgpInstall();
		}

		public function postflight () {

			$this->cgpInstall();
		}

		public function cgpInstall () {
				
			jimport ('joomla.filesystem.file');
			jimport ('joomla.installer.installer');
			
			$this->path = JInstaller::getInstance ()->getPath ('extension_administrator');

			$dst = JPATH_ROOT . DS . 'components' . DS . 'com_cgp';

			$src = $this->path . DS . 'components' . DS . 'com_cgp';

			$this->recurse_copy ($src, $dst);
			
			//copy cgpgeneric

			$src = $this->path . DS . 'plugins' . DS . 'vmpayment' . DS . 'cgpgeneric';

			$dst = JPATH_ROOT . DS . 'plugins' . DS . 'vmpayment' . DS . 'cgpgeneric';

			$this->recurse_copy ($src, $dst, 'plugins');
			
			
			$this->installPlugin ('CardGatePlus Credit Card', 'plugin', 'cgpcreditcard', 'vmpayment');
			$this->installPlugin ('CardGatePlus iDEAL', 'plugin', 'cgpideal', 'vmpayment');
			$this->installPlugin ('CardGatePlus iDEAL QR', 'plugin', 'cgpidealqr', 'vmpayment');
			$this->installPlugin ('CardGatePlus Mister Cash', 'plugin', 'cgpmistercash', 'vmpayment');
			$this->installPlugin ('CardGatePlus OnlineÜberweisen', 'plugin', 'cgponlineueberweisen', 'vmpayment');
			$this->installPlugin ('CardGatePlus PayPal', 'plugin', 'cgppaypal', 'vmpayment');
			$this->installPlugin ('CardGatePlus Paysafecard', 'plugin', 'cgppaysafecard', 'vmpayment');
			$this->installPlugin ('CardGatePlus Paysafecash', 'plugin', 'cgppaysafecash', 'vmpayment');
			$this->installPlugin ('CardGatePlus SofortBanking', 'plugin', 'cgpsofortbanking', 'vmpayment');
			
			$this->installPlugin ('CardGatePlus Banktransfer', 'plugin', 'cgpbanktransfer', 'vmpayment');
			$this->installPlugin ('CardGatePlus Gift Card', 'plugin', 'cgpgiftcard', 'vmpayment');
			$this->installPlugin ('CardGatePlus Direct Debit', 'plugin', 'cgpdirectdebit', 'vmpayment');
			$this->installPlugin ('CardGatePlus Przelewy24', 'plugin', 'cgpprzelewy24', 'vmpayment');
			$this->installPlugin ('CardGatePlus Afterpay', 'plugin', 'cgpafterpay', 'vmpayment');
			$this->installPlugin ('CardGatePlus Klarna', 'plugin', 'cgpklarna', 'vmpayment');
			$this->installPlugin ('CardGatePlus Bitcoin', 'plugin', 'cgpbitcoin', 'vmpayment');
			$this->installPlugin ('CardGatePlus Billink', 'plugin', 'cgpbillink', 'vmpayment');
			$this->installPlugin ('CardGatePlus SprayPay', 'plugin', 'cgpspraypay', 'vmpayment');

            $task = vRequest::getCmd ('task');
			
			if ($task != 'updateDatabase') {

				// images auto move
				$src = $this->path . DS . "images";
				$dst = JPATH_ROOT . DS . "images";
				$this->recurse_copy ($src, $dst);
				echo "Card Gate images moved to the joomla images FE folder<br/ >";

				// language auto move
				$src = $this->path . DS . "languageBE";
				$dst = JPATH_ADMINISTRATOR . DS . "language";
				$this->recurse_copy ($src, $dst);
				echo " Card Gate language moved to the joomla language BE folder<br/ >";
			
				echo "<H3>Installing CardGate Plugins and components Success.</h3>";
			} else {
				echo "<H3>Updated CardGatePlus Plugin tables</h3>";
			}
			return true;

		}

		/**
		 * Installs a CardGatePlus plugin into the database
		 *
		 */
		private function installPlugin ($name, $type, $element, $group) {
            $task = vRequest::getCmd ('task');

			if ($task != 'updateDatabase') {
				$data = array();

                $table = JTable::getInstance ('extension');
                $data['enabled'] = 1;
                $data['access'] = 1;
                $tableName = '#__extensions';
                $idfield = 'extension_id';

                $data['params'] = '';
                $data['custom_data'] = '';
                $data['manifest_cache'] = '';

				$data['name'] = $name;
				$data['type'] = $type;
				$data['element'] = $element;
				$data['folder'] = $group;
				$data['client_id'] = 0;
                $data['package_id'] = 0;
                $data['locked'] = 0;

                $src = $this->path . DS . 'plugins' . DS . $group . DS . $element;

				$db = JFactory::getDBO ();
				$q = 'SELECT COUNT(*) FROM `' . $tableName . '` WHERE `element` = "' . $element . '" and folder = "' . $group . '" ';
				$db->setQuery ($q);
				$count = $db->loadResult ();

				//We write only in the table, when it is not installed already
				if ($count == 0) {
					// 				$table->load($count);
					if (version_compare (JVERSION, '4.0.0', 'ge')) {
                        $data['manifest_cache'] = json_encode(JInstaller::parseXMLInstallFile($src . '/' . $element . '.xml'));
					}

					if (!$table->bind ($data)) {
						$app = JFactory::getApplication ();
						$app->enqueueMessage ('CGPInstaller table->bind throws error for ' . $name . ' ' . $type . ' ' . $element . ' ' . $group);
					}

					if (!$table->check ($data)) {
						$app = JFactory::getApplication ();
						$app->enqueueMessage ('CGPInstaller table->check throws error for ' . $name . ' ' . $type . ' ' . $element . ' ' . $group);

					}

					if (!$table->store ($data)) {
						$app = JFactory::getApplication ();
						$app->enqueueMessage ('CGPInstaller table->store throws error for ' . $name . ' ' . $type . ' ' . $element . ' ' . $group);
					}

					$errors = $table->getErrors ();
					foreach ($errors as $error) {
						$app = JFactory::getApplication ();
						$app->enqueueMessage (get_class ($this) . '::store ' . $error);
					}
					// remove duplicated
				} elseif ($count == 2) {
					$q = 'SELECT ' . $idfield . ' FROM `' . $tableName . '` WHERE `element` = "' . $element . '" ORDER BY  `' . $idfield . '` DESC  LIMIT 0,1';
					$db->setQuery ($q);
					$duplicatedPlugin = $db->loadResult ();
					$q = 'DELETE FROM `' . $tableName . '` WHERE ' . $idfield . ' = ' . $duplicatedPlugin;
					$db->setQuery ($q);
					$db->query ();
				}
			}

			if (version_compare (JVERSION, '1.7.0', 'ge')) {
				// Joomla! 1.7 code here
				$dst = JPATH_ROOT . DS . 'plugins' . DS . $group . DS . $element;

			} elseif (version_compare (JVERSION, '1.6.0', 'ge')) {
				// Joomla! 1.6 code here
				$dst = JPATH_ROOT . DS . 'plugins' . DS . $group . DS . $element;
			} else {
				// Joomla! 1.5 code here
				$dst = JPATH_ROOT . DS . 'plugins' . DS . $group;
			}

			if ($task != 'updateDatabase') {
				$this->recurse_copy ($src, $dst);
			}
			$this->updatePluginTable ($name, $type, $element, $group, $dst);
		}


		public function updatePluginTable ($name, $type, $element, $group, $dst) {

			$app = JFactory::getApplication ();

			//Update Tables
			if (!class_exists ('VmConfig')) {
				require(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart' . DS . 'helpers' . DS . 'config.php');
			}
		
			if (class_exists ('VmConfig')) {
				$pluginfilename = $dst . DS . $element . '.php';
                if(file_exists($pluginfilename)){
                    if(!class_exists('plg'.ucfirst($group).ucfirst($element))){
                        require_once ($pluginfilename);	//require_once cause is more failproof and is just for install
                    }
                } else {
                    $app = JFactory::getApplication ();
                    $app->enqueueMessage (get_class ($this) . ':: VirtueMart3 could not find file '.$pluginfilename);
                    return false;
                }

                try {
                    $plugin = vDispatcher::createPlugin($group, $element, false); //new $pluginClassname($dispatcher, $config);
                }
                catch (Exception $e) {
                    //errros in sql during plugin instalalation and updates
                    $app->enqueueMessage (get_class ($this) . ':: vDispatcher::createPlugin '.$pluginfilename.' '. $e->getMessage());
                }

				$_psType = substr ($group, 2);
				$tablename = '#__virtuemart_' . $_psType . '_plg_' . $element;
				$db = JFactory::getDBO ();
				$prefix=$db->getPrefix();
				$query = 'SHOW TABLES LIKE "' . str_replace ('#__', $prefix, $tablename) . '"';
				$db->setQuery ($query);
				$result = $db->loadResult ();

				if ($result) {
					$SQLfields = $plugin->getTableSQLFields ();
					$loggablefields = $plugin->getTableSQLLoggablefields ();
					$tablesFields = array_merge ($SQLfields, $loggablefields);
					$update[$tablename] = array($tablesFields, array(), array());
					
					vmdebug('install plugin',$update );
					
					$app->enqueueMessage (get_class ($this) . ':: CardGate update ' . $tablename);
					$updater = new GenericTableUpdater();

					$updater->updateMyVmTables ($update);
				}

			} else {
				$app = JFactory::getApplication ();
				$app->enqueueMessage (get_class ($this) . ':: VirtueMart must be installed, or the tables cant be updated ' . $error);

			}

		}

		public function installModule ($title, $module, $ordering, $params) {

			$params = '';

			$table = JTable::getInstance ('module');

			$db = $table->getDBO ();
			$q = 'SELECT id FROM `#__modules` WHERE `module` = "' . $module . '" ';
			$db->setQuery ($q);
			$id = $db->loadResult ();
			$src = JPATH_ROOT . DS . 'modules' . DS . $module;

			if (!empty($id)) {
				return;
			}
			$table->load ();
			if (version_compare (JVERSION, '1.7.0', 'ge')) {
				// Joomla! 1.7 code here
				$position = 'position-4';
				$access = 1;
			} else {
				if (version_compare (JVERSION, '1.6.0', 'ge')) {
					// Joomla! 1.6 code here
					$access = 1;
				} else {
					// Joomla! 1.5 code here
					$position = 'left';
					$access = 0;
				}
			}

			if (empty($table->title)) {
				$table->title = $title;
			}
			if (empty($table->ordering)) {
				$table->ordering = $ordering;
			}
			if (empty($table->published)) {
				$table->published = 1;
			}
			if (empty($table->module)) {
				$table->module = $module;
			}
			if (empty($table->params)) {
				$table->params = $params;
			}
			if (empty($table->access)) {
				$table->access = $access;
			}
			if (empty($table->position)) {
				$table->position = $position;
			}
			if (empty($table->client_id)) {
				$table->client_id = $client_id = 0;
			}

			if (!$table->check ()) {
				$app = JFactory::getApplication ();
				$app->enqueueMessage ('VMInstaller table->check throws error for ' . $title . ' ' . $module . ' ' . $params);
			}

			if (!$table->store ()) {
				$app = JFactory::getApplication ();
				$app->enqueueMessage ('VMInstaller table->store throws error for for ' . $title . ' ' . $module . ' ' . $params);
			}

			$errors = $table->getErrors ();
			foreach ($errors as $error) {
				$app = JFactory::getApplication ();
				$app->enqueueMessage (get_class ($this) . '::store ' . $error);
			}
			// 			}

			$lastUsedId = $table->id;

			$q = 'SELECT moduleid FROM `#__modules_menu` WHERE `moduleid` = "' . $lastUsedId . '" ';
			$db->setQuery ($q);
			$moduleid = $db->loadResult ();

			$action = '';
			if (empty($moduleid)) {
				$q = 'INSERT INTO `#__modules_menu` (`moduleid`, `menuid`) VALUES( "' . $lastUsedId . '" , "0");';
			} else {
				//$q = 'UPDATE `#__modules_menu` SET `menuid`= "0" WHERE `moduleid`= "'.$moduleid.'" ';
			}
			$db->setQuery ($q);
			$db->query ();

			if (version_compare (JVERSION, '1.6.0', 'ge')) {

				$q = 'SELECT extension_id FROM `#__extensions` WHERE `element` = "' . $module . '" ';
				$db->setQuery ($q);
				$ext_id = $db->loadResult ();

				//				$manifestCache = str_replace('"', '\'', $data["manifest_cache"]);
				$action = '';
				if (empty($ext_id)) {
					if (version_compare (JVERSION, '1.6.0', 'ge')) {
						$manifest_cache = json_encode (JApplicationHelper::parseXMLInstallFile ($src . DS . $module . '.xml'));
					}
					$q = 'INSERT INTO `#__extensions` 	(`name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `ordering`) VALUES
																	( "' . $module . '" , "module", "' . $module . '", "", "0", "1","' . $access . '", "0", "' . $db->getEscaped ($manifest_cache) . '", "' . $params . '","' . $ordering . '");';
				} else {
			}
				$db->setQuery ($q);
				if (!$db->query ()) {
					$app = JFactory::getApplication ();
					$app->enqueueMessage (get_class ($this) . '::  ' . $db->getErrorMsg ());
				}

			}
		}

		/**
		 * @author Max Milbers
		 * @param string $tablename
		 * @param string $fields
		 * @param string $command
		 */
		private function alterTable ($tablename, $fields, $command = 'CHANGE') {

			if (empty($this->db)) {
				$this->db = JFactory::getDBO ();
			}

			$query = 'SHOW COLUMNS FROM `' . $tablename . '` ';
			$this->db->setQuery ($query);
			$columns = $this->db->loadResultArray (0);

			foreach ($fields as $fieldname => $alterCommand) {
				if (in_array ($fieldname, $columns)) {
					$query = 'ALTER TABLE `' . $tablename . '` ' . $command . ' COLUMN `' . $fieldname . '` ' . $alterCommand;

					$this->db->setQuery ($query);
					$this->db->query ();
				}
			}

		}

		/**
		 *
		 * @author Max Milbers
		 * @param string $table
		 * @param string $field
		 * @param string $fieldType
		 * @return boolean This gives true back, WHEN it altered the table, you may use this information to decide for extra post actions
		 */
		private function checkAddFieldToTable ($table, $field, $fieldType) {

			$query = 'SHOW COLUMNS FROM `' . $table . '` ';
			$this->db->setQuery ($query);
			$columns = $this->db->loadResultArray (0);

			if (!in_array ($field, $columns)) {

				$query = 'ALTER TABLE `' . $table . '` ADD ' . $field . ' ' . $fieldType;
				$this->db->setQuery ($query);
				if (!$this->db->query ()) {
					$app = JFactory::getApplication ();
					$app->enqueueMessage ('Install checkAddFieldToTable ' . $this->db->getErrorMsg ());
					return FALSE;
				} else {
					return TRUE;
				}
			}
			return FALSE;
		}

		/**
		 * copy all $src to $dst folder and remove it
		 *
		 * @author Max Milbers
		 * @param String $src path
		 * @param String $dst path
		 * @param String $type modules, plugins, languageBE, languageFE
		 */
		private function recurse_copy ($src, $dst) {

			$dir = opendir ($src);
			$this->createIndexFolder ($dst);

			if (is_resource ($dir)) {
				while (FALSE !== ($file = readdir ($dir))) {
					if (($file != '.') && ($file != '..')) {
						if (is_dir ($src . DS . $file)) {
							$this->recurse_copy ($src . DS . $file, $dst . DS . $file);
						} else {
							if (JFile::exists ($dst . DS . $file)) {
								if (!JFile::delete ($dst . DS . $file)) {
									$app = JFactory::getApplication ();
									$app->enqueueMessage ('Couldnt delete ' . $dst . DS . $file);
								}
							}
							if (!JFile::move ($src . DS . $file, $dst . DS . $file)) {
								$app = JFactory::getApplication ();
								$app->enqueueMessage ('Couldnt move ' . $src . DS . $file . ' to ' . $dst . DS . $file);
							}
						}
					}
				}
				closedir ($dir);
				if (is_dir ($src)) {
					JFolder::delete ($src);
				}
			} else {
				$app = JFactory::getApplication ();
				$app->enqueueMessage ('Couldnt read dir ' . $dir . ' source ' . $src);
			}

		}


		public function uninstall () {

			return TRUE;
		}

		/**
		 * creates a folder with empty html file
		 *
		 * @author Max Milbers
		 *
		 */
		public function createIndexFolder ($path) {

			if (JFolder::create ($path)) {
				if (!JFile::exists ($path . DS . 'index.html')) {
					JFile::copy (JPATH_ROOT . DS . 'components' . DS . 'index.html', $path . DS . 'index.html');
				}
				return TRUE;
			}
			return FALSE;
		}

	}


	// PLZ look in #cgpinstall.php# to add your plugin and module
	function com_install () {
		$cgpInstall = new com_cgpInstallerScript();
		$cgpInstall->cgpInstall ();
		return TRUE;
	}

	function com_uninstall () {

		return TRUE;
	}

} //if defined
// pure php no tag
