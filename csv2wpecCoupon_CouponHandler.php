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


include_once('csv2wpecCoupon_DataHandler.php');
include_once('csv2wpecCoupon_CouponError.php');
include_once('csv2wpecCoupon_CouponListTable.php');


class csv2wpecCoupon_CouponHandler extends csv2wpecCoupon_DataHandler
{

	public function __construct( $slug )
	{
		parent::__construct();

		$this->dataSlug = 'coupon';
		if ( isset( $slug ) ) {
			$slug .= "_" . $this->dataSlug;
			$this->dataSlug =  $slug;
		}

		$this->errorHandler = new csv2wpecCoupon_CouponError( 'csv2wpec-coupon' );
	 	$this->primaryKey = 'Coupon Code';
	}


	/**
	 *
	 * READ FROM DATABASE
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Extract all records from the Coupon DB table
	 * Called when saving data to file
	 *
	 * @return ARRAY_A Query results
	 *
	 */
	public function FillFromDBTable()
	{
		global $wpdb;
		$tablename = $this->wpsc_coupon_table( $wpdb->prefix );
		$sql = "SELECT `coupon_code`,
					   `value`,
					   `is-percentage` as is_percentage,
					   `use-once` as use_once,
					   `active`,
					   `every_product`,
					   `start`,
					   `expiry`,
					   `condition`,
					   `id`
				FROM $tablename
				ORDER BY `id` ASC";
		return $wpdb->get_results( $sql, ARRAY_A );
	}


