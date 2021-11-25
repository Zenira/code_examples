<?php
/**
 * The html table class will print a table from the values given.
 *
 * @author       Lindsay Sauer <redacted@email.com>
 * @package      core
 * @subpackage   database
 * @version      1.1
 */
 
if(false) {
	require_once('object.php');
	require_once('../functions/include.php');
} else {
	if(!isset($GLOBALS['wwwBaseDir'])) require_once('../other_includes/set_base_dir.php');
	require_once($GLOBALS['wwwBaseDir'] . 'core/class/object.php');
	require_once($GLOBALS['wwwBaseDir'] . 'core/functions/include.php');
}

class Html_Table extends Object {
	/**
	 * @var string ID of the table to be generated.
	 */
	public $_tableID;
	
	/**
	 * @var string Options to pass to the tablesorter call.
	 */
	public $_tablesorterOptions = '';
	public $_tablesorterWidgets = '';
	public $_tablesorterWidgetOptions = '';
	
	/**
	 * @var string Classes to apply to different areas of the table.
	 */
	public $_tableClasses = '';
	public $_headerClasses = array(); 
	public $_rowClasses = array();
	public $_cellClasses = array();
	
	/**
	 * @var boolean True to show the total rows returned.
	 */
	public $_tableShowTotalRows = false;
	
	/**
	 * @var string Optional identifier for the total rows.
	 */
	public $_tableTotalRowsIdentifier = '';
	
	/**
	 * @var string Optional identifier for the total rows plural.
	 */
	public $_tableTotalRowsIdentifierPlural = '';
	
	/**
	 * @var bool If the JS and CSS files should be included
	 */
	public $_includeFiles = true;
	
	/**
	 * @var string HTML generated for display.
	 * @access private
	 */
	private $_tableHTML;
	
	/**
	 * @var int Total number of table header columns.
	 * @access private
	 */
	private $_totalColumns;
	
	/**
	 * @var bool If the tablesorter javascript has been included or not.
	 * @access private
	 */
	private static $_jsIncluded = false;
	
	/**
	 * Creates html for a table from the data passed. $row['trClass'] are ignored and used to set the class for a row.
	 * @param array $headers An array of table headers.
	 * @param array $rows A multidimensional array of data to display in the table.
	 * @param array $classes A multidimensional array of classes. Contains trClass, thClasses, and tdClasses.
	 * @return string The table HTML to be displayed.
	 */
	public function createHtmlTable($headers, $rows, $classes = '') {
		if($this->_includeFiles && !$this->_jsIncluded) {
			$this->includeJS();
			$this->includeCSS();	
		}
				
		// Set Classes
		$headerClasses = isset($classes['thClasses']) ? $classes['thClasses'] : '';
		$rowClasses = isset($classes['trClasses']) ? $classes['trClasses'] : '';
		$cellClasses = isset($classes['tdClasses']) ? $classes['tdClasses'] : '';
		$tableClasses = isset($classes['tableClasses']) ? $classes['tableClasses'] : '';
		
		// Calculate total columns
		$_totalColumns = 0;
		$rowspan = 1;
		foreach($headers as $header) {
			if (!is_array($header)) {
				$_totalColumns++;
			} else {
				$_totalColumns += count($header['headers']['labels']);
				$rowspan = 2;
			}
		}
		
		$_tableHTML .= '<div class="tablesorter-loader ui basic segment">
						  <div class="ui active inverted dimmer">
							<div class="ui text loader">Loading</div>
						  </div>
						  <p></p>
						</div>';
		$_tableHTML .= '<div class="tablesorter-wrapper">';	
		if ($this->_tableShowTotalRows) {
			$_tableHTML .= $this->createTotalRowsContainer(count($rows));
		}
		$_tableHTML .= '<table id="' . $this->_tableID . '" class="ui small selectable tablet '.$this->_tableClasses.' stackable table">';
		
		// --------------------------------------------------------------
		// Headers
		// --------------------------------------------------------------
		$_tableHTML .= '<thead><tr>';
		$subheaders = array();
		foreach($headers as $header) {
			if (!is_array($header)) {
				$_tableHTML .= '<th rowspan="'.$rowspan.'">'.$header.'</th>';
			} else {
				$_tableHTML .= '<th colspan="'.count($header['headers']['labels']).'" data-sorter="false">'.$header['multiheader'].'</th>';				
				$subheaders[] = $header['headers'];
			}
		}
		$_tableHTML .= '</tr>';
		
		// Subheaders
		if (count($subheaders) > 0) {
			$_tableHTML .= '<tr>';
			foreach ($subheaders as $subheader) {
				foreach ($subheader['labels'] as $key=>$header) {		
					$_tableHTML .= '<th data-selector-name="'.$subheader['data-selector-name'][$key].'" data-priority="'.$subheader['data-priority'][$key].'">'.$header.'</th>';
				}
			}
			$_tableHTML .= '</tr>';
		}
		$_tableHTML .= '</thead><tbody>';
		// --------------------------------------------------------------
		if(!$rows) {
			$noResultsTxt = ($this->_tableTotalRowsIdentifier <> '') ? ' '.strtolower($this->_tableTotalRowsIdentifier) : '';
			$_tableHTML .=' <tr class="noResults"><td colspan="'.$_totalColumns.'">No results.</td></tr>';
		} else {
			// Not a multidimensional array; create one
			if(!is_array($rows[0])) {
				$rows = array($rows);
			}
			foreach($rows as $k => $row) {
				$rowClasses = (isset($this->_rowClasses[$k])) ? $this->_rowClasses[$k] : '';
				$rowClass = '';
				if (isset($row['trClass'])) {
					$rowClasses = $row['trClass'];
					unset($row['trClass']);
				}
				
				if(sizeof($row) != $_totalColumns) {
					$errorMessage = 'A table row does not have the same number of values as the columns of the table. Headers('.$_totalColumns.') - Rows('.sizeof($row).')';
					$this->throwError($errorMessage);
				}
				
				$_tableHTML .= '<tr class="'.$rowClasses.'">';
				
				foreach($row as $value) {
					$_tableHTML .= '<td>' . $value . '</td>';
				}
				$_tableHTML .= '</tr>';
			}
		}
		$_tableHTML .= '</tbody></table></div>';
		
		$this->tableSorter();
		return $_tableHTML;
	}
	
