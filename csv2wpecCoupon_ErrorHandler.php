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


class csv2wpecCoupon_ErrorHandler {

	protected $localName;
	protected $errorMessages;
	protected $errorList;

	function __construct( $localName = '' ) {
		$this->localName = $localName;
		$this->errorMessages = array();
		$this->errorList = array();
	}


	/**
	 *
	 * Initialize the error messages
	 *
	 * @return array Error message strings
	 *
	 */
	protected function InitErrorMessages()
	{
		// override method in derived extended classes
		return array();
	}


	/**
	 *
	 * Reset the error list
	 *
	 */
	function Reset()
	{
		$this->errorList = array();
	}


	/**
	 *
	 * Get the ErrorList
	 *
	 * @return array Errors added to the list
	 *
	 */
	function GetErrorList()
	{
		return $this->errorList;
	}


	/**
	 *
	 * Are there errors in the list?
	 *
	 * @return boolean True if the error list is non empty
	 *
	 */
	function IsError()
	{
		return !empty( $this->errorList );
	}


	/**
	 *
	 * How many errors are in the list?
	 *
	 * @return integer The number of errors in the list
	 *
	 */
	function NumErrors()
	{
		return count( $this->errorList );
	}


	/**
	 *
	 * Add an error to the list
	 *
	 * @param string $code Error code
	 * @param array $params Error parameters
	 * @param string $linenum Error line number
	 *
	 */
	function AddError( $code, $params = array(), $linenum = '' )
	{
		$this->errorList[] = array( 'code' => $code, 'params' => $params, 'linenum' => $linenum );
	}


	/**
	 *
	 * Get an error message string
	 *
	 * @param string $code Error code
	 * @param array $params Error parameters
	 * @param string $linenum Error line number
	 * @return string $errmsg Error message
	 *
	 */
	function GetErrorMessage( $code, $params = array(), $linenum = '' )
    {
		if ( empty ( $this->errorMessages ) )
			$this->errorMessages = $this->InitErrorMessages();

		$errmsg = ( !empty( $code ) && isset( $this->errorMessages[$code] ) ) ?
			$this->printf_array( $this->errorMessages[$code], $params ) :
			__( 'Unknown error', $this->localName );

		if ( !empty( $linenum ) ) {
			$errmsg = '[line ' . (string)$linenum . ']' . $errmsg ;
		}

		return $errmsg;
	}

	private function printf_array( $format, $params )
	{
		$e_msg = call_user_func_array( 'sprintf', array_merge( (array)$format, $params ) );
	    return $e_msg;
	}


	/**
	 *
	 * Get the message strings of all errors in the list
	 *
	 * @return array|NULL $eMessages Error message strings
	 *								 NULL if the list is empty
	 *
	 */
	function GetErrors()
    {
		if ( !empty( $this->errorList ) ) {
			$eMessages = array();
			foreach ( $this->errorList as $error )
				$eMessages[] = $this->GetErrorMessage( $error['code'], $error['params'], $error['linenum'] );
			return $eMessages;
		}
	}


	/**
	 *
	 * File Reading Errors
	 * Generate content for error messages occured while reading the file
	 *
	 * @return string $contents Html-formatted errors
	 *
	 */
	public function formatFileErrors()
	{
		$contents = '';
		if ( empty( $this->errorList ) )
			return $contents;

		$caption = $this->GetErrorMessage( 'file_reading_error' );

		ob_start();

		?>
			<div>
				<p class='error_title'><?php esc_html_e( $caption ); ?></p>
				<?php
					foreach ( $this->errorList as $error ) {
						$errorMessage = $this->GetErrorMessage( $error['code'], $error['params'], $error['linenum'] );
				?>
					<span class='error_item'><?php esc_html_e( $errorMessage ); ?></span><br />
				<?php } ?>
			</div>
		<?php

		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

}