	/**
	 *
	 * READ FROM FILE
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * VALIDATE DATA
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Single Data Record Validation
	 *
	 * @param array $CsvData Data record (by reference)
	 *
	 */
	protected function validateDataRecord( &$CsvData )
	{
        $coupon_data = array();

        $pID = $CsvData['record_id'];
        $is_new_coupon = ( $pID === 0 );
		if ( $is_new_coupon )
			$coupon_data = $this->DefaultData();

		// purge empty entries
		$this->purgeEntries( $CsvData );
		// set data for ListaTable display
		$coupon_data['raw_data'] = $CsvData;
		// register invalid field errors
		$coupon_data['validating_field_errors'] = array();

		// Tracking errors
		// Triggered when data records are merged, in order to retrieve the error line number.
		// Tracking is not necessary for single data record, because the line number of the
		// record is displayed in the ListTable
		$track_error = isset( $CsvData['csv_data'] );


														$coupon_data['record_id'] 		= $pID;
														$coupon_data['coupon_code'] 	= (string)$CsvData['coupon_code'];

		if ( isset( $CsvData['value'] ) ) {
			if ( is_numeric( $CsvData['value'] ) )
				$coupon_data['value'] = (float)$CsvData['value'];
			else {
				$linenum = ( $track_error ) ? $this->TrackValidationError( 'value', $CsvData['value'], $CsvData['csv_data'] ) : '';
				$this->errorHandler->AddError( 'invalide_discount_value', array( $this->MapIndexToHeader( 'value' ), '' ), $linenum );
				$coupon_data['validating_field_errors']['value'] = 1;
			}
		}

		// valid values: integer number (string): 0 (= Fixed Amount), 1 (= Percentage), 2 (= Free Shipping) (default: 0)
		if ( isset( $CsvData['is_percentage'] ) ) {
			$discount_type = strtolower( $CsvData['is_percentage'] );
			switch ( $discount_type ) {
				case '0':
				case 'fixed amount':
					$coupon_data['is_percentage'] = 0;
					break;
				case '1':
				case 'percentage':
					$coupon_data['is_percentage'] = 1;
					break;
				case '2':
				case 'free shipping':
					$coupon_data['is_percentage'] = 2;
					break;
				default:
					$linenum = ( $track_error ) ? $this->TrackValidationError( 'is_percentage', $CsvData['is_percentage'], $CsvData['csv_data'] ) : '';
					$this->errorHandler->AddError( 'invalide_discount_type', array( $this->MapIndexToHeader( 'is_percentage' ), '' ), $linenum );
					$coupon_data['validating_field_errors']['is_percentage'] = 1;
			}
		}

		if ( isset( $CsvData['use_once'] ) ) {
			if ( $this->is_boolean( $CsvData['use_once'] ) )
				$coupon_data['use_once'] = (int)(bool)$CsvData['use_once'];
			else {
				$linenum = ( $track_error ) ? $this->TrackValidationError( 'use_once', $CsvData['use_once'], $CsvData['csv_data'] ) : '';
				$this->errorHandler->AddError( 'invalide_use_once_value', array( $this->MapIndexToHeader( 'use_once' ), '' ), $linenum );
				$coupon_data['validating_field_errors']['use_once'] = 1;
			}
		}

		if ( isset( $CsvData['active'] ) ) {
			if ( $this->is_boolean( $CsvData['active'] ) )
				$coupon_data['active'] = (int)(bool)$CsvData['active'];
			else {
				$linenum = ( $track_error ) ? $this->TrackValidationError( 'active', $CsvData['active'], $CsvData['csv_data'] ) : '';
				$this->errorHandler->AddError( 'invalide_active_value', array( $this->MapIndexToHeader( 'active' ), '' ), $linenum );
				$coupon_data['validating_field_errors']['active'] = 1;
			}
		}

		if ( isset( $CsvData['every_product'] ) ) {
			if ( $this->is_boolean( $CsvData['every_product'] ) )
				$coupon_data['every_product'] = (int)(bool)$CsvData['every_product'];
			else {
				$linenum = ( $track_error ) ? $this->TrackValidationError( 'every_product', $CsvData['every_product'], $CsvData['csv_data'] ) : '';
				$this->errorHandler->AddError( 'invalide_every_product_value', array( $this->MapIndexToHeader( 'every_product' ), '' ), $linenum );
				$coupon_data['validating_field_errors']['every_product'] = 1;
			}
		}

		if ( isset( $CsvData['validating_result'] ) )	$coupon_data['validating_result'] = $CsvData['validating_result'];
		if ( isset( $CsvData['linenum'] ) )				$coupon_data['linenum']          = (int)$CsvData['linenum'];

		// start date
		if ( $is_new_coupon && ( !isset( $CsvData['start'] ) || empty( $CsvData['start'] ) ) ) {
			$linenum = ( $track_error ) ? $this->TrackValidationError( 'start', '', $CsvData['csv_data'] ) : '';
			$this->errorHandler->AddError( 'missing_start_date', array( $this->MapIndexToHeader( 'start' ), '' ), $linenum );
			$coupon_data['validating_field_errors']['start'] = 1;
		}
		if ( isset ( $CsvData['start'] ) ) {
			$coupon_data['start'] = $this->ValidDateTime( $CsvData['start'] );
			if ( !isset( $coupon_data['start'] ) ) {
				$linenum = ( $track_error ) ? $this->TrackValidationError( 'start', $CsvData['start'], $CsvData['csv_data'] ) : '';
				$this->errorHandler->AddError( 'invalid_start_date', array( $this->MapIndexToHeader( 'start' ), $CsvData['start'] ), $linenum );
				$coupon_data['validating_field_errors']['start'] = 1;
			}
		}

		// expiration date
		if ( $is_new_coupon && ( !isset( $CsvData['expiry'] ) || empty( $CsvData ) ) ) {
			$linenum = ( $track_error ) ? $this->TrackValidationError( 'expiry', '', $CsvData['csv_data'] ) : '';
			$this->errorHandler->AddError( 'missing_expiry_date', array( $this->MapIndexToHeader( 'expiry' ), '' ), $linenum );
			$coupon_data['validating_field_errors']['expiry'] = 1;
		}
		if ( isset ( $CsvData['expiry'] ) ) {
			$coupon_data['expiry'] = $this->ValidDateTime( $CsvData['expiry'] );
			if ( !isset( $coupon_data['expiry'] ) ) {
				$linenum = ( $track_error ) ? $this->TrackValidationError( 'expiry', $CsvData['expiry'], $CsvData['csv_data'] ) : '';
				$this->errorHandler->AddError( 'invalid_expiry_date', array( $this->MapIndexToHeader( 'expiry' ), $CsvData['expiry'] ), $linenum );
				$coupon_data['validating_field_errors']['expiry'] = 1;
			}
		}

		/*
		// check only date format, not logic
		{
			$today_timestamp = strtotime( date("Y-m-d H:i:s") );
			$start__timestamp = strtotime( $coupon_data['start'] );
			$expiry__timestamp = strtotime( $coupon_data['expiry'] );
			if ( $expiry__timestamp < $start__timestamp  ) {
				$linenum = ( $track_error ) ? $this->TrackValidationError( 'expiry', $CsvData['expiry'], $CsvData['csv_data'] ) : '';
				$this->errorHandler->AddError( 'expiry_lt_start_error', array( $this->MapIndexToHeader( 'expiry' ), $CsvData['expiry'] ), $linenum );
				$coupon_data['validating_field_errors']['expiry'] = 1;
			}
			if ( $expiry__timestamp < $today_timestamp  ) {
				$linenum = ( $track_error ) ? $this->TrackValidationError( 'expiry', $CsvData['expiry'], $CsvData['csv_data'] ) : '';
				$this->errorHandler->AddError( 'expiry_lt_today_error', array( $this->MapIndexToHeader( 'expiry' ), $CsvData['expiry'] ), $linenum );
				$coupon_data['validating_field_errors']['expiry'] = 1;
			}
		}
		*/

		if ( isset( $CsvData['condition'] ) ) {

			$rules_in = array();
			$rules_out = array();

			//
			// Syntax:
			// 	property:logic:value|property:logic:value|...|property:logic:value ( WPec < 3.8.11 )
			//  [operator]:property:logic:value|operator:property:logic:value|...|operator:property:logic:value ( WPec >= 3.8.11 )
			//

			if ( $this->isBooleanOperator() ) // WPec >= 3.8.11
				$key_rules = array( 'operator', 'property', 'logic', 'value' );
			else // WPec < 3.8.11
				$key_rules = array( 'property', 'logic', 'value' );
			$num_rules = count( $key_rules );

			$operators = array( 'and', 'or' );
			$ruleprops = array( 'item_name', 'item_quantity', 'total_quantity', 'subtotal_amount' );
			$rulelogic = array( 'equal', 'greater', 'less', 'contains', 'not_contain', 'begins', 'ends', 'category' );

			$conditions = $this->csv_explode( $CsvData['condition'], self::value_delimiter );

			foreach ( $conditions as $m_condition ) {

				$m_condition = trim( $m_condition );

				if ( empty ( $m_condition ) ) {
					// skip empty conditions
				}

				// remove all conditions found until now, including these in the database
				elseif ( $m_condition == self::delete_all ) {
					$coupon_data['clear_conditions'] = true; // used in update to reset databsse conditions
					$rules_in = array(); 	// skip all previous in entries
					$rules_out = array(); 	// skip all previous out entries

				} else {

					// lowercase all items but the last one
					$condition = $this->tolowerCondition( $m_condition, self::props_delimiter );

					// explode props
					$props = $this->csv_explode( $condition, self::props_delimiter );

					// remove delete marker
					$remove_item = $this->CheckDeleteMarker( $props[0] );

					$er_conditions = array();
					$num_props = count( $props );

                    // simplified syntax for conditions
					if ( $num_props == 1 ) {
						$data = $this->rule_simple_sintax( $props[0] );
						$props = $data['rule'];
						if ( !empty( $data['error'] ) )
							$er_conditions[] = $data['error'];
                        $num_props = count( $props );
					}
					elseif ( $num_props == 2 ) {
						$operator = $props[0];
						$data = $this->rule_simple_sintax( $props[1] );
						$props = $data['rule'];
						array_unshift( $props, $operator );
						if ( !empty( $data['error'] ) )
							$er_conditions[] = $data['error'];
                        $num_props = count( $props );
					}

					if ( empty( $er_conditions ) ) {

						$k = 0;

						// WPec >= 3.8.11
						if ( $num_rules == 4 ) {
							if ( $num_props == ($num_rules - 1) )
								array_unshift( $props, $operators[0] );
							elseif ( empty( $props[$k] ) )
								$props[$k] = $operators[0];
							elseif ( !in_array( $props[$k], $operators ) )
								$er_conditions[] = 'invalid_operator_condition';
							if ( count( $props ) != $num_rules )
								$er_conditions[] = 'invalid_extnumber_of_items';
							$k++;
						}
						// WPec < 3.8.11
						elseif ( $num_props > 3 ) {
							// remove the first one only if the operator is '' or 'and'
							// ('and' is the default operator in WPec < 3.8.11)
							if ( $num_props == 4 && ( $props[$k] == '' || $props[$k] == 'and' ) )
								array_shift( $props );
							if ( count( $props ) != 3 )
								$er_conditions[] = 'invalid_number_of_items';
						}

						if ( empty( $er_conditions ) ) {
							if ( !in_array( $props[$k++], $ruleprops ) ) $er_conditions[] = 'invalid_prop_condition';
							if ( !in_array( $props[$k++], $rulelogic ) ) $er_conditions[] = 'invalid_logic_condition';
							if ( empty( $props[$k] ) && !is_numeric( $props[$k] ) ) $er_conditions[] = 'empty_value_condition';
						}

					}

					if ( empty( $er_conditions ) ) {
						if ( $remove_item )
							$rules_out[] = array_combine( $key_rules, $props );
						else
							$rules_in[] = array_combine( $key_rules, $props );
					}
					else {
						foreach ( $er_conditions as $er_code ) {

							$m_condition = implode ( ":", $props );

							$linenum = ( $track_error ) ? $this->TrackValidationError( 'condition', $m_condition, $CsvData['csv_data'] ) : '';
							$this->errorHandler->AddError( $er_code, array( $this->MapIndexToHeader( 'condition' ), $condition ), $linenum );
							$coupon_data['validating_field_errors']['condition'] = 1;
						}
					}

				}
			}

			$rules_overlapped = $this->array_intersection( $rules_in, $rules_out );
			if ( !empty( $rules_in ) )
				$coupon_data['condition_in'] = $this->array_difference( $rules_in, $rules_overlapped );
			if ( !empty( $rules_out ) )
				$coupon_data['condition_out'] = $this->array_difference( $rules_out, $rules_overlapped );
		}

		// set to true if there are no errors in data validation
		$coupon_data['valid_data'] = !$this->errorHandler->IsError();

		$CsvData = $coupon_data;
	}



