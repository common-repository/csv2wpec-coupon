<?php

	$data_log['action'] = "processing";
	$data_log['sourcefile'] = $_SESSION[$dataKey]['file_uploaded'];
	$data_log['log_url'] = $this->getPluginFileUrl();
	$data_log['log_dir'] = $this->getPluginDir();
	$log_link = $this->import_coupons->logErrors( $data_log );

?>
		<br />
		<div class="error">
			<p><span class="bold"><?php esc_html_e( "Data Processing Errors", 'csv2wpec' ); ?></span><br /></p>
			<p><?php esc_html_e( 'Errors have been logged to: ', 'csv2wpec' ); ?>&nbsp;<?php echo( $log_link ); ?></p>
		</div>
