<?php
/**
 * This file contains the common_site_customization class. This class adds and updates organization customization.
 *
 * @package      cms
 * @subpackage   database
 */

/**
 * @ignore
 */
if(false) {
	require_once('../../common/functions/html_entity_conversion.php');
	require_once('../../common/class/crypt_array.php');
	require_once('../../common/functions/debug.php');
	require_once('../../common/functions/session.php');
} else {
	if(!isset($GLOBALS['wwwBaseDir'])) require_once('../../common/other_includes/set_base_dir.php');
	require_once($GLOBALS['wwwBaseDir'] . 'common/functions/html_entity_conversion.php');
	require_once($GLOBALS['wwwBaseDir'] . 'common/class/crypt_array.php');
	require_once($GLOBALS['wwwBaseDir'] . 'common/functions/debug.php');
	require_once($GLOBALS['wwwBaseDir'] . 'common/functions/session.php');
}

/**
 * This class is used to add and update organization customization.
 *
 * @author       Lindsay Sauer <lindsay_sauer@amatrol.com>
 * @package      common
 * @subpackage   database
 * @version      1.0
 */
class Common_Site_Customization extends Common_Object {
	
	/**
	 * @var boolean True if error messages should contain Amatrol administrator level detail.
	 * @access private
	 */
	protected $_adminLevelError;
	
	/**
	 * @var int The organization id.
	 * @access private
	 */
	private $_orgId;
	
	/**
	 * @var int The site id.
	 * @access private
	 */
	private $_siteId;
	
	/**
	 * @var object The common_organization_customization table
	 * @access private
	 */
	private $_table;
	
	/**
	 * @var object The query object to use for searching
	 * @access private
	 */
	private $_query;
	
	
	/**
	 * @var string The encoding to use for storing data
	 * @access private
	 */
	private $_encoding = 'UTF-8';
	
	/**
	 * @param int $orgId Must be an org id that exists. If empty, gets the current session org id
	 * @param int $siteId Must be a site id that exists. If empty, gets the current session site id
	 * @param boolean $adminLevelError Error string should contain admin level detail.
	 */
	public function __construct($orgId = '', $siteId = '', $adminLevelError = false) {		
		if ($orgId == '') $orgId = $_SESSION['org']['org_id'];
		$cleanOrgId = (int)($orgId);
		if ($siteId == '') $siteId = $_SESSION['site']['site_id'];
		$cleanSiteId = (int)($siteId);
		
		$qstr = "Select org_id From common_organization Where org_id = $cleanOrgId";		
		$orgNameQuery = new Common_Query($qstr, $this->_adminLevelError);
		$rows = $orgNameQuery->getResults();
		if(count($rows) < 1) {
			$errorMessage = "Invalid org id.";
			if($this->_adminLevelError) {
				$errorMessage .= " --- orgId: $orgId";
			}
			$this->throwError($errorMessage);
		}
		$this->_orgId = $rows[0]['org_id'];		
		
		
		$qstr = "Select site_id From common_site Where site_id = $cleanSiteId";		
		$siteNameQuery = new Common_Query($qstr, $this->_adminLevelError);
		$rows = $siteNameQuery->getResults();
		if(count($rows) < 1) {
			$errorMessage = "Invalid site id.";
			if($this->_adminLevelError) {
				$errorMessage .= " --- siteId: $siteId";
			}
			$this->throwError($errorMessage);
		}
		$this->_siteId = $rows[0]['site_id'];			
		
		$this->_adminLevelError = $adminLevelError;
		$this->_table = new Common_Table_Common_Organization_Customization(false, array(), $wherePairs = array('org_customization_id' => -1));
		$this->_query = new Common_Query('');		
	}
	
	/**
	 * Manages a customization value.
	 * @return boolean The success or failure of the query.
	 */
	private function setCustomization($fieldName, $value, $displayFieldName) {		
		$value = htmlNumericEntities(trim(stripslashes($value)), $this->_encoding);	// Sanitize Input
		$value = str_replace("\n", "<br />", str_replace("\r", "<br />", str_replace("\r\n", "<br />", $value)));
		$fieldNameValuePairs = array();
		$fieldNameValuePairs['org_customization_value'] = $value;
		
		$qstr = "
				Select org_customization_id, org_customization_value, org_customization_status
				From common_organization_customization
				Where org_id = '".$this->_orgId."'
					And site_id = '".$this->_siteId."'
					And org_customization_field = '$fieldName'
				Limit 1
				";
		$this->_query->setQueryString($qstr, $this->_adminLevelError);
		$rows = $this->_query->getResults();
		
		// Check if value is blank. if blank, set inactive
		if ($value == '') {
			$fieldNameValuePairs['org_customization_status'] = 'Inactive';
			unset($fieldNameValuePairs['org_customization_value']);
			$success = $this->_table->updateRow($fieldNameValuePairs, $rows[0]['org_customization_id']);	
		} else {
			if(count($rows) == 0) {
				// Insert New Row
				$fieldNameValuePairs['org_id'] = $this->_orgId;
				$fieldNameValuePairs['site_id'] = $this->_siteId;
				$fieldNameValuePairs['org_customization_field'] = $fieldName;
				$success = $this->_table->addRow($fieldNameValuePairs);
						
			} else {
				if ($rows[0]['org_customization_status'] != 'Active')
					$fieldNameValuePairs['org_customization_status'] = 'Active';
				
				$success = $this->_table->updateRow($fieldNameValuePairs, $rows[0]['org_customization_id']);			
			}
		}
		$this->updateCustomizationSessionValues($fieldName, $value);
		return $success;
	}
		