	/**
	 *
	 * Simplified sintax for rules
	 *
	 * @param string $condition Condition in simplified format
	 * @return array ( Conditions in standard format, Error	)
	 *
	 */
	function rule_simple_sintax( $condition ) {

		$error = '';

		if ( ( $pos = strpos( $condition, "=" ) ) !== false )
			$logic = 'equal';
		elseif ( ( $pos = strpos( $condition, ">" ) ) !== false )
			$logic = 'greater';
		elseif ( ( $pos = strpos( $condition, "<" ) ) !== false )
			$logic = 'less';
		else
			$logic = '';

		if ( !empty( $logic ) ) {

			$property = strtolower( trim( substr( $condition, 0, $pos ) ) );
			$value = trim( substr( $condition, $pos+1 ) );

			if ( $property == 'quantity' )
				$property = 'item_quantity';
			elseif ( $property == 'total' )
				$property = 'total_quantity';
			elseif ( $property == 'subtotal' )
				$property = 'subtotal_amount';
			else
				$property = '';

			if ( !empty( $property ) ) {
				if ( !is_numeric( $value ) )
					$error = 'ss_non_numeric_value';
			}
			else {
				$error = 'ss_undefined_property';
			}

		}
		else {

			$property = 'item_name';
			$value = '';

			$items = explode( "*", $condition );
			$num_items = count( $items );

			switch ( $num_items ) {

				case 1: // equal, category
					if ( ( $pos = strpos( $items[0], "#" ) ) === 0 ) {
						$logic = 'category';
						$value = trim( substr( $items[0], 1 ) );
					}
					else {
						$logic = 'equal';
						$value = $items[0];
					}
					break;

				case 2: // begins, ends
					$i0 = trim( $items[0] );
					$i1 = trim( $items[1] );
					if ( empty( $i0 ) ) {
						$logic = 'ends';
						$value = $items[1];
					} elseif ( empty( $i1 ) ) {
						$logic = 'begins';
						$value = $items[0];
					}
					break;

				case 3: // contains, not_contain
					$i0 = trim( $items[0] );
					$i2 = trim( $items[2] );
					if ( empty( $i0 ) ) {
						if ( empty( $i2 ) ) {
							$logic = 'contains';
							$value = $items[1];
						}
					}
					elseif ( $items[0] == '!' ) {
						if ( empty( $i2 ) ) {
							$logic = 'not_contain';
							$value = $items[1];
						}
					}
					break;
			}

			if ( !empty( $logic ) ) {
				if ( empty( $value ) )
					$error = 'ss_undefined_value';
			}
			else {
				$error = 'ss_undefined_logic';
			}

		}

		$props = array( $property, $logic, $value );
		return array( 'rule' => $props, 'error' => $error );
	}


