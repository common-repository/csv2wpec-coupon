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


class csv2wpecCoupon_DataHandler {

	protected $dataStore;
	protected $dataError;
	protected $dataHeader;		// Header fields in data file

	protected $errorHandler;

	protected $listTable;

	protected $HeaderToIndex;	// Map header fields onto indexes of data structure
	protected $IndexToHeader;	// Map indexes of data structure onto header fields

	protected $CList;			// Code list of database records
	protected $RList;			// Full record list

	protected $dataSlug;		// Override in derived class
	protected $primaryKey;		// Override in derived class

	// constants to handle multi-valued fields
	const delete_all = "!-*";
	const delete_single = "!-";
	const value_delimiter = "|";
	const props_delimiter = ":";


	public function __construct()
	{
		$this->nullData();
	}

	public function getData()
	{
		return $this->dataStore;
	}

	public function getDataCount()
	{
		return count( $this->dataStore );
	}

	public function isDataError()
	{
		$this->errorHandler->IsError();
	}

	public function nullData()
	{
		$this->dataStore = array();
		$this->dataError = array();
		$this->dataHeader = array();
		unset($this->CList);
		unset($this->RList);
	}

	public function getDataSlug()
	{
		return $this->dataSlug;
	}

	public function getDataHeader()
	{
		return $this->dataHeader;
	}


	/**
	 *
	 * READ FROM DATABASE
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Data Query
	 *
	 * @return array Query results
	 *
	 */
	protected function FillFromDBTable()
	{
		// override method in derived extended classes
		return array();
	}


	/**
	 *
	 * READ FROM FILE
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Read import data from file
	 *
	 * @param string $fileName Filename to read
	 * @return string Description of errors occurred while reading the file
	 *
	 */
	public function FillFromFile( $fileName )
	{
		$this->nullData();
		$this->errorHandler->Reset();

		if ( empty( $fileName ) )
			$this->errorHandler->AddError( 'undefined_filename' );
		elseif ( !is_file( $fileName ) )
			$this->errorHandler->AddError( 'file_not_found' );
		else {

			$this->BuildList();

			$fileType = strtolower( pathinfo( $fileName, PATHINFO_EXTENSION ) );
			switch ( $fileType ) {
				case 'csv':
					$this->FillFromCsvFile( $fileName );
					break;
				default:
					$this->errorHandler->AddError( 'file_type_error' );
			}

			// try to remove the file from the server when done
			@unlink( $fileName );
		}

		// content for error messages occured while reading the file
		return $this->errorHandler->formatFileErrors();
	}


