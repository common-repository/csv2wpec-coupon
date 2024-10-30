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

		<div id='export_coupon_write'>
			<p><?php esc_html_e( "Click 'Export Coupons' to export all Coupons to a CSV file", 'csv2wpec-coupon' ); ?></p>
			<input name='export_coupon_start_writing' id="export_coupon_start_writing" type="button" value='<?php esc_attr_e( 'Export Coupons', 'csv2wpec-coupon' ); ?>' class='button-primary' onclick="location.href='<?php echo esc_url( $export_coupon_url ); ?>'" />
			<img id="export_coupon_writing" src="<?php echo esc_url( $this->getPluginFileUrl( 'images/loading.gif' ) ); ?>" />
		</div>

		<br />
		<div id='export_coupon_processing'></div>