	/**
	 *
	 * Extract Coupon ids and codes from the database
	 *
	 * @return ARRAY_A Query results
	 *
	 */
	protected function recordList()
	{
		global $wpdb;
		$tablename = $this->wpsc_coupon_table( $wpdb->prefix );
		$sql = "SELECT id, coupon_code as code FROM $tablename";
		return $wpdb->get_results( $sql, ARRAY_A );
	}


	/**
	 *
	 * Remove empty items from a data record
	 *
	 * @param array $CsvData Data record (by reference)
	 *
	 */
	protected function purgeEntries( &$CsvData )
	{
		foreach ( $CsvData as $key => $value ) {
			switch ( $key ) {

				case 'value':
				case 'is_percentage':
				case 'use_once':
				case 'active':
				case 'every_product':
					if ( empty( $value ) && !is_numeric( $value ) )
						unset( $CsvData[$key] );
					break;

				case 'start':
				case 'expiry':
				case 'condition':
					if ( empty( $value ) )
						unset( $CsvData[$key] );
					break;
			}
		}
	}


	/**
	 *
	 * SAVE TO CSV FILE
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Prepare data for writing to a file
	 *
	 * @param array $coupon Data record
	 * @param array $CsvData Formatted data record (by reference)
	 *
	 */
	protected function CsvFormatDataItem( $coupon, &$CsvData )
	{
		$CsvData['Coupon Code'] = $coupon['coupon_code'];
		$CsvData['Discount'] = $coupon['value'];
		$CsvData['Discount Type'] = $coupon['is_percentage'];
		$CsvData['Start'] = $coupon['start'];
		$CsvData['Expiry'] = $coupon['expiry'];
		$CsvData['Active'] = $coupon['active'];
		$CsvData['Use Once'] = $coupon['use_once'];
		$CsvData['Every Product'] = $coupon['every_product'];
		$CsvData['Rules'] = $this->pack_multivalue( $coupon['condition'] );
		// $CsvData['Coupon ID'] = $coupon['id'];
	}


