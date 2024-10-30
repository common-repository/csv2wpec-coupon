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


include_once( 'csv2wpecCoupon_ErrorHandler.php' );


class csv2wpecCoupon_CouponError extends csv2wpecCoupon_ErrorHandler {

	function __construct( $localName ) {
		parent::__construct( $localName );
	}


	/**
	 *
	 * Initialize the Coupon error messages
	 *
	 * @return array Coupon error message strings
	 *
	 */
	protected function InitErrorMessages() {

		return array (

		// reading errors

			'undefined_filename' =>								__( 'Undefined file name', $this->localName ),
			'file_not_found' =>									__( 'File not found. Please make sure the file exists and is a regular file', $this->localName ),
			'file_type_error' =>								__( 'Invalid file type', $this->localName ),
			'file_reading_error' =>								__( 'Errors occurred while reading the file', $this->localName ),
			'csv_file_not_selected' => 							__( 'No CSV file has been selected. Please select a file to import', $this->localName ),
			'csv_file_open_failure' => 							__( 'Can not open CSV file. Please check if the file exists and if you have permission to open it', $this->localName ),
			'csv_wrong_field_name' =>							__( '[line %d] Wrong field name [%s]', $this->localName ),
			'key_field_not_found' =>							__( '[line %d] Key field "%s" not found', $this->localName ),
			'csv_skip_lines' =>									__( '[lines %d-%d] Lines skipped', $this->localName ),
			'csv_skip_line' =>									__( '[line %d] Line skipped', $this->localName ),
			'csv_see_documentation' => 							__( 'Please see the documentation for details', $this->localName ),

		// validating errors

			'primary_key_not_defined' => 						'[%s] ' . __( 'Key field not defined in the header line %s of CSV file', $this->localName ),
			'record_not_identified' => 							'[%s] ' . __( 'Code "%s" at line number %s cannot identify a unique coupon. Please see the documentation for details', $this->localName ),
			'invalide_discount_value' =>						'[%s] ' . __( 'Invalid value. Floating number expected.', $this->localName ),
			'invalide_use_once_value' =>						'[%s] ' . __( 'Invalid value. Boolean number expected: { 0, 1 }', $this->localName ),
			'invalide_active_value' =>							'[%s] ' . __( 'Invalid value. Boolean number expected: { 0, 1 }', $this->localName ),
			'invalide_every_product_value' =>					'[%s] ' . __( 'Invalid value. Boolean number expected: { 0, 1 }', $this->localName ),
			'invalide_discount_type' =>							'[%s] ' . __( 'Invalid value. Integer number expected: { 0, 1, 2}', $this->localName ),
			'missing_start_date' =>								'[%s] ' . __( 'Start Date is required', $this->localName ),
			'missing_expiry_date' => 							'[%s] ' . __( 'Expiration Date is required', $this->localName ),
			'invalid_start_date' => 							'[%s] ' . __( 'Invalid Start Date. Please use DATE format "YYYY-MM-DD" (ex: 2013-05-20) or DATETIME format: "YYYY-MM-DD HH:MM:SS" (ex: 2013-05-20 00:00:00)', $this->localName ),
			'invalid_expiry_date' => 							'[%s] ' . __( 'Invalid Expiration Date. Please use DATE format "YYYY-MM-DD" (ex: 2013-05-20) or DATETIME format: "YYYY-MM-DD HH:MM:SS" (ex: 2013-05-20 00:00:00)', $this->localName ),
			'expiry_lt_start_error' => 							'[%s] ' . __( 'Expiration Date cannot be less than Start date', $this->localName ),
			'expiry_lt_today_error' => 							'[%s] ' . __( 'Expiration Date cannot be less than the Current date', $this->localName ),
			'missing_item_in_condition' => 						'[%s] ' . __( 'Missing item in Condition: "%s". Valid condition format is "property:logic:value"' , $this->localName),
			'invalid_extnumber_of_items' =>						'[%s] ' . __( 'Too many items in Condition: "%s". Valid condition format is "operator:property:logic:value"', $this->localName),
			'invalid_number_of_items' =>						'[%s] ' . __( 'Too many items in Condition: "%s". Valid condition format is "property:logic:value"', $this->localName),
			'invalid_operator_condition' =>						'[%s] ' . __( 'Invalid operator in Condition: "%s". Valid operators are: "AND", "OR"', $this->localName),
			'invalid_prop_condition' => 						'[%s] ' . __( 'Invalid property term in Condition: "%s". Valid property terms are: "item_name", "item_quantity", "total_quantity", "subtotal_amount"', $this->localName),
			'invalid_logic_condition' => 						'[%s] ' . __( 'Invalid logic term in Condition: "%s". Valid logic terms are: "equal", "greater", "less", "contains", "not_contain", "begins", "ends", "category"', $this->localName),
			'empty_value_condition' => 							'[%s] ' . __( 'Value term not defined in Condition: "%s"', $this->localName),

			// simplified syntax v.1.1
			'ss_non_numeric_value' => 							'[%s] ' . __( 'A numeric value is required in condition: "%s"', $this->localName),
			'ss_undefined_property' => 							'[%s] ' . __( 'Undefined or invalid property in condition: "%s". Valid properties are: "quantity", "total", "subtotal"', $this->localName),
			'ss_undefined_value' => 							'[%s] ' . __( 'Undefined value in condition: "%s". Please see the documentation for details', $this->localName),
			'ss_undefined_logic' => 							'[%s] ' . __( 'Undefined or invalid logic in condition: "%s". Please see the documentation for details', $this->localName),

		// processing errors

			'error_deleting_coupon' => 							__( 'Error deleting coupon', $this->localName ),
			'error_updating_coupon' => 							__( 'Error updating coupon', $this->localName ),
			'error_inserting_coupon' => 						__( 'Error adding new coupon', $this->localName ),
		);
	}
}

