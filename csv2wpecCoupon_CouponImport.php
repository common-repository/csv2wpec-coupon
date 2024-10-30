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


if ( !$valid_data ) {

	$data_log = array();
	$data_log['action'] = 'validating';
	$data_log['sourcefile'] = $filename;
	$data_log['log_url'] = $this->getPluginFileUrl();
	$data_log['log_dir'] = $this->getPluginDir();
	$log_link = $this->import_coupons->logDataErrors( $data_log );

?>
		<div class="error">
			<p><span class="bold"><?php esc_html_e( "Data Validation Errors", 'csv2wpec-coupon' ); ?></span><br /></p>
			<p>
				<span><?php esc_html_e( "Data cannot be processed because of errors in the Import File.", 'csv2wpec-coupon' ); ?></span><br />
				<span><?php esc_html_e( "You should fix the errors and read the file again for validation.", 'csv2wpec-coupon' ); ?></span><br />
			</p>
			<p><?php esc_html_e( 'Errors have been logged to: ', 'csv2wpec-coupon' ); ?>&nbsp;<?php echo( $log_link ); ?></p>
			<br />
		</div>
<?php
} else {
?>
		<div id='import_coupon_write'>
			<p><?php esc_html_e( "Click 'Import Coupons' to import Coupons from the CSV Import File", 'csv2wpec-coupon' ); ?></p>
			<input name='import_coupon_start_writing' id="import_coupon_start_writing" type="button" value='<?php esc_attr_e( 'Import Coupons', 'csv2wpec-coupon' ); ?>' class='button-primary' />
			<img id="import_coupon_writing" src="<?php echo esc_url( $this->getPluginFileUrl( 'images/loading.gif' ) ); ?>" />
		</div>

		<div id='import_coupon_processing'>
		</div>

		<div id="import_coupon_statistics">
			<br />
			<p class='statistics-title'><?php esc_html_e( 'Statistics', 'csv2wpec-coupon' ); ?></p>
			<table class="statistics-table">
			  <tr>
				<th scope="col"><?php esc_html_e( 'Coupons', 'csv2wpec-coupon' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Processed', 'csv2wpec-coupon' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Created', 'csv2wpec-coupon' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Updated', 'csv2wpec-coupon' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Deleted', 'csv2wpec-coupon' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Errors', 'csv2wpec-coupon' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Processing time', 'csv2wpec-coupon' ); ?></th>
			  </tr>
			  <tr>
				<td class='cstat_items'></td>
				<td class='cstat_processed'></td>
				<td class='cstat_created'></td>
				<td class='cstat_updated'></td>
				<td class='cstat_deleted'></td>
				<td class='cstat_errors'></td>
				<td class='cstat_time'></td>
			  </tr>
			</table>
			<br />
			<div id="import_coupon_processing_log"></div>
		</div>
<?php
}
?>