	/**
	 *
	 * Read import data from a CSV File.
	 * File content is stored in $dataStore array
	 *
	 * @param string $fileName Filename to read
	 * @param boolean $enable_wildcards True (default) to enable wild cards on the key field
	 * @return boolean True if an error occured while reading the file
	 *
	 */
	protected function FillFromCsvFile( $fileName, $delimiter = ',', $enable_wildcards = true )
	{
		// open csv file
		$handle = @fopen( $fileName, 'r');
		if ( !$handle ) {
			$this->errorHandler->AddError( 'csv_file_open_failure' );
			return;
		}

		// get allowed csv fields (lowercase)
		$this->MapHeaderToIndex();
		$fieldMap_Norm = array_change_key_case( $this->HeaderToIndex );
		$mHeader = array();

		// reset all reading variables
		$line_count = 0;
		$csv_fields = NULL;
		$key_pos = NULL;
		$lines_skipped = array();

		$key_field = strtolower( $this->primaryKey );
		$db_key_field = $this->MapHeaderToIndex( $this->primaryKey );

		while ( ( $data = @fgetcsv( $handle, filesize($handle), $delimiter )) !== false ) {

			$line_count++;

			$data_list = array_map( 'trim', $data );
			$data_fields = array_map( 'strtolower', $data_list );

			// Blank line
			// PHP documentation: "A blank line in a CSV file will be returned as an array comprising a single null field"
			if ( !isset($data_fields[1]) && empty($data_fields[0]) ) {

				// register skipped lines
				if ( !empty( $lines_skipped ) ) {
					$this->errorHandler->AddError( isset( $lines_skipped[1] ) ? 'csv_skip_lines' : 'csv_skip_line' , $lines_skipped );
					$lines_skipped = array();
				}

				// blank lines end data blocks
				unset($csv_fields);

			}

			// skip lines starting with the pound sign
			elseif ( !empty( $data_fields[0] ) && $data_fields[0][0] == '#' ) {
			}

			// check if a not empty line is an header line
			elseif ( in_array( $key_field, $data_fields ) ) {

				// register skipped lines
				if ( !empty( $lines_skipped ) ) {
					$this->errorHandler->AddError( isset( $lines_skipped[1] ) ? 'csv_skip_lines' : 'csv_skip_line' , $lines_skipped );
					$lines_skipped = array();
				}

				// check field names
				$csv_fields = array();
				$wrong_names = false;
                foreach ( $data_fields as $key => $field ) {
					if ( isset( $fieldMap_Norm[$field] ) )
						$csv_fields[] = $fieldMap_Norm[$field];
					else {
						// $this->errorHandler->AddError( 'csv_wrong_field_name', array( $line_count, $field ) );
						$this->errorHandler->AddError( 'csv_wrong_field_name', array( $line_count, $data_list[$key] ) );
						$wrong_names = true;
					}
				}

				if ( $wrong_names  )
					unset( $csv_fields );
				else {
					// merge the header fields (used in ListTable)
					if ( empty( $mHeader )  )
						$mHeader = array( $this->primaryKey );
					$mHeader = $this->array_fusion( $mHeader, $data_list );
					$key_pos = array_search( $key_field, $data_fields );
				}

			}

			// header not found: skip reading data
			elseif ( !isset( $csv_fields ) ) {

				if ( !isset( $lines_skipped[0] ) ) $lines_skipped[0] = $line_count;
				else $lines_skipped[1] = $line_count;

			}

			// read data
			else {

				// $data = array_map( 'htmlentities', $data );
				// $data = array_map( 'esc_attr', $data );

				$key_value = $data[$key_pos];
				$to_delete = $this->CheckDeleteMarker( $key_value );
				$code_list = array();

				// search for a wildcards
				if ( $enable_wildcards &&
					 !empty( $key_value ) &&
					 ( strpos( $key_value, '?' ) !== false || strpos( $key_value, '*' ) !== false ) ) {
						foreach ( $this->RList as $key => $m_record ) {
							$db_key = $m_record['code'];
							if ( !empty( $db_key ) && $this->wild_compare( $key_value, $db_key ) )
								$code_list[] = $this->RList[$key];
						}
				}
				else
					$code_list[] = array( 'id' => '', 'code' => $key_value );

				foreach ( $code_list as $key => $m_record ) {

					// update the code record list ( new record not in data list)
					$code = $m_record['code'];
					$id = $m_record['id'];
					if ( empty( $id ) && !isset( $this->dataStore['csv_data'][$code] ) )
						$this->RList[] = $m_record;

					// update the data list
					$data_array = $this->array_combine_special( $csv_fields, $data );
					$data_array[$db_key_field] = $code;
					$data_array['record_id'] = $id;
					$data_array['delete_record'] = $to_delete;
					$data_array['linenum'] = $line_count; // add line number
					$this->dataStore['csv_data'][$code][] = $data_array;
				}

			}

		} // end of while (@fgetcsv)

		// close csv file
		fclose($handle);

		// register skipped lines
		if ( !empty( $lines_skipped ) ) {
			$this->errorHandler->AddError( isset( $lines_skipped[1] ) ? 'csv_skip_lines' : 'csv_skip_line' , $lines_skipped );
			$lines_skipped = array();
		}

		// build header data
		if ( !empty( $mHeader )  ) {
			foreach ( $mHeader as $value ) {
				$index = $fieldMap_Norm[strtolower( $value )];
				$this->dataHeader[$index] = $value;
			}
		}
	}