	/**
	 * Adds the tableSorter function to the table 
	 * @return string The script to add tableSorter to the table with id $tableID.
	 */
	public function tableSorter() { ?>
		<?=jsInclude($GLOBALS['wwwBaseDir'] . 'core/javascript/jquery.tablesorter.accentfolding.js')?>
        <script>
            $(document).ready(function() {									   
                // Default settings
                var widgetOptions = {<?=$this->_tablesorterWidgetOptions?>};
                var widgets = [<?=$this->_tablesorterWidgets?>];
				var initializedConfig = function() {
					$('.tablesorter-loader').hide();
					$('.tablesorter-wrapper').show();
					if ($.inArray('columnSelector', widgets)) {
						$('#columnSelector .ui.checkbox').checkbox();
					}
					if (typeof tablesorterInitializedFunction !== 'undefined') {
						tablesorterInitializedFunction();
					}
				}
				
                // Configure Tablesorter
                var tablesorterConfig = {
                    namespace: "<?=$this->_tableID?>",
                    /*widthFixed : true,*/	// Hidden filter input/selects will resize the columns, so try to minimize the change
                    sortLocaleCompare : true, // Enable use of the characterEquivalents reference
                    widgets: widgets,
                    widgetOptions : widgetOptions,
                    headerTemplate: '{content}{icon}',
                    cssIcon: 'icon sort',
                    cssIconDesc: 'down',
                    cssIconAsc: 'up',
					initialized: initializedConfig,
					duplicateSpan : true,
					<?=$this->_tablesorterOptions?>
                };
				if (typeof tablesorterAdditionalConfig !== 'undefined') $.extend(tablesorterConfig, tablesorterAdditionalConfig);

                $("#<?=$this->_tableID?>").tablesorter(tablesorterConfig);
				$('.tablesorter-wrapper').show();
				
            });
        </script>
		<?
	}
	
	/**
	 * Includes all required JavaScript.
	 */
	private function includeJS() {
		echo jsInclude($GLOBALS['wwwBaseDir'] . 'core/javascript/jquery/jquery.tablesorter.min.js');
		echo jsInclude($GLOBALS['wwwBaseDir'] . 'core/javascript/jquery/jquery.tablesorter.widgets.min.js');
		echo jsInclude($GLOBALS['wwwBaseDir'] . 'core/javascript/jquery/widgets/widget-columnSelector.min.js');
		echo jsInclude($GLOBALS['wwwBaseDir'] . 'core/javascript/jquery/widgets/widget-storage.min.js');
		echo jsInclude($GLOBALS['wwwBaseDir'] . 'core/javascript/widgets/widget-filter-formatter-jui.js');
		$this->_jsIncluded = true;
	}
	
	/**
	 * Includes all required CSS.
	 */
	private function includeCSS() {
		//echo cssInclude($GLOBALS['wwwBaseDir'] . 'core/css/tablesorter.css');
		echo cssInclude($GLOBALS['wwwBaseDir'] . 'core/css/tablesorter.custom.css');
	}
	
	/**
	 * Sets up the container to display the table's total rows.
	 * @param int $totalRows The total number of rows.
	 * @return string The html of the container.
	 */
	private function createTotalRowsContainer($totalRows) {
		$html = '<div id="' . $this->_tableID . '_numberOfRowsDiv" class="numberOfRowsDiv hideForPrint">';
			$html .= '<div id="' . $this->_tableID . '_numberOfRows" class="numberOfRows">';
			$html .= $totalRows.' ';
			if ($this->_tableTotalRowsIdentifier == '') {
				$this->_tableTotalRowsIdentifier = 'Result';
			}
			if ($totalRows == 1) {
				$html .= $this->_tableTotalRowsIdentifier;
			}
			else {
				if ($this->_tableTotalRowsIdentifierPlural <> '') {
					$html .= $this->_tableTotalRowsIdentifierPlural;
				} else {
					$html .= $this->_tableTotalRowsIdentifier . 's';
				}
			}
			$html .= ' Shown';
			$html .= '</div>';
		$html .= '</div>';
		return $html;
	}
}
?>