	/**
	 *
	 * SAVE TO DATABASE
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Coupon Data Record Processing
	 * Depending on the ID, a Coupon is
	 *    deleted (id < 0)
	 *    updated (id > 0)
	 *    created (id = 0)
	 *
	 * @param array $CsvData Data record
	 * @return array $status Results of data processing
	 *
	 */
	public function InsertDataRecordIntoDB( $CsvData )
	{
		global $wpdb;
		$this->errorHandler->Reset();

		$pKEY = $this->MapHeaderToIndex( $this->primaryKey );
		$status = array( 'code' => $CsvData[$pKEY] );

		if ( isset( $CsvData['validating_result'] ) && $CsvData['validating_result'] == 'skip_processing' )
			return array( 'processing_result' => 'not_processed' );
		if ( !isset( $CsvData['record_id'] ) || ( empty( $CsvData['record_id'] ) && !is_numeric( $CsvData['record_id'] ) ) )
			return array( 'processing_result' => 'not_processed' );

		$pID = $CsvData['record_id'];
		$tablename = $this->wpsc_coupon_table( $wpdb->prefix );

		if ( $pID < 0 ) { // delete

			$sql = "DELETE FROM $tablename WHERE id = %d LIMIT 1";
			$deleted = $wpdb->query( $wpdb->prepare( $sql, absint( $pID ) ) );
			if ( !$deleted ) {
				$this->errorHandler->AddError( 'error_deleting_coupon' );
			}
			else {
				$status['record_id'] = '';
				$status['processing_result'] = 'deleted';
			}
		}

		elseif ( $pID > 0 ) { // update

			$updated = false;
			$sql = "SELECT `coupon_code`, `value`, `is-percentage`, `use-once`, `active`, `every_product`, `start`, `expiry`, `condition` FROM $tablename WHERE `id` = %d LIMIT 1;";
			$check_values = $wpdb->get_row( $wpdb->prepare( $sql, $pID ), ARRAY_A );

			if ( $check_values != null ) {

				$conditions = array();
				if ( !$this->IsNullOrEmptyString( $check_values['condition'] ) ) {

					$conditions = unserialize( $check_values['condition'] );

					// WPec >= 3.8.11
					if ( $this->isBooleanOperator() ) {
						foreach ( $conditions as $key => $condition ) {
							if ( $condition['operator'] == '' )
								$conditions[$key]['operator'] = 'and';
						}
					}
				}

				$update_conditions = false;

				if ( !empty( $conditions ) ) {
					if ( isset( $CsvData['clear_conditions'] ) ) {
						$conditions = array();
						$update_conditions = true;
					}
					elseif ( !empty( $CsvData['condition_out'] ) ) {
						$conditions = $this->array_difference( $conditions, $CsvData['condition_out'] );
						$update_conditions = true;
					}
				}

				if ( !empty( $CsvData['condition_in'] ) ) {
					$conditions = $this->array_fusion( $conditions, $CsvData['condition_in'] );
					$update_conditions = true;
				}

				if ( $update_conditions )
				{
					if ( !empty( $conditions ) ) {

						// WPec >= 3.8.11
						// remove operator from the first item
						if ( $this->isBooleanOperator() )
							$conditions[0]['operator'] = '';

						$CsvData['condition'] = serialize( $conditions );
					}
					else
						$CsvData['condition'] = '';
				}

				if ( isset( $CsvData['is_percentage'] ) ) {
					$CsvData['is-percentage'] = $CsvData['is_percentage'];
					unset( $CsvData['is_percentage'] );
				}
				if ( isset( $CsvData['use_once'] ) ) {
					$CsvData['use-once'] = $CsvData['use_once'];
					unset( $CsvData['use_once'] );
				}

				$insert_array = array();
				foreach ( $CsvData as $key => $value ) {
					if ( isset( $check_values[$key] ) && $value != $check_values[$key] )
						$insert_array[] = "`$key` = '$value'";
				}

				if ( count( $insert_array ) > 0 ) {
					$sql = "UPDATE $tablename SET " . implode( ", ", $insert_array ) . " WHERE `id` = %d LIMIT 1";
					$updated = $wpdb->query( $wpdb->prepare( $sql, $pID ) );
				}
				else
					$updated = true;
			}

			if ( !$updated )
				$this->errorHandler->AddError( 'error_updating_coupon' );
			else
				$status['processing_result'] = 'updated';
		}

		else {  // insert

			if ( isset( $CsvData['condition_in'] ) ) {

				// WPec >= 3.8.11
				if ( $this->isBooleanOperator() )
					$CsvData['condition_in'][0]['operator'] = '';

				$conditions = serialize( $CsvData['condition_in'] );
			}
			else
				$conditions = '';

			$inserted = $wpdb->insert( $tablename,
									   array( 'coupon_code' => 		$CsvData['coupon_code'],
							  				  'value' => 			$CsvData['value'],
							  				  'is-percentage' => 	$CsvData['is_percentage'],
							  				  'use-once' =>			$CsvData['use_once'],
							  				  'active' => 			$CsvData['active'],
							  				  'every_product' =>	$CsvData['every_product'],
							  				  'start' =>			$CsvData['start'],
							  				  'expiry' =>			$CsvData['expiry'],
							  				  'condition' =>		$conditions
  											),
									   array( '%s', '%f', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ) );

			if ( !$inserted )
				$this->errorHandler->AddError( 'error_inserting_coupon' );
			else {
				$status['record_id'] = $wpdb->insert_id; // the ID generated for the AUTO_INCREMENT column
				$status['processing_result'] = 'inserted';
			}
		}

		$status['processing_errors'] = $this->errorHandler->GetErrors();
		return $status;
	}