	/**
	 *
	 * VALIDATE DATA
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * All records Validation
	 *
	 * @return boolean $valid_data True if validation is successfull
	 *
	 */
	public function validateData()
	{
		$valid_data = true;

 		if ( !empty( $this->dataStore['csv_data'] ) ) {

			$this->InitValidation();

			foreach ( $this->dataStore['csv_data'] as $key => $dataRecords ) {

				$numRecords = count( $dataRecords );
				if ( $numRecords > 0 ) {

					if ( $numRecords > 1 ) {
						// merge multi-records
						$dataItem = $this->mergeRecords( $dataRecords );
						$dataItem['csv_data'] = $dataRecords;
					}
					else {
						$dataItem = $dataRecords[0];
						$this->purgeEntries( $dataItem );
					}

					$this->errorHandler->Reset();

					$pID = $this->recordID( $dataItem );
					$dataItem['record_id'] = $pID;

					if ( isset( $pID ) ) {
						if ( $pID < 0 ) // no sanitize if we are deleting
							$dataItem['validating_result'] = 'to_delete';
						else
							$this->validateDataRecord( $dataItem );
					}

					$dataItem['validating_errors'] = $this->errorHandler->GetErrors();
					if ( isset( $dataItem['valid_data'] ) && $dataItem['valid_data'] != true )
						$valid_data = false;

					$this->dataStore[] = $dataItem;

				}
				unset( $this->dataStore['csv_data'][$key] );
			}
			unset( $this->dataStore['csv_data'] );
		}

		return $valid_data;
	}


	/**
	 *
	 * Single Record Validation
	 *
	 * @param array $CsvData Data record (by reference)
	 *
	 */
	protected function validateDataRecord( &$CsvData )
	{
		// override method in derived extended classes
	}


	/**
	 *
	 * Merge records having the same key
	 *
	 * @param array $dataRecord Records having the same key
     * @return array $output Merging result
     *
 	 */
	protected function mergeRecords( $dataRecord )
	{
		$length = count( $dataRecord ) - 1;
		$output = $dataRecord[$length];
		$linenum = $output['linenum'];
		$this->purgeEntries($output);

		$multivalues = $this->MultiValueFields();

		while ( $length-- ) {
			$elem = $dataRecord[$length];
			$this->purgeEntries( $elem );
			if ( !empty( $elem ) )
			{
				foreach ( $multivalues as $item ) {
					if ( isset( $elem[$item] ) ) {
						if ( isset( $output[$item] ) )
							$output[$item] .= '|' . $elem[$item];
						else
							$output[$item] = $elem[$item];
					}
				}

				$output += $elem;
				$linenum = $elem['linenum'] . '/' . $linenum;
			}
		}

		$output['linenum'] = $linenum;
		return $output;
	}


	/**
	 *
	 * Remove empty items from a data record
	 *
	 * @param array $dataItem Data record (by reference)
	 *
	 */
	protected function purgeEntries( &$dataItem )
	{
		// override function in derived extended classes
	}


