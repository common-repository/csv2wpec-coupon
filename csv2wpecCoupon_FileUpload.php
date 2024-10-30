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

	// include("mime.php");

	if (!isset($_SESSION)) {
		// session is not started.
		session_start();
	}

	if ( isset( $_POST['DATA_KEY'] ) ) {

		$dataKey = $_POST['DATA_KEY'];
		$_SESSION[$dataKey]['file_uploaded'] = '';

		if ( isset( $_POST['OP_TYPE'] ) ) {

			$op_type = $_POST['OP_TYPE'];
			$file_type = $op_type . '_file';

			if ( isset( $_FILES[$file_type] ) && !empty( $_FILES[$file_type] ) ) {

				$file = $_FILES[$file_type];
				$file_error = $file['error'];

				if ( $file_error === UPLOAD_ERR_OK ) {

					$tmp_name = $file['tmp_name'];

					$file_type = false;
					if( function_exists( 'finfo_fopen' ) ) {
						$finfo = finfo_open( FILEINFO_MIME );
        				$file_type = finfo_file( $finfo, $tmp_name );
        				finfo_close( $finfo );
					}
					elseif( function_exists( 'mime_content_type' ) ) {
						$file_type  = mime_content_type( $tmp_name );
					}
					elseif ( !is_dir( $tmp_name ) && ( $fn = @fopen( $tmp_name , "rb" ) ) ) {
						$bin = fread( $fn, $maxlen = 3072 );
						fclose( $fn );
						if ( strpos( $bin, "<?php" ) !== false )
							$file_type = "application/x-httpd-php";
					}
					// else {
					//	include("mime.php");
					//	$file_type = mime_content_type( $tmp_name );
					// }

					if ( empty ( $file_type ) )
						$file_type = $file['type'];

					$csv_mimetypes = array(
						'text/csv',
						'text/plain',
						'application/csv',
						'text/comma-separated-values',
						'application/excel',
						'application/vnd.ms-excel',
						'application/vnd.msexcel',
						'text/anytext',
						'application/txt',
					);

					if( in_array( $file_type, $csv_mimetypes ) ) {

						if ( isset( $_POST['UPLOAD_DIR'] ) ) {

							$wpsc_upload_dir = $_POST['UPLOAD_DIR'];
							$dst_name = $file['name'];
							$dest_file = $wpsc_upload_dir . $dst_name;
							$dest_file = str_replace( '\\', '/', $dest_file ); // fix path

							if ( move_uploaded_file( $tmp_name, $dest_file ) ) {
								$_SESSION[$dataKey]['file_uploaded'] = $dest_file;
								echo "success";
							}

							else {
								echo "File uploading failed. Cannot move the file to the destination folder";
							}
						}
						else {
							echo "File uploading failed. Destination folder undefined";
						}
					}
					else {
						echo "File uploading failed. File type error"; // . ": " . $file_type;
					}
				}
				else {
					echo "File uploading failed with error code: " . $file_error;
					/*
					1 = UPLOAD_ERR_INI_SIZE		The uploaded file exceeds the upload_max_filesize directive in php.ini.
					2 = UPLOAD_ERR_FORM_SIZE 	The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
					3 = UPLOAD_ERR_PARTIAL		The uploaded file was only partially uploaded.
					4 = UPLOAD_ERR_NO_FILE		No file was uploaded.
					6 = UPLOAD_ERR_NO_TMP_DIR	Missing a temporary folder
					7 = UPLOAD_ERR_CANT_WRITE	Failed to write file to disk
					8 = UPLOAD_ERR_EXTENSION	A PHP extension stopped the file upload
					*/
				}
			}
			else {
				echo "File uploading failed. Undefined file";
			}
		}
		else {
			echo "File uploading failed";
		}
	}
	else {
		echo "File uploading failed";
	}

?>