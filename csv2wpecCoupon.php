<?php
/*
   Plugin Name: Csv2WPeC Coupon
   Plugin URI: http://wordpress.org/extend/plugins/csv2wpec-coupon/
   Description: Csv2WPeC Coupon plugin provides an easy way to import/export WP e-Commerce Coupons from/to a CSV file
   Version: 1.1
   Author: Davide Mencarini
   Author URI: http://www.csv2wpec.it
   Text Domain: csv2wpec-coupon
   License: GPL3
*/

/*
    "WordPress Plugin Template" Copyright (C) 2013 Michael Simpson  (email : michael.d.simpson@gmail.com)

    This following part of this file is part of WordPress Plugin Template for WordPress.

    WordPress Plugin Template is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    WordPress Plugin Template is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Contact Form to Database Extension.
    If not, see http://www.gnu.org/licenses/gpl-3.0.html
*/

$csv2wpecCoupon_minimalRequiredPhpVersion = '5.0';

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying
 * an error message on the Admin page
 */
function csv2wpecCoupon_noticePhpVersionWrong() {
    global $csv2wpecCoupon_minimalRequiredPhpVersion;
    echo '<div class="updated fade">' .
      __('Error: plugin "Csv2WPeC Coupon" requires a newer version of PHP to be running.',  'csv2wpec-coupon').
            '<br/>' . __('Minimal version of PHP required: ', 'csv2wpec-coupon') . '<strong>' . $csv2wpecCoupon_minimalRequiredPhpVersion . '</strong>' .
            '<br/>' . __('Your server\'s PHP version: ', 'csv2wpec-coupon') . '<strong>' . phpversion() . '</strong>' .
         '</div>';
}


function csv2wpecCoupon_PhpVersionCheck() {
    global $csv2wpecCoupon_minimalRequiredPhpVersion;
    if (version_compare(phpversion(), $csv2wpecCoupon_minimalRequiredPhpVersion) < 0) {
        add_action('admin_notices', 'csv2wpecCoupon_noticePhpVersionWrong');
        return false;
    }
    return true;
}


/**
 * Initialize internationalization (i18n) for this plugin.
 *
 *
 *
 * @return void
 */
function csv2wpecCoupon_i18n_init() {
    $pluginDir = dirname(plugin_basename(__FILE__));
    load_plugin_textdomain('csv2wpec-coupon', false, $pluginDir . '/languages/');
}


//////////////////////////////////
// Run initialization
/////////////////////////////////

// First initialize i18n
csv2wpecCoupon_i18n_init();


// Next, run the version check.
// If it is successful, continue with initialization for this plugin
if (csv2wpecCoupon_PhpVersionCheck()) {
    // Only load and run the init function if we know PHP version can parse it
    include_once('csv2wpecCoupon_init.php');
    csv2wpecCoupon_init(__FILE__);
}