	/**
	 *
	 * Set the ID of the current record
	 * ID < 0 when deleting the record
	 *    = 0 when adding a new record
	 *    > 0 when updating an existing record
	 *
	 * @param array $CsvData Data Record
	 * @return integer $pID|NULL Current record ID
	 *
	 */
	protected function recordID( $CsvData )
	{
		$pKEY = $this->MapHeaderToIndex( $this->primaryKey );
		if ( empty( $CsvData ) || !isset( $CsvData[$pKEY] ) ) {
			$this->errorHandler->AddError( 'primary_key_not_defined', array( $this->primaryKey, $CsvData['linenum'] ) );
			return;
		}

		$Code = (string)$CsvData[$pKEY];
		$delete_id = $CsvData['delete_record'] ? -1 : 1;
		$pID = NULL;

		if ( empty($Code) ) {
			if ( isset( $CsvData['record_id'] ) && isset( $this->CList[$CsvData['record_id']] ) )
				$pID = absint( $CsvData['record_id'] ) * $delete_id;
		}
		else {
			$IDS = array_keys ( $this->CList, $Code );
            $n = count( $IDS );
			if ( $n == 0 ) {
				if ( $delete_id === 1 ) $pID = 0; // new record
			}
			elseif ( $n == 1 ) {
                $pID = absint( $IDS[0] ) * $delete_id; // update unique record
			}
			elseif ( isset( $CsvData['record_id'] ) && in_array( $CsvData['record_id'], $IDS ) ) {
				$pID = absint( $CsvData['record_id'] ) * $delete_id; // update using record_id
			}
		}

		if ( is_null( $pID ) )
			$this->errorHandler->AddError( 'record_not_identified', array(  $this->primaryKey, $Code, $CsvData['linenum'] ) );

		return $pID;
	}


	/**
	 *
	 * Called before data validation starts
	 *
	 */
	protected function InitValidation()
	{
		// override method in derived extended classes
 	}


	/**
	 *
	 * Track the line number of the input file where a validation error occurred
	 *
	 * @param integer $index internal name of data item
	 * @param string $value of data item
	 * @param array $data raw user's data
	 * @return integer Error line number
	 *
	 */
	protected function TrackValidationError( $index, $value, $data )
	{
		$length = count( $data );
		while ( $length-- ) {
			$elem = $data[$length];
			if ( isset( $elem[$index] ) ) {
				$items = $this->csv_explode( $elem[$index], self::value_delimiter );
				if ( in_array( $value, $items ) && isset( $elem['linenum'] ) )
					return $elem['linenum'];
			}
		}
		return '';
	}


	/**
	 *
	 * SAVE TO CSV FILE
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Save data to CSV file
	 *
	 */
	public function SaveToFileAs()
	{
		$CsvData = array();

		$keys = array_keys( $this->CsvMap() );
		$NullArray = array_fill_keys ( $keys, '' );

		$this->dataStore = $this->FillFromDBTable();
		foreach ( $this->dataStore as $key => $dataItem ) {
			$CsvData[$key] = $NullArray;
			$this->CsvFormatDataItem( $dataItem, $CsvData[$key] );
		}

		// header row
		array_unshift( $CsvData, $keys );

		$filename = 'csv2wpec' . '_' . $this->dataSlug;
		$this->outputCSV( $CsvData, $filename );
	}


	/**
	 *
	 * Format data while saving to file
	 *
	 */
	protected function CsvFormatDataItem( $dataItem, &$CsvData )
	{
		// override method in derived extended classes
	}


	/**
	 *
	 * Save contents to file
	 * Based on:http://www.php.net/manual/en/function.fputcsv.php#100033
	 *
	 * @param array $data Csv data to save
	 * @param string $filename Filename to save CSV as (optional)
	 *
	 **/
	function outputCSV( $data, $filename = null ) {

		// set the file name and add todays date
		if ( $filename == null ) $filename = 'data';
		$filename .= '-' . gmdate('Y-m-d') . '.csv';

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=\"$filename\"");
		header("Pragma: no-cache");
		header("Expires: 0");

		$outstream = fopen("php://output", 'w');

		function __outputCSV(&$vals, $key, $filehandler) {
			fputcsv($filehandler, $vals, ',', '"');
		}
		array_walk($data, '__outputCSV', $outstream);