	/**
	 *
	 * DISPLAY DATA
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Display Coupon Data in a WP ListTable
	 *
	 * @param string $list_slug ListTable name
	 * @return string $contents Html content of the output table
	 *
	 */
	public function Display( $list_slug )
	{
 		if ( !isset( $this->listTable ) )
 			$this->listTable = new csv2wpecCoupon_CouponListTable( $list_slug );

		return parent::Display( $list_slug );
	}


	/**
	 *
	 * COUPON ARRAYS
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Relationship between the field names in the header line of the CSV file
	 * (external item names) and the key names of the associative array used to
	 * store and manipolate data (external item names)
	 *
	 * @return array - Internal vs external item names
	 *
	 */
	protected function CsvMap()
	{
    	return array ( 'Coupon Code' =>		'coupon_code',
					   'Discount' =>		'value',
					   'Discount Type' =>	'is_percentage',
					   'Use Once' =>		'use_once',
					   'Active' =>			'active',
					   'Every Product' =>	'every_product',
					   'Start' =>			'start',
					   'Expiry' =>			'expiry',
					   'Rules' =>			'condition',
					   'Coupon ID' =>		'record_id'
					 );
	}


	/**
	 *
	 * Set default values when adding a new coupon
	 *
	 * @return array - Default field values
	 *
	 */
	private function DefaultData()
	{
		return array ( 'coupon_code' => 	'',
					   'value' =>			0.00,
					   'is_percentage' => 	0,
					   'use_once' =>		0,
					   'active' =>			1,
					   'every_product' => 	0,
					   'start' =>			NULL, // force to define
					   'expiry' =>			NULL, // force to define
					   'condition' =>		''
					);
	}


