<?php

/*
	"Csv2WPeC Coupon" Copyright (C) 2013 Davide Mencarini (email: davidemencarini@alice.it)

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>
*/


include_once( 'csv2wpecCoupon_LifeCycle.php' );
include_once( 'csv2wpecCoupon_CouponHandler.php' );


class csv2wpecCoupon_Plugin extends csv2wpecCoupon_LifeCycle {

 	var $export_coupons;
	var $import_coupons;

	public function __construct()
	{
		$this->export_coupons = new csv2wpecCoupon_CouponHandler( 'export' );
		$this->import_coupons = new csv2wpecCoupon_CouponHandler( 'import' );
	}


    /**
     *
     * @return string Display name of the plugin to show as a name/title in HTML.
     *
     */
    public function getPluginDisplayName() {
        return 'Csv2WPeC Coupon';
    }


    /**
     *
     * Check WP and WPeC required version on activation
     *
     */
    public function activate()
    {
    	require( 'csv2wpecCoupon_CheckVersion.php' );
  	}


	function get_plugin_status( $location = '' )
	{
        $plugin_file = trailingslashit( WP_PLUGIN_DIR ) . $location;
        $plugin_file = str_replace( '\\', '/', $plugin_file );
		if ( !file_exists( $plugin_file ) )
			return 0;
		elseif ( is_plugin_inactive( $location ) )
			return 1;
		elseif ( is_plugin_active( $location ) )
			return 2;
	}


    /**
     *
     * Add scripts & styles for the administration page
     * Define Ajax actions
     *
     */
    public function addActionsAndFilters()
    {
        // Add just for the administration page
        if ( strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug() ) !== false )
        {
			// Add Actions & Filters

 			add_action( 'init', array(&$this, 'addAdminStyles') );
 			add_action( 'init', array(&$this, 'addAdminScripts') );

			// Adding scripts & styles to all pages

			// Register short codes

			// Register AJAX hooks

			// actions on coupons
			add_action( 'wp_ajax_DumpCoupons',    			array( &$this, 'ajaxDumpCoupons' ) );
			add_action( 'wp_ajax_ExportCoupons',  			array( &$this, 'ajaxExportCoupons' ) );
			add_action( 'wp_ajax_ReadCoupons',    			array( &$this, 'ajaxReadCoupons' ) );
			add_action( 'wp_ajax_ImportCoupons',  			array( &$this, 'ajaxImportCoupons' ) );

			// update list table
			add_action( 'wp_ajax_UpdateListTable', 			array( &$this, 'ajaxUpdateListTable' ) );
		}