	/**
	 * Updates the current session.
	 */
	private function updateCustomizationSessionValues($name, $value) {
		$_SESSION['org'][$name] = $value;
		
	}
		
	
	/**
	 * Manages the custom template button color.
	 * @return boolean The success or failure of the query.
	 */
	public function setButtonColor($value) {
		$fieldName = 'org_button_color';
		$displayFieldName = 'button color';		
		return $this->setCustomization($fieldName, $value, $displayFieldName); 
	}
	
	/**
	 * Manages the custom template bar color.
	 * @return boolean The success or failure of the query.
	 */
	public function setBarColor($value) {
		$fieldName = 'org_bar_color';
		$displayFieldName = 'bar color';
		return $this->setCustomization($fieldName, $value, $displayFieldName); 
	}
	
	/**
	 * Manages the custom template header color.
	 * @return boolean The success or failure of the query.
	 */
	public function setHeaderColor($value) {
		$fieldName = 'org_header_color';
		$displayFieldName = 'header color';
		return $this->setCustomization($fieldName, $value, $displayFieldName); 		
	}
	
	/**
	 * Manages the custom template footer color.
	 * @return boolean The success or failure of the query.
	 */
	public function setFooterColor($value) {
		$fieldName = 'org_footer_color';
		$displayFieldName = 'footer color';
		return $this->setCustomization($fieldName, $value, $displayFieldName); 
	}
	
	/**
	 * Manages the custom template font color.
	 * @return boolean The success or failure of the query.
	 */
	public function setFontColor($value) {
		$fieldName = 'org_font_color';
		$displayFieldName = 'font color';
		return $this->setCustomization($fieldName, $value, $displayFieldName); 
	}
	
	/**
	 * Manages the custom template logo.
	 * @return boolean The success or failure of the query.
	 */
	public function setLogo($value) {
		$fieldName = 'org_logo';
		$displayFieldName = 'logo';
		return $this->setCustomization($fieldName, $value, $displayFieldName); 
	}
	
	/**
	 * Manages the welcome header.
	 * @return boolean The success or failure of the query.
	 */
	public function setWelcomeHeader($value) {	
		$fieldName = 'org_custom_welcome_header';
		$displayFieldName = 'welcome header';
		return $this->setCustomization($fieldName, $value, $displayFieldName); 
	}
	
	/**
	 * Manages the welcome subheader.
	 * @return boolean The success or failure of the query.
	 */
	public function setWelcomeSubheader($value) {
		$fieldName = 'org_custom_welcome_subheader';
		$displayFieldName = 'welcome subheader';
		return $this->setCustomization($fieldName, $value, $displayFieldName); 
	}
	
	/**
	 * Manages the welcome message.
	 * @return boolean The success or failure of the query.
	 */
	public function setWelcomeMessage($value) {		
		if(false) {
			require_once('class/table/common_user.php');
		} else {
			require_once($GLOBALS['wwwBaseDir'] . 'common/class/table/common_user.php');
		}
		$fieldName = 'org_custom_welcome_message';
		$displayFieldName = 'welcome message';

		$userTable = new Common_Table_Common_User(true, array(), array('user_id' => -1));
		$fieldNameValuePairs = array('user_hide_welcome_message' => 'No');
		$wherePairs = array('org_id' => $this->_orgId);			
		$userTable->updateRows($fieldNameValuePairs, $wherePairs);
		
		return $this->setCustomization($fieldName, $value, $displayFieldName);
	}
	
	/**
	 * Manages the welcome box background color.
	 * @return boolean The success or failure of the query.
	 */
	public function setWelcomeBoxBackgroundColor($value) {		
		$fieldName = 'org_custom_welcome_box_background_color';
		$displayFieldName = 'welcome box background color';
		return $this->setCustomization($fieldName, $value, $displayFieldName); 
	}
	
	/**
	 * Manages the welcome box border color.
	 * @return boolean The success or failure of the query.
	 */
	public function setWelcomeBoxBorderColor($value) {		
		$fieldName = 'org_custom_welcome_box_border_color';
		$displayFieldName = 'welcome box border color';
		return $this->setCustomization($fieldName, $value, $displayFieldName); 
	}
	
	/**
	 * Manages the welcome box border color.
	 * @return boolean The success or failure of the query.
	 */
	public function setWelcomeBoxDisplay($value) {		
		$fieldName = 'org_custom_welcome_display';
		$displayFieldName = 'welcome box display option';
		return $this->setCustomization($fieldName, $value, $displayFieldName); 
	}
	
	
	private function br2nl($value) {
		return preg_replace('#<br\s*/?>#i', "\n", $value);
	}
	
}
?>
