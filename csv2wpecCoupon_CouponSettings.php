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

?>

				<div id="export_coupon_section">
					<h3><?php esc_html_e( 'Export Coupons', 'csv2wpec-coupon' ); ?></h3>

					<div id="export_coupon_read">
						<p><?php esc_html_e( 'Lists all Coupons in the database', 'csv2wpec-coupon' ); ?></p>
						<input type="button" class='button' value="<?php esc_attr_e( 'Display Coupons', 'csv2wpec-coupon' ); ?>" id='export_coupon_start_reading' name='export_coupon_start_reading' />
						<input type="button" class='button' value="<?php esc_attr_e( 'Hide List', 'csv2wpec-coupon' ); ?>" id='export_coupon_hide_readout' name='export_coupon_hide_readout' />
						<input type="button" class='button' value="<?php esc_attr_e( 'Show List', 'csv2wpec-coupon' ); ?>" id='export_coupon_show_readout' name='export_coupon_show_readout' />
						<img id="export_coupon_reading" src="<?php echo esc_url( $this->getPluginFileUrl( 'images/loading.gif' ) ); ?>" />
					</div>

					<div id="export_coupon_table"></div>
					<div id="export_coupon_write_wrapper"></div>

				</div>

				<br />

				<?php
					$action_file = $this->getPluginFileUrl( 'csv2wpecCoupon_FileUpload.php' );
					$upload_path = $this->import_coupons->defaultUploadPath();
					$dataKey = $this->prefix( $this->import_coupons->getDataSlug() );
				?>

				<div id="import_coupon_section">
					<h3><?php esc_html_e( 'Import Coupons', 'csv2wpec-coupon' ); ?></h3>

					<p>
						<?php esc_html_e( 'Click "Browse" to select a Coupon CSV Import File', 'csv2wpec-coupon' ); ?><br />
						<?php esc_html_e( 'Click "Read File" to read and validate the Import File', 'csv2wpec-coupon' ); ?>
					</p>

					<form id="import_coupon_browse" name="form" action="<?php echo esc_url( $action_file ); ?>" method="POST" enctype="multipart/form-data" >
						<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
						<input type="hidden" name="UPLOAD_DIR" value="<?php esc_attr_e( $upload_path ); ?>" />
						<input type="hidden" name="OP_TYPE" value="coupon" />
						<input type="hidden" name="DATA_KEY" value="<?php esc_attr_e( $dataKey ); ?>" />
						<input name="coupon_file" id="coupon_file" size="80" type="file" />
						<input type="button" class='button' name="action" value="<?php esc_attr_e( 'Read file', 'csv2wpec-coupon' ); ?>" onclick="redirect_coupon()" />
						<input type="button" value="" id='import_coupon_start_reading' name='import_coupon_start_reading' />
						<iframe id='import_coupon_iframe' name='import_coupon_iframe' src=""></iframe>
						<img id="import_coupon_reading" src="<?php echo esc_url( $this->getPluginFileUrl( 'images/loading.gif' ) ); ?>" />
					</form>

					<div id="import_coupon_table"></div>
					<div id="import_coupon_validating_log"></div>
					<div id="import_coupon_write_wrapper"></div>

				</div>