		fclose($outstream);
	}


	/**
	 *
	 * SAVE TO DATABASE
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Process Data
	 *
	 */
	public function InsertDataIntoDB( $dataStore )
	{
		// override method in derived extended classes
	}


	/**
	 *
	 * Process Data Record
	 *
	 */
	public function InsertDataRecordIntoDB( $dataItem )
	{
		// override method in derived extended classes
	}


	/**
	 *
	 * DISPLAY DATA
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Display Data in a WP ListTable
	 *
	 * @param string $list_slug ListTable Name
	 * @return string $contents Html-formatted Output Table
	 *
	 */
	protected function Display( $list_slug )
	{
		ob_start();

			$this->listTable->prepare_items();
			$form_name = $this->dataSlug . '_' . 'form';
			?>
			<form id="<?php esc_attr_e( $form_name ); ?>" method="get">
				<?php $this->listTable->display(); ?>
			</form>
			<?php

		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}


	/**
	 *
	 * Check if the ListTable is empty
	 *
	 * @return boolean True if there are items in the table
	 *
	 */
	public function NotEmptyTable()
	{
		return ( isset( $this->listTable ) && $this->listTable->has_items() );
	}


	/**
	 *
	 * LOG ERRORS
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Retrieve data to Log
	 *
	 * @param array $data_log Logging metadata
	 * @return string $log_link Link to the log file
	 *                          NULL if log file cannot be opened
	 *
	 */
	public function logDataErrors( $data_log )
	{
		$action_key = $data_log['action'] . '_errors';
		$code_key = $this->MapHeaderToIndex( $this->primaryKey );

        $data_log['data'] = array();
		foreach ( $this->dataStore as $dataItem ) {
			if ( isset( $dataItem[$action_key] ) ) {
				$data_log['data'][] = array( 'linenum' => $dataItem['linenum'],
											 'code' => $dataItem[$code_key],
											 'errmsg' => $dataItem[$action_key] );
			}
		}

		return $this->logErrors( $data_log );
	}


	/**
	 *
	 * Update the the Log file
	 *
	 * @param array $data_log Logging data
	 *                        'action': current action (processing/validating)
	 *						  'code': item key code
	 *						  'errmsg': array of error message strings
	 * @return string $log_link Link to the log file
	 *                          NULL if log file cannot be opened
	 *
	 */
	public function logErrors( $data_log )
	{
		ob_start();

			$time = date_i18n( '[d/M/Y H:i:s]' );
			echo( PHP_EOL . $time . PHP_EOL );
			echo( $data_log['action'] . ' file = ' . $data_log['sourcefile'] . PHP_EOL );

			foreach ( $data_log['data'] as $log_item ) {
				$caption = sprintf( '%s. %s - ', (string)$log_item['linenum'], $log_item['code'] );
				foreach ( $log_item['errmsg'] as $message  )
					echo( $caption . $message . PHP_EOL );
			}

		$contents = ob_get_contents();
		ob_end_clean();

        // open log file in append mode (file is created if do not exist)
        $log_name = strtolower( '/logs/' . $this->dataSlug . '_log.txt');
        $log_file = $this->fix_path( $data_log['log_dir'] . $log_name );
        $fp = @fopen( $log_file, 'a' );

		if ( $fp ) {
			fwrite($fp, $contents );
			fclose($fp);
		    $log_url = $this->fix_path( $data_log['log_url'] . $log_name );
			$log_link = '<a href="' . esc_url( $log_url ) . '" target="_blank">' . esc_html( $log_url ) . '</a>';
			return $log_link;
		}
	}


	/**
	 *
	 * DATA ARRAYS
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Map the fiels names of the CSV file header row (external item names)
	 * to the keys of the associative array internally used to store and
	 * manipolate the data (external item names)
	 *
	 * @return array - Internal vs external data names
	 *
	 */
	protected function CsvMap() {
		// override method in derived extended classes
    	return array ();
	}


	/**
	 *
	 * Map a field name (external data name) to a key name (internal data name)
	 *
	 * @param string $field_name - Field name
	 * @return string|NULL - Key corresponding to input Field
	 *					   ( NULL if $field_name is not a valid field )
	 *
	 */
	protected function MapHeaderToIndex( $field_name = '' )
	{
		if ( !isset( $this->HeaderToIndex ) )
			$this->HeaderToIndex = $this->CsvMap();
		if ( !empty( $field_name ) && isset( $this->HeaderToIndex[$field_name] ) )
			return $this->HeaderToIndex[$field_name];
	}


	/**
	 *
	 * Map a key name (internal data name) to a field name (external data name)
	 *
	 * @param string $index - Key name
	 * @return string|NULL - Field corresponding to input Key
	 *					   ( NULL if $index is not a valid key )
	 *
	 */
	protected function MapIndexToHeader( $index = '' )
	{
		if ( !isset( $this->IndexToHeader ) )
			$this->IndexToHeader = array_flip( $this->CsvMap() );
		if ( !empty( $index ) && isset( $this->IndexToHeader[$index] ) )
			return $this->IndexToHeader[$index];
	}


	/**
	 *
	 * Set default values when adding new data
	 *
	 * @return array - Default field values
	 *
	 */
	private function DefaultData()
	{
		// override method in derived extended classes
    	return array ();
	}


	/**
	 *
	 * UTILS
	 * ---------------------------------------------------------
	 *
	 */


	/**
	 *
	 * Extract data ids and key codes from the database
	 *
	 * @return ARRAY_A - Query results
	 *
	 */
	protected function recordList()
	{
		// override method in derived extended classes
	}


	/**
	 *
	 * Build a list of record ids and base codes
	 * RList is the resulting Array from a database Query
	 * CList is the associative array having the ids as keys
	 * and the codes as values
	 *
	 */
	protected function BuildList()
	{
		// initialize the full record list
		$this->RList = $this->recordList();

		// initialize the code list of database records
		if ( !empty( $this->RList ) ) {
			foreach ( $this->RList as $m_record )
				$this->CList[$m_record['id']] = $m_record['code'];
		}
		else
			$this->CList = array();
	}


	/**
	 *
	 * Basic File operation
	 * Get the Basename of a Filename
	 *
	 * @param string $filepath Filename (path included)
	 * @return string Base name of the file (path excluded)
	 *
	 */
	function m_basename( $filepath ) {
		return end( explode( "/", $this->fix_path( $filepath ) ) );
	}


	/**
	 *
	 * Basic File operation
	 * Fix File path for OS compatibility
	 *
	 * @param string $filepath Filename
	 * @return string Fixed Filename
	 *
	 */
	function fix_path( $filepath ) {
		return str_replace( '\\', '/', $filepath );
	}


	/**
	 *
	 * Basic File operation
	 * Get WP Default Upload folder
	 *
	 * @return string WP Default Upload folder
	 *
	 */
	public function defaultUploadPath()
	{
		$uploads = wp_upload_dir();
		$is_path = ( false === $uploads['error'] && isset( $uploads['path'] ) );
		return ( $is_path ? $this->fix_path( $uploads['path'] . '/' ) : '' );
	}


	/**
	 *
	 * Basic Array operation
	 * Array Merging
	 *
	 * @param array $a1 First array
	 * @param array $a2 Second array
	 * @return array Merging result
	 *
	 */
	protected function array_fusion( $a1, $a2 )
	{
		$result = array_merge($a1, $a2);
		$values = array();
		foreach ($result as $d)
			$values[md5(json_encode($d))] = $d;
		return array_values($values);
	}


	/**
	 *
	 * Basic Array operation
	 * Array difference
	 *
	 * @param array $a1 First array
	 * @param array $a2 Second array
	 * @return string $result The difference between first and the second array
	 *
	 */
	protected function array_difference( $a1, $a2 )
	{
		$result = array();
		foreach ( $a1 as $values )
			if ( !in_array($values, $a2) )
				$result[] = $values;
		return $result;
	}


	/**
	 *
	 * Basic Array operation
	 * Array Intersection
	 *
	 * @param array $a1 First array
	 * @param array $a2 Second array
	 * @return string $result The intersection between the input arrays
	 *
	 */
	protected function array_intersection( $a1, $a2 )
	{
		$result = array();
		foreach ( $a1 as $values )
			if ( in_array($values, $a2) )
				$result[] = $values;
		return $result;
	}


	/**
	 *
	 * Basic Array operation
	 * String to Array conversion
	 *
	 * @param array $a1 First array
	 * @param array $a2 Second array
	 * @return string $result The intersection between the input arrays
	 *
	 */
	protected function csv_explode( $str, $delim = ',', $enclose = '"', $preserve = false )
	{
		$resArr = array();
		$n = 0;
		$expEncArr = explode( $enclose, $str );
		foreach ( $expEncArr as $EncItem ) {
			if ( $n++%2 ) {
				array_push($resArr, array_pop($resArr) . ($preserve?$enclose:'') . $EncItem. ($preserve?$enclose:''));
			}
			else {
				$expDelArr = explode($delim, $EncItem);
				array_push($resArr, array_pop($resArr) . array_shift($expDelArr));
				$resArr = array_merge($resArr, $expDelArr);
			}
		}
        return array_map('trim', $resArr);
	}


	/**
	 *
	 * Pack multi-valued field data
	 * First level items are divided by the value delimiter (|)
	 * Second level items are divided by the props delimiter (:)
	 *
	 * @param array $a Data Record
	 * @param boolean $as_string True to implode Data record
	 * @return array|string Data Record Packed (and imploded)
	 *
	 */
	protected function pack_multivalue( $a, $as_string = true )
	{
		$pa = array();
		if ( isset( $a ) ) {
			$upa = unserialize( $a );
			if ( is_array( $upa ) && count( $upa ) > 0 ) {
				foreach ( $upa as $key => $value )
					$pa[$key] = implode( self::props_delimiter, $value );
			}
		}
		return $as_string ? implode( self::value_delimiter, $pa ) : $pa;
	}


	/**
	 *
	 * Basic Array Operation
	 * Estract the values of a property from array of StdClass Objects
	 *
	 * @param array $dim_array Array of StdClass Objects
	 * @param boolean $selkey Property
	 * @return array $values The values of the property
	 *
	 */
	protected function extract_stdclass_values( $dim_array, $selkey ) {
		$values = array();
		if ( !empty( $dim_array ) && array_key_exists( $selkey, $dim_array[0] ) ) {
			foreach ( $dim_array as $array )
				array_push( $values, $array->$selkey );
		}
		return $values;
	}


	/**
	 *
	 * Basic Array Operation
	 * Estract the values of a key from a Multidimensional Associative Array
	 *
	 * @param array $dim_array Multidimensional Associative Array
	 * @param boolean $selkey Selected Key
	 * @return array $values The values of the selected key
	 *
	 */
	protected function extract_array_values( $dim_array, $selkey ) {
		$values = array();
		if ( !empty( $dim_array ) && array_key_exists( $selkey, $dim_array[0] ) ) {
			foreach ( $dim_array as $array )
				array_push( $values, $array[$selkey] );
		}
		return $values;
	}


	/**
	 *
	 * Basic Array operation
	 * Array Combination
	 *
	 * Build an associative arrays with column headers as keys.
	 * array_combine returns false when empty columns are at the end of a row.
	 * This function allows to pad either array or not when parsing CSV data to arrays
	 * (dejiakala at gmail dot com 26-Oct-2011 08:32)
	 *
	 * @param array $a Header columns
	 * @param array $b Current row
	 * @param boolean $pad True to pad either array
	 * @return array Associative array with column headers as keys
	 *
	 */
	function array_combine_special($a, $b, $pad = TRUE) {
		$acount = count($a);
		$bcount = count($b);
		// more elements in $a than $b but we don''t want to pad either
		if (!$pad) {
			$size = ($acount > $bcount) ? $bcount : $acount;
			$a = array_slice($a, 0, $size);
			$b = array_slice($b, 0, $size);
		} else {
			// more headers than row fields
			if ($acount > $bcount) {
				// $more = $acount - $bcount;
				// how many fields are we missing at the end of the second array ?
				// Add empty strings to ensure arrays $a and $b have same number of elements
				$more = $acount - $bcount;
				for($i = 0; $i < $more; $i++) {
					$b[] = "";
				}
			// more fields than headers
			} elseif ($acount < $bcount) {
				$more = $bcount - $acount;
				// fewer elements in the first array, add extra keys
				for($i = 0; $i < $more; $i++) {
					$key = 'extra_field_0' . $i;
					$a[] = $key;
				}

			}
		}
		return array_combine($a, $b);
	}


	/**
	 *
	 * Basic String operation
	 * Strip the leading delete marker
	 *
	 * @return boolean - True if the delete marker has been found
	 *
	 */
	protected function CheckDeleteMarker( &$s )
	{
		if ( !empty( $s ) && strpos( (string)$s, self::delete_single ) === 0 ) {
			$s = substr( $s, strlen( self::delete_single ) );
			return true;
		}
		return false;
	}


	/**
	 *
	 * Basic String operation
	 * Check to see if a wildcard string matches a given string
	 *
	 * @param array $wild The string that may contain wild card characters
	 * @param array $string The string to test for matching
	 * @return boolean True if the two strings match
	 *
	 */
	protected function wild_compare($wild, $string) {
		$wild_i = 0;
		$string_i = 0;

		$wild_len = strlen($wild);
		$string_len = strlen($string);

		while ($string_i < $string_len && $wild[$wild_i] != '*') {
			if (($wild[$wild_i] != $string[$string_i]) && ($wild[$wild_i] != '?')) {
				return 0;
			}
			$wild_i++;
			$string_i++;
		}

		$mp = 0;
		$cp = 0;
		while ($string_i < $string_len) {
			if ($wild[$wild_i] == '*') {
				if (++$wild_i == $wild_len) {
					return 1;
				}
				$mp = $wild_i;
				$cp = $string_i + 1;
			}
			else
				if ( ( $wild[$wild_i] == $string[$string_i] ) || ( $wild[$wild_i] == '?' ) ) {
					$wild_i++;
					$string_i++;
				}
				else {
					$wild_i = $mp;
					$string_i = $cp++;
				}
		}

		while ( $wild_i < $wild_len &&  $wild[$wild_i] == '*' ) {
			$wild_i++;
		}

		return ($wild_i == $wild_len);
	}


	/**
	 *
	 * Basic String operation
	 * Check if data is not empty or 0
	 *
	 * @param string $data Input Data
	 * @return boolean True if Data is not empty or 0
	 *
	 */
	protected function NotEmptyData( $data )
	{
		return ( !empty( $data ) || is_numeric( $data ) );
	}


	/**
	 *
	 * Basic String operation
	 * Check is a string is not empty
	 *
	 */
	// Function for basic field validation (present and neither empty nor only white space )
	function IsNullOrEmptyString( $s )
	{
		return ( !isset( $s ) || trim( $s ) === '' );
	}


	/**
	 *
	 * Basic Numeric Operation
	 * Test numeric values
	 *
	 */
	protected function is_positive( $num ) { return ( is_numeric( $num ) && (int)$num > 0 ); }
	protected function is_negative( $num ) { return ( is_numeric( $num ) && (int)$num < 0 ); }
	protected function is_boolean( $num )  { return ( is_numeric( $num ) && ( (int)$num == 0 || (int)$num == 1 ) ); }

}