	/**
	 *
	 * Return array keys of Coupon multi-valued fields
	 *
	 * @return array Keys of multi-valued fields
	 *
	 */
	protected function MultiValueFields()
	{
		return array ( 'condition' );
	}


	/**
	 *
	 * UTILS
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Get the name of Coupon database table
	 *
	 * @param string $prefix WP table prefix
	 * @return string Name of Coupon database table
	 *
	 */
	protected function wpsc_coupon_table( $prefix ) { return ( $prefix . 'wpsc_coupon_codes' ); }


	/**
	 *
	 * Validate DateTime input from 'Start' and Expiry' fields
	 *
	 * @param string $CsvDateTime - DateTime to validate
	 * @return string $dateTime|NULL - DateTime if valid else NULL
	 *
	 */
	function ValidDateTime( $CsvDateTime )
	{
		$CsvDateTime = trim( $CsvDateTime );
		if ( !empty( $CsvDateTime ) ) {

			$mdate = explode( ' ', trim( $CsvDateTime ) );
			$nTokens = count( $mdate );

			if ( $nTokens > 0 )
			{
				$dateTime = $mdate[0] . ' ' . ( ( $nTokens == 1 ) ? '00:00:00' : $mdate[1] );

				if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $dateTime, $matches)) {
					if ( checkdate($matches[2], $matches[3], $matches[1]) )
						return $dateTime;
				}
			}
		}
	}


	/**
	 *
	 * Starting from WPeC version 3.8.11 you can choose either AND or OR operator for coupon conditions.
	 * Test WPeC version to enable the operator term in coupon conditions
	 *
	 * @return boolen - True if wpsc version is greater or equal to '3.8.11'
	 *
	 */
	private function isBooleanOperator()
	{
		return $this->isWpscVersionGreaterEqualThan( '3.8.11' );
	}

	private function isWpscVersionGreaterEqualThan( $version )
	{
		$wpsc_version = get_option( 'wpsc_version' );
		return  ( $wpsc_version && version_compare( $wpsc_version, $version ) >= 0 );
	}


	/**
	 *
	 * Lowercase all the items of a conditions but the last one
	 *
	 * @param string $s Condition
	 * @param string $token Item delimiter
	 # @return string The condition in lowercase (but the last item)
	 *
	 */
	private function tolowerCondition( $s, $token )
	{
		$pos = strrpos( $s, $token );
		if ( $pos !== false )
			return ( strtolower( substr( $s, 0, $pos ) ) . substr( $s, $pos ) );
		return $s;
	}

}