		// Add Plugin Page
		add_action( 'admin_menu', array(&$this, 'addSettingsSubMenuPage') );
   }


	function addAdminStyles()
	{
       	wp_enqueue_style( 'jquery-ui', plugins_url('css/jquery-ui.css', __FILE__) );

		wp_register_style( 'csv2wpec-coupon', plugins_url( 'css/csv2wpec.css', __FILE__ ) );
		wp_enqueue_style( 'csv2wpec-coupon' );
 	}


	function addAdminScripts()
	{
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-tabs' );

		// coupon
		wp_enqueue_script( 'ajax-coupon-handler', plugins_url( 'js/coupon.js', __FILE__ ), array('jquery') );
		wp_localize_script( 'ajax-coupon-handler', 'ajax_coupon_localize',
			array(
				'plugin_ajaxurl' => admin_url( 'admin-ajax.php' ) . '?page=' . $this->getSettingsSlug(),
				'ajaxDumpCoupons_Nonce' => wp_create_nonce( 'dump-coupon-nonce' ),
				'ajaxExportCoupons_Nonce' => wp_create_nonce( 'export-coupon-nonce' ),
				'ajaxReadCoupons_Nonce' => wp_create_nonce( 'read-coupon-nonce' ),
				'ajaxImportCoupons_Nonce' => wp_create_nonce( 'import-coupon-nonce' ),
				'ajaxDumpCoupons_Error' => __( 'Error dumping coupon data', 'csv2wpec-coupon' ),
				'ajaxReadCoupons_Error' => __( 'Error reading coupon data', 'csv2wpec-coupon' ),
				'ajaxImportCoupons_Error' => __( 'Error importing coupon data', 'csv2wpec-coupon' ),
				'import_coupon_LogFile' => __( 'Errors have been logged to: ', 'csv2wpec-coupon' ),
				'pluginTextDomain' => 'csv2wpec-coupon',
			)
		);

		// list table
		wp_enqueue_script( 'ajax-list-table-handler', plugins_url( 'js/list_table.js', __FILE__ ), array('jquery') );
		wp_localize_script( 'ajax-list-table-handler', 'ajax_list_table_localize',
			array(
				'ajaxPluginPage' => $this->getSettingsSlug(),
				'ajaxUpdateListTable_Nonce' => wp_create_nonce( 'update-list-table-nonce' ),
			)
		);
 	}


    /**
     *
	 * Check version of a registered WordPress script
	 *
	 */
	function script_version_compare( $handle, $version, $compare = '>=' ) {
		global $wp_scripts;
		if ( !is_a($wp_scripts, 'WP_Scripts') )
			$wp_scripts = new WP_Scripts();

		$query = $wp_scripts->query($handle, 'registered');
		if (!$query)
			return false;

		return version_compare( $query->ver, $version, $compare );
	}


    /**
     *
	 * Page Setting
     * generate content for the Administration page
     *
     */
    public function settingsPage() {
		if ( !current_user_can('manage_options') ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'csv2wpec-coupon' ) );
		}
		?>
		<div class='wrap'>

			<div id="icon-plugins" class="icon32"></div>
			<h2><?php esc_html_e( $this->getPluginDisplayName() ); ?></h2>
			<br />

			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$("#plugin_config_tabs").tabs();
					$("#plugin_config_tabs").show();
				});
			</script>

			<div class="plugin_config">
				<div id="plugin_config_tabs" style="display:none;">
					<ul>
						<li><a href="#plugin_config-coupon-tab"><?php esc_html_e( 'Coupons', 'csv2wpec-coupon' ); ?></a></li>
					</ul>
					<div id="plugin_config-coupon-tab">
						<?php $this->outputCouponContents(); ?>
					</div>
				</div>
			</div>

		</div>
		<?php
	}


    public function outputCouponContents()  	{ include( 'csv2wpecCoupon_CouponSettings.php' ); }


    /**
	 *
     * Puts the configuration page in the Tools menu
     *
     */
    public function addSettingsSubMenuPage() {
        $this->requireExtraPluginFiles();
        $displayName = $this->getPluginDisplayName();
        add_submenu_page('tools.php',
                         $displayName,
                         $displayName,
                         'manage_options',
                         $this->getSettingsSlug(),
                         array(&$this, 'settingsPage'));
    }


	/** *********************************************************************************
	 *
	 * ajax coupon callbacks
	 *
	 */


    /**
	 *
     * Display Coupons in a WP ListTable
     *
     */
	function ajaxDumpCoupons()
	{
		// Role-Based Security: check if user has the minimal role required for this operation
		$minRole = $this->minimalRoleRequiredForOperation( 'dumpCoupons' );
	    if ( !$this->isUserRoleEqualOrBetterThan( $minRole ) )
	    	die(1);

		// Nonce Security check
		$nonce = $_POST['ajaxDumpCoupons_Nonce'];
		if ( !wp_verify_nonce( $nonce, 'dump-coupon-nonce' ) )
			die ( 'Busted!');

		// Do not let IE cache this request
		header( "Pragma: no-cache" );
		header( "Cache-Control: no-cache, must-revalidate" );
		header( "Expires: Thu, 01 Jan 1970 00:00:00 GMT" );
		header( "Content-Type: application/json" );

		$response = array( 'table' => '',
						   'html' => '' );

		$dataKey = $this->prefix( $this->export_coupons->getDataSlug() );
		$response['table'] = $this->export_coupons->Display( $dataKey );

        if ( $this->export_coupons->NotEmptyTable() ) {
			ob_start();
				$export_coupon_url = sprintf( $this->getAjaxUrl('ExportCoupons&page=%s'), urlencode( $this->getSettingsSlug() ) );
				require( 'csv2wpecCoupon_CouponExport.php' );
 				$response['html'] = ob_get_contents();
			ob_end_clean();
		}

		// output response
		echo json_encode( $response );

		// exit
		die();
	}


    /**
	 *
     * Save Coupons to File
     *
     */
	function ajaxExportCoupons()
	{
		// Role-Based Security: check if user has the minimal role required for this operation
		$minRole = $this->minimalRoleRequiredForOperation( 'exportCoupons' );
	    if ( !$this->isUserRoleEqualOrBetterThan( $minRole ) )
	    	die(1);

		// // Nonce Security check
		// $nonce = $_POST['ajaxExportCoupons_Nonce'];
		// if ( !wp_verify_nonce( $nonce, 'export-coupon-nonce' ) )
		// 	die ( 'Busted!');

		// Do not let IE cache this request
		header( "Pragma: no-cache" );
		header( "Cache-Control: no-cache, must-revalidate" );
		header( "Expires: Thu, 01 Jan 1970 00:00:00 GMT" );

		$this->export_coupons->SaveToFileAs();

		// exit
		die();
	}


    /**
	 *
     * Read and Validate Coupons Data from File
     * Data is saved as WP option
     *
     */
	function ajaxReadCoupons()
	{
		// Role-Based Security: check if user has the minimal role required for this operation
		$minRole = $this->minimalRoleRequiredForOperation( 'readCoupons' );
	    if ( !$this->isUserRoleEqualOrBetterThan( $minRole ) )
	    	die(1);

		// Nonce Security check
		$nonce = $_POST['ajaxReadCoupons_Nonce'];
		if ( !wp_verify_nonce( $nonce, 'read-coupon-nonce' ) )
			die ( 'Busted!');

		// Do not let IE cache this request
		header( "Pragma: no-cache" );
		header( "Cache-Control: no-cache, must-revalidate" );
		header( "Expires: Thu, 01 Jan 1970 00:00:00 GMT" );
		header( "Content-Type: application/json" );

		$response = array( 'table' => '',
						   'html' => '' );

		$dataKey = $this->prefix( $this->import_coupons->getDataSlug() );
		$filename = $_SESSION[$dataKey]['file_uploaded'];
		$reading_errors = $this->import_coupons->FillFromFile( $filename );

		if ( empty( $reading_errors ) )
		{
			$valid_data = $this->import_coupons->validateData();

			$_SESSION[$dataKey]['data'] = $this->import_coupons->getData();
 			$_SESSION[$dataKey]['header'] = $this->import_coupons->getDataHeader();
 			$_SESSION[$dataKey]['action'] = 'validating';

 			$response['table'] = $this->import_coupons->Display( $dataKey );

			if ( $this->import_coupons->getDataCount() > 0 ) {
				ob_start();
					require('csv2wpecCoupon_CouponImport.php');
	 				$response['html'] = ob_get_contents();
				ob_end_clean();
 			}
		}
		else {
			$response['html'] = $reading_errors;
			unset( $_SESSION[$dataKey]['data'] );
			unset( $_SESSION[$dataKey]['header'] );
			unset( $_SESSION[$dataKey]['action'] );
		}

		// output response
		echo json_encode( $response );

		// exit
		die();
	}


    /**
	 *
     * Write Coupon Data to Database
     *
     */
	function ajaxImportCoupons()
	{
		// Role-Based Security: check if user has the minimal role required for this operation
		$minRole = $this->minimalRoleRequiredForOperation( 'importCoupons' );
	    if ( !$this->isUserRoleEqualOrBetterThan( $minRole ) )
	    	die( 1 );

		// Nonce Security check
		$nonce = $_POST['ajaxImportCoupons_Nonce'];
		if ( !wp_verify_nonce( $nonce, 'import-coupon-nonce' ) )
			die ( 'Busted!');

		// Do not let IE cache this request
		header( "Pragma: no-cache" );
		header( "Cache-Control: no-cache, must-revalidate" );
		header( "Expires: Thu, 01 Jan 1970 00:00:00 GMT" );
		header( "Content-Type: application/json" );

		// start processing
		$response = array();

		$result = array( 'items' => 0,
						 'processed' => 0,
						 'not_processed' => 0,
						 'inserted' => 0,
						 'updated' => 0,
						 'deleted' => 0,
						 'error' => 0
						);

		$dataKey = $this->prefix( $this->import_coupons->getDataSlug() );
		$data = $_SESSION[$dataKey]['data'];

		if ( $data ) {

			$result['items'] = count( $data );
			$data_log = array();

			$_SESSION[$dataKey]['action'] = 'processing';

			foreach ( $data as $key => $dataItem ) {
				$status = $this->import_coupons->InsertDataRecordIntoDB( $dataItem );
				$data[$key] = array_merge( $dataItem, $status );

				if ( isset( $status['processing_result'] ) )
					$result[$status['processing_result']]++;

				// prepare data for logging
				if ( isset( $status['processing_errors'] ) ) {
					$result['error']++;
					$data_log['data'][] = array( 'linenum' => $dataItem['linenum'],
												 'code' => $status['code'],
												 'errmsg' => $status['processing_errors']
											   );
				}
			}

			$_SESSION[$dataKey]['data'] = $data;

			$result['processed'] = $result['items'] - $result['not_processed'];

			// log errors
			if ( !empty( $data_log ) ) {
				ob_start();
					require('csv2wpecCoupon_CouponProcessingLog.php');
					$result['file_log'] = ob_get_contents();
				ob_end_clean();
			}

 		}

		$response['table'] = $this->import_coupons->Display( $dataKey );
		$response['result'] = $result;

		// output response
		echo json_encode( $response );

		// exit
		die();
	}


	/** *********************************************************************************
	 *
	 * ajax update wp_ListaTable
	 *
	 */


    /**
	 *
     * Update a WP ListTable
     * $form_id select the table to upload
     *
     */
	function ajaxUpdateListTable()
	{
		// Nonce Security check
		$nonce = $_GET['_wpnonce'];
		if ( !wp_verify_nonce( $nonce, 'update-list-table-nonce' ) )
			die ( 'Busted!');

		// Do not let IE cache this request
		header( "Pragma: no-cache" );
		header( "Cache-Control: no-cache, must-revalidate" );
		header( "Expires: Thu, 01 Jan 1970 00:00:00 GMT" );
		header( "Content-Type: application/json" );

		$form_id = $_GET['form_id'];

		// output response
		echo json_encode( $this->UpdateListTable( $form_id ) );

		// exit
		die();
    }


	private function UpdateListTable( $form_id )
	{
		$response = array();

		switch ( $form_id ) {

			case 'export_coupon_form' :
				$slug = $this->export_coupons->getDataSlug();
				$response['table_wrapper'] = $slug . '_' . 'table';
		 		$response['table'] = $this->export_coupons->Display( $this->prefix($slug) );
				break;

			case 'import_coupon_form' :
				$slug = $this->import_coupons->getDataSlug();
				$response['table_wrapper'] = $slug . '_' . 'table';
		 		$response['table'] = $this->import_coupons->Display( $this->prefix($slug) );
		 		break;
		}

		return $response;
	}


	/** *********************************************************************************
	 *
	 * Operation Roles
	 *
	 */


	/**
	 *
	 * defines minimum role for operations
	 *
	 */
	 function minimalRoleRequiredForOperation( $opType )
	 {
	 	$minRole = '';
		switch ( $opType ) {
			// Coupon Operation Roles
			case 'dumpCoupons'		: $minRole = 'Administrator'; break;
			case 'exportCoupons'	: $minRole = 'Administrator'; break;
			case 'readCoupons'		: $minRole = 'Administrator'; break;
			case 'importCoupons'	: $minRole = 'Administrator'; break;
		}
		return $minRole;
	 }


    /**
     *
     * @param string $pathRelativeToThisPluginRoot points to a file with relative path from
     * this plugin's root dir. I.e. file "des.js" in the root of this plugin has
     * url = $this->getPluginFileUrl('des.js');
     * If it was in a sub-folder "js" then you would use
     *    $this->getPluginFileUrl('js/des.js');
     * @return string full url to input file
     *
     */
    public function getPluginFileUrl( $pathRelativeToThisPluginRoot = '' ) {
        return plugins_url( $pathRelativeToThisPluginRoot, __FILE__ );
    }

}
