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


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/**
 * csv2wpec Coupon List Table Class
 *
 * Renders the Coupons table on the Coupons page
 *
 */
class csv2wpecCoupon_CouponListTable extends WP_List_Table
{
	private $per_page = 24;			// items per page
	private $total_count;			// total numner of items
 	private $list_slug;				// list table code

	private $user_mode;
	private $max_user_columns = 6;	// max number of columns for collapsed user table

	private $statuses;

	function __construct( $slug )
	{
		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
			'singular'	=> 'coupon',		// singular name of the listed records
			'plural'	=> 'coupons',		// plural name of the listed records
			'ajax'		=> false,			// does this table support ajax?
            'screen'	=> 'interval-list' 	// hook suffix
		) );

		if ( isset( $slug ) )
			$this->list_slug = $slug;

		$this->user_mode = $this->is_user_data();

		$this->statuses = array(
			'active'   => _x( 'Active', 'coupon status', 'csv2wpec-coupon' ),
			'inactive' => _x( 'Inactive', 'coupon status', 'csv2wpec-coupon' ),
			'unknown'  => _x( 'Unknown', 'coupon status', 'csv2wpec-coupon' ),
		);
    }


	/**
	 *
	 * Register column information
	 *
	 * @return array An associative array containing column information:
	 * 				 'column slugs (and class)' => 'Visible Titles'
	 *
	 */
	function get_columns()
	{
		if ( !( $columns = $this->get_user_columns() ) ) {
			$columns = array(
				// 'cb'			=> '<input type="checkbox" />',
				'coupon_code'	=> esc_html__( 'Coupon code', 'csv2wpec-coupon' ),
				'value'			=> esc_html__( 'Discount', 'csv2wpec-coupon' ),
				'start'			=> esc_html__( 'Start Date', 'csv2wpec-coupon' ),
				'expiry'		=> esc_html__( 'Expiration', 'csv2wpec-coupon' ),
				'status'		=> esc_html__( 'Status', 'csv2wpec-coupon' ),
			);
		}
		else {
			if ( isset( $columns['record_id']  ) )
				unset( $columns['record_id'] );
			$tot_columns = count( $columns );
			$collapse = isset ( $_GET['collapse'] ) ? $_GET['collapse'] : 1;
			if ( ( $tot_columns > $this->max_user_columns ) && $collapse ) {
				$columns = array_slice( $columns, 0, $this->max_user_columns );
				$columns = array_map( 'esc_html', $columns );
			}
		}

		return $columns;
	}


	/**
	 *
	 * Register sortable columns (ASC/DESC toggle)
	 *
	 * @return array An associative array containing all the columns that should
	 *				 be sortable: 'slugs' => array( 'data_values', bool );
	 *				 the value is db column to sort by. Often, the key and value
	 *				 will be the same, but this is not always the case (as the
	 *				 value is a column name from the database, not the list table)
	 *
	 */
	function get_sortable_columns()
	{
		$sortable_columns = array(
			// 'coupon_code'	=> array( 'coupon_code', false ),  // true means it's already sorted
			// 'value'			=> array( 'value', false ),
			// 'start'			=> array( 'start', false ),
			// 'expiry'			=> array( 'expiry', false ),
			// 'status'			=> array( 'status', false )
		);

		return $sortable_columns;
	}


	/**
	 *
	 * Default render for columns
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
	 * @return string Text or HTML to be placed inside the column <td>
	 *
	 */
	protected function column_default( $item, $column_name )
	{
		return $this->check_invalid_field( $item, $column_name );
	}


	/**
	 *
	 * Render the checkbox column
	 *
     * @param 	array 	$item A singular item (one full row's worth of data)
     * @return	string 	Text to be placed inside the column
	 *
	 */
    function column_cb( $item )
    {
		return sprintf(
		    '<input type="checkbox" name="%1$s[]" value="%2$s" />',
		    /*$1%s*/ esc_attr( $this->_args['singular'] ),  	// Let's simply repurpose the table's singular label
		    /*$2%s*/ esc_attr( $item['ID'] )					// The value of the checkbox should be the record's id
		);
    }


	/**
	 *
	 * Render the coupon_code column
	 *
	 * @param	array	$item A singular item (one full row's worth of data)
	 * @return	string 	Text to be placed inside the column
	 *
	 */
	protected function column_coupon_code( $item )
	{
		$code = $item['coupon_code'];
		if ( $code == '' )
			$code = 'N/A';

		$page = isset ( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';

		//Build row actions
		$actions = array(
		);

		if ( isset( $item['data_errors'] ) ) {
			$code_url = sprintf( "?page=%s&action=show_data_errors&item=%s&type=coupon", $page, $item['Progr'] );
			$code_link = sprintf( '<a href="%s">%s</a>', esc_url( $code_url ), esc_html( $code ) );
			$code = '<span class="active_key">' . $code_link . '</span>';
		}

		$status = '';
		if ( isset( $item['result'] ) ) {
			switch ( $item['result'] ) {
				case 'skip_processing':
					$status = '<span class="status_skip"> [' . esc_html__('skip processing', 'csv2wpec-coupon') . '] </span>'; break;
				case 'to_delete':
					$status = '<span class="status_to_delete"> [' . esc_html__('to delete', 'csv2wpec-coupon') . '] </span>'; break;
				case 'deleted':
					$status = '<span class="status_deleted"> [' . esc_html__('deleted', 'csv2wpec-coupon') . '] </span>'; break;
				case 'inserted':
					$status = '<span class="status_inserted"> [' . esc_html__('inserted', 'csv2wpec-coupon') . '] </span>'; break;
				case 'updated':
					$status = '<span class="status_updated"> [' . esc_html__('updated', 'csv2wpec-coupon') . '] </span>'; break;
			}
		}

		$linenum = isset( $item['linenum'] ) ? '[' . $item['linenum'] . ']' : '';

		//Return the title contents
		return sprintf('<span class="item_code">%1$s</span> <span class="item_id">(id:%2$s) %3$s</span> %4$s %5$s',
		    /*$1%s*/ $code,
		    /*$2%s*/ esc_html( $item['ID'] ),
		    /*$3%s*/ esc_html( $linenum ),
		    /*$4%s*/ $status,
		    /*$5%s*/ $this->row_actions($actions)
		);
    }


	/**
	 *
	 * Render the discount column
	 *
	 * @param	array 	$item Contains all the data of the discount code
	 * @return	string
	 *
	 */
	protected function column_value( $item )
	{
		if ( $this->user_mode )
			return $this->check_invalid_field( $item, 'value' );

		switch ( $item['is_percentage'] )
		{
			case 0:
				return wpsc_currency_display( $item['value'] );
				break;
			case 1:
				return esc_html( $item['value'] . '%' );
				break;
			case 2:
				return esc_html__( 'Free shipping', 'csv2wpec-coupon' );
				break;
		}
	}


	/**
	 *
	 * Render the start column
	 *
	 * @param	array 	$item Contains all the data of the start code
	 * @return	string
	 *
	 */
	protected function column_start( $item )
	{
		if ( $this->user_mode )
			return $this->check_invalid_field( $item, 'start' );

		if ( !empty( $item[ 'start'] ) && '0000-00-00 00:00:00' != $item['start'] ) {
			$start_date = strtotime( get_date_from_gmt( $item['start'] ) );
			$value = date_i18n( get_option( 'date_format' ), $start_date );
		}
		else
			$value = '';

		return esc_html( $value );
	}


	/**
	 *
	 * Render the expiry column
	 *
	 * @param	array 	$item Contains all the data of the expiry code
	 * @return	string
	 *
	 */
	protected function column_expiry( $item )
	{
		if ( $this->user_mode )
			return $this->check_invalid_field( $item, 'expiry' );

		if ( !empty( $item['expiry'] ) && '0000-00-00 00:00:00' != $item['expiry'] ) {
			$expiry_date = strtotime( get_date_from_gmt( $item['expiry'] ) );
			$value = date_i18n( get_option( 'date_format' ), $expiry_date );
		}
		else
			$value = '';

		return esc_html( $value );
	}


	/**
	 *
	 * Render the status column
	 *
	 * @access      private
	 * @param       array $item Contains all the data of the status code
	 * @since       3.8.10
	 * @return      string
	 *
	 */
	protected function column_status( $item )
	{
		if ( !array_key_exists( $item['status'], $this->statuses ) )
			$item['status'] = 'unknown';

		$column = '<span class="coupon-status coupon-status-%1$s">%2$s</a>';
		$column = sprintf( $column, esc_attr( $item['status'] ), esc_html( $this->statuses[$item['status']] ) );

		return $column;
	}


	/**
	 *
	 * Register bulk actions
	 *
	 * @return array An associative array containing all the bulk actions:
	 *				 'slugs' => 'Visible Titles'
	 *
	 */
	function get_bulk_actions() {
		$actions = array(
			// 'delete'		=> 'Delete'
		);
		return $actions;
	}


	/**
	 *
	 * Handle bulk actions
	 *
	 * @return array An associative array containing all the bulk actions:
	 *				 'slugs' => 'Visible Titles'
	 *
	 */
	function process_bulk_action()
	{
		// //Detect when a bulk action is being triggered
		// if ( 'delete' === $this->current_action() ) {
		// 	wp_die( 'Items deleted (or they would be if we had items to delete)!' );
		// }
	}


	/**
	 *
	 * Retrieve the data for the table from the database
	 *
	 */
	public function retrieve_data_from_database()
	{
		global $wpdb;

		$coupons_data = array();

		// if ( isset( $_GET['paged'] ) ) $page = $_GET['paged']; else $page = 1;
		$current_page = isset( $_GET['paged'] ) ? $_GET['paged'] : 1;
		$per_page = $this->per_page;
		$offset = ( $current_page - 1 ) * $this->per_page;

		$where = '';
		$order = isset( $_GET['order'] ) ? $_GET['order'] : 'ASC';
		$limit = " LIMIT $offset,$per_page;";

		$this->total_count = $wpdb->get_var( "SELECT COUNT(id) AS count FROM " . WPSC_TABLE_COUPON_CODES );
		$coupons = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` {$where} ORDER BY id {$order} {$limit} ", ARRAY_A );

		if ( $coupons ) {
			foreach ( $coupons as $coupon ) {
				$coupons_data[] = array(
					'ID'			=> $coupon['id'],
					'coupon_code'	=> $coupon['coupon_code'],
					'value'			=> $coupon['value'],
					'is_percentage'	=> $coupon['is-percentage'],
					'start'			=> $coupon['start'],
					'expiry'		=> $coupon['expiry'],
					'status'		=> $coupon['active'] == 1 ? 'active' : 'inactive',
				);
			}
		}

		return $coupons_data;
	}


	/**
	 *
	 * Retrieve the data for the table from an array
	 *
	 */
	public function retrieve_data_from_array()
	{
		$coupons_data = array();

		$dataKey = $this->list_slug;
		$this->total_count = count( $_SESSION[$dataKey]['data'] );

		if ( $this->total_count > 0 ) {

			// checks for sorting input and sorts the data accordingly
			function usort_reorder($a,$b){
				$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'coupon_code'; //If no sort, default to coupon
				$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
				$result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
				return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
			}
			// usort( $data, 'usort_reorder' );

			// trim the data to the current page
			$current_page = isset( $_GET['paged'] ) ? $_GET['paged'] : 1;
			$per_page = $this->per_page;
			$offset = ($current_page-1) * $per_page;
			$coupons = array_slice( $_SESSION[$dataKey]['data'], $offset, $per_page );

			if ( $coupons ) {

				$columns = $this->get_columns();
				$column_keys = array_keys( $columns );
				$null_array = array_fill_keys ( $column_keys, '' );

				$data_action = isset( $_SESSION[$dataKey]['action'] ) ? $_SESSION[$dataKey]['action'] : '';
				$data_errors = $data_action . '_errors';
				$field_errors = $data_action . '_field_errors';
				$action_result = $data_action . '_result';

				foreach ( $coupons as $key => $coupon ) {

					$coupons_data[$key] = $null_array;

					$coupons_data[$key]['Progr'] = $offset + $key;
					$coupons_data[$key]['ID'] = isset( $coupon['record_id'] ) ? $coupon['record_id'] : '-';
					$coupons_data[$key]['coupon_code'] = isset( $coupon['coupon_code'] ) ? $coupon['coupon_code'] : '';
					$coupons_data[$key]['linenum'] = isset( $coupon['linenum'] ) ? $coupon['linenum'] : '';

					if ( isset( $coupon[$data_errors] ) && !empty( $coupon[$data_errors] )) $coupons_data[$key]['data_errors'] = $coupon[$data_errors];
					if ( isset( $coupon[$field_errors] ) && !empty( $coupon[$field_errors] ) ) $coupons_data[$key]['field_errors'] = $coupon[$field_errors];
					if ( isset( $coupon[$action_result] ) && !empty( $coupon[$action_result] ) ) $coupons_data[$key]['result'] = $coupon[$action_result];

					if ( isset( $coupon['raw_data'] ) ) {
						$data = $coupon['raw_data'];
						foreach ( $data as $name => $value ) {
							if ( isset($value) && ( !empty( $value ) || is_numeric( $value ) ) )
								$coupons_data[$key][$name] = $value;
						}
					}
				}
			}
		}

		return $coupons_data;
	}


	/**
	 *
	 * Setup the final data for the table
	 *
	 */
    function prepare_items()
    {
		// records per page to show
		$per_page = $this->per_page;

		// column headers (all columns, hidden columns, sortable columns)
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		// handle bulk actions
		// $this->process_bulk_action();

		//	retrieve data from database or array according to $list_slug
		$data = $this->user_mode ? $this->retrieve_data_from_array() : $this->retrieve_data_from_database();

		// total items are in our data array (or total number of items in your database)
		$total_items = $this->total_count;

		// add data to the items property
		$this->items = $data;

		// register pagination options & calculations.
		$this->set_pagination_args( array(
		    'total_items' => $total_items,
		    'per_page'    => $per_page,
		    'total_pages' => ceil($total_items/$per_page)
		) );
    }


	/**
	 *
	 * Check if the table is used to display Coupon User Data
	 *
	 * @return True if this table displays User Data
	 *
	 */
	protected function is_user_data()
	{
		if ( isset( $this->list_slug ) ) {
			$dataKey = $this->list_slug;
			return ( isset( $_SESSION[$dataKey]['data'] ) );
		}
	}


	/**
	 *
	 * Return column information
	 * Column information is stored in the global $_SESSION
	 * if the table is used to display User Data
	 *
	 * @return array An associative array containing column information:
	 * 				 'column slugs (and class)' => 'Visible Titles'
	 *
	 */
	protected function get_user_columns()
	{
		if ( isset( $this->list_slug ) ) {
			$dataKey = $this->list_slug;
			if ( isset( $_SESSION[$dataKey]['data'] ) && isset( $_SESSION[$dataKey]['header'] ) )
				return $_SESSION[$dataKey]['header'];
		}
	}


	/**
	 *
	 * Generates content for invalid fields
	 *
	 * #return string HTML formatted string
	 *
	 */
	protected function check_invalid_field( $item, $column_name )
	{
		return !isset( $item['field_errors'][$column_name] ) ?
			esc_html( $item[$column_name] ):
			( '<span class="status_error">' . esc_html( $item[$column_name] ) . '</span>' );
	}


	/**
	 *
	 * Generates content for a single row of the table
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @param object $item The current item
	 *
	 */
	function single_row( $item )
	{
		parent::single_row( $item );

		// xtra row for validation errors
		if ( isset( $item['data_errors'] ) ) {

			$column_count = $this->get_column_count();
			$message_container = 'coupon_data_errors_' . $item['Progr'];

			?>
			<tr class="data_errors" name="<?php echo esc_attr( $message_container ); ?>" id="<?php echo esc_attr( $message_container ); ?>">
				<td colspan="<?php echo esc_attr( $column_count ); ?>">
					<div><?php
						foreach ( $item['data_errors'] as $error ) {
						?>
						<span><?php echo esc_html( $error ); ?></span><br />
						<?php
						}
						?>
					</div>
				</td>
			</tr>
			<?php
		}
	}


	/**
	 *
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since 3.1.0
	 * @param string $which
	 *
	 */
	function extra_tablenav( $which )
	{
		if ( $this->user_mode && 'top' == $which ) {

			$base = '?';
			$views = array();

			$current_page = isset( $_GET['paged'] ) ? $_GET['paged'] : 1;
			$collapse = isset( $_GET['collapse'] ) ? $_GET['collapse'] : 1;

			if ( $collapse == 1 ) {
				$arr_params = array ( 'paged' => $current_page, 'collapse' => 0 );
				$views = array(
					'expand'	=> sprintf( '<a href="%s" class="expand_table">%s</a>', esc_url( add_query_arg( $arr_params, $base ) ), esc_html__('Expand Table', 'csv2wpec-coupon') ),
				);
			}
			else {
				$arr_params = array ( 'paged' => $current_page, 'collapse' => 1 );
				$views = array(
					'collapse'	=> sprintf( '<a href="%s" class="expand_table">%s</a>', esc_url( add_query_arg( $arr_params, $base ) ), esc_html__('Collapse table', 'csv2wpec-coupon') ),
				);
			}

			echo "<ul class='subsubsub'>\n";
			foreach ( $views as $class => $view ) {
				$views[ $class ] = "\t<li class='$class'>$view";
			}
			echo implode( " |</li>\n", $views ) . "</li>\n";
			echo "</ul>";
		}
	}


	/**
	 *
	 * Overrides WP_List_Table::current_action
	 * WP_List_Table key 'action' and 'action2' are replaced by 'list_action'
	 * and 'list_action2' respectively, to prevent the WP ajax action hooks
	 * being overriden in $_GET
	 *
	 * @return string|bool The action name or False if no action was selected
	 *
	 */
	function current_action()
	{
		if ( isset( $_REQUEST['list_action'] ) && -1 != $_REQUEST['list_action'] )
			return $_REQUEST['list_action'];

		if ( isset( $_REQUEST['list_action2'] ) && -1 != $_REQUEST['list_action2'] )
			return $_REQUEST['list_action2'];

		return false;
	}


	/**
	 *
	 * Overrrides WP_List_Table::no_items
	 * The Message to be displayed when there are no items in the table
	 *
	 * @since 3.1.0
	 * @access public
	 *
	 */
	function no_items()
	{
		esc_html_e( 'No Coupons found', 'csv2wpec-coupon');
	}

}