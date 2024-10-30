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


$minimalRequiredWPVersion = '3.3';		// minimal WP version required
$minimalRequiredWPeCVersion = '3.8.0';	// minimal WPeC version required

// Check WP version

global $wp_version;

if ( (float)$wp_version < (float)$minimalRequiredWPVersion ) {
	deactivate_plugins( plugin_basename(__FILE__) ); // Deactivate ourselves
	$smg = __( "Looks like you're running an older version of WordPress.", 'csv2wpec-coupon' ) . '<br />' .
		   __( "You need to be running at least WordPress ", 'csv2wpec-coupon' ) . $minimalRequiredWPVersion . ' ' .
		   __( "to use", 'csv2wpec-coupon' ) . ' ' . $this->getPluginDisplayName() . '.';
	exit( $smg );
	return;
}

// Check WPeC version

$wpec_relative_path = 'wp-e-commerce/wp-shopping-cart.php';
$status = $this->get_plugin_status( $wpec_relative_path );

switch ( $status ) {
	case 0: // WPeC is not installed
		deactivate_plugins( plugin_basename(__FILE__) );
		$smg = __( "Looks like the WP-Ecommerce plugin is not installed.", 'csv2wpec-coupon' ) . '<br />';
		$smg = __( "You need to install the WP e-commerce plugin (minimal required version: ", 'csv2wpec-coupon' ) . $minimalRequiredWPeCVersion . ') ' .
			   __( "to use", 'csv2wpec-coupon' ) . ' ' . $this->getPluginDisplayName() . '.';
		exit( $smg );
		break;

	case 1: // WPeC is inactive
		deactivate_plugins( plugin_basename(__FILE__) );
		$smg = __( "Looks like the WP-Ecommerce plugin is inactive.", 'csv2wpec-coupon' ) . '<br />';
		$smg = __( "You need to activate the WP e-commerce plugin (minimal required version: ", 'csv2wpec-coupon' ) . $minimalRequiredWPeCVersion . ') ' .
			   __( "to use", 'csv2wpec-coupon' ) . ' ' . $this->getPluginDisplayName() . '.';
		exit( $smg );
		break;

	case 2: // WPeC is active, check version
		$wpec_version = WPSC_VERSION . "." . WPSC_MINOR_VERSION;
		if ( version_compare( $wpec_version, $minimalRequiredWPeCVersion ) < 0 ) {
			deactivate_plugins( plugin_basename(__FILE__) ); // Deactivate ourselves
			$smg = __( "Looks like you're running an older version of WP e-commerce plugin.", 'csv2wpec-coupon' ) . '<br />' .
				   __( "Minimum WPeC version required to use ", 'csv2wpec-coupon' ) . $this->getPluginDisplayName() . ' ' .
				   __( "is", 'csv2wpec-coupon' ) . ' ' . $minimalRequiredWPeCVersion;
			exit( $smg );
		}
}
