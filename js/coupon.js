
jQuery(document).ready(function($){

	function m_ajaxurl() {
		return ajax_coupon_localize.plugin_ajaxurl;
	}

	$("#export_coupon_start_reading").click(function(e) {
		$("#export_coupon_table").hide();
		$("#export_coupon_write_wrapper").hide();
		$('#export_coupon_reading').show();
		$("#export_coupon_hide_readout").hide();
		$("#export_coupon_show_readout").hide();
		var data = {
			action: 'DumpCoupons',
			ajaxDumpCoupons_Nonce: ajax_coupon_localize.ajaxDumpCoupons_Nonce
		};
		$.ajax({
			type: 'POST',
			url: m_ajaxurl(),
			data: data,
			success: function( response ) {
				$("#export_coupon_table").html(response.table);
				$("#export_coupon_write_wrapper").html(response.html);
				$("#export_coupon_table").show();
				$("#export_coupon_write_wrapper").show();
				$("#export_coupon_hide_readout").show();
				$('#export_coupon_reading').hide();
			},
			error: function( response ) {
				$("#export_coupon_write_wrapper").html(ajax_coupon_localize.ajaxDumpCoupons_Error);
				$("#export_coupon_write_wrapper").show();
				$('#export_coupon_reading').hide();
			}
		});
	});


	$("#export_coupon_hide_readout").click(function(e) {
		$("#export_coupon_table").hide();
		$("#export_coupon_write_wrapper").hide();
		$("#export_coupon_hide_readout").hide();
		$("#export_coupon_show_readout").show();
	});

	$("#export_coupon_show_readout").click(function(e) {
		$("#export_coupon_table").show();
		$("#export_coupon_write_wrapper").show();
		$("#export_coupon_hide_readout").show();
		$("#export_coupon_show_readout").hide();
	});


	$("#import_coupon_start_reading").click(function(e) {
		$("#import_coupon_table").hide();
		$('#import_coupon_write_wrapper').hide();
		// $('#import_coupon_validating_log').hide();
		$('#import_coupon_reading').show();
		var data = {
			action:'ReadCoupons',
			ajaxReadCoupons_Nonce: ajax_coupon_localize.ajaxReadCoupons_Nonce
		};
		$.ajax({
			type: 'POST',
			url: m_ajaxurl(),
			data: data,
			success: function( response ) {
				$("#import_coupon_table").html( response.table );
				$('#import_coupon_write_wrapper').html( response.html );
				$("#import_coupon_table").show();
				$('#import_coupon_write_wrapper').show();
				$('#import_coupon_reading').hide();
			},
			error: function( response ) {
				$('#import_coupon_write_wrapper').html(ajax_coupon_localize.ajaxReadCoupons_Error);
				$('#import_coupon_write_wrapper').show();
				$('#import_coupon_reading').hide();
			}
		});
	});


	$("#import_coupon_start_writing").live('click',function(e) {

		// initial time
		var jc_time0 = new Date().getTime();

		// Show statistics at the end of the import process
		function ShowStatistics( result )
		{
			// calculate the processing time
			var jc_time1 = new Date().getTime();
			var totalSec = Math.round( ( jc_time1 - jc_time0 ) / 1000);
			var hours = parseInt( totalSec / 3600 ) % 24;
			var minutes = parseInt( totalSec / 60 ) % 60;
			var seconds = totalSec % 60;
			var processing_time = (hours < 10 ? "0" + hours : hours) + ":" + (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds  < 10 ? "0" + seconds : seconds);

			$('.cstat_items').html( result["items"] );
			$('.cstat_processed').html( result["processed"] );
			$('.cstat_created').html( result["inserted"] > 0 ? result["inserted"] : '-' );
			$('.cstat_updated').html( result["updated"] > 0 ? result["updated"] : '-' );
			$('.cstat_deleted').html( result["deleted"] > 0 ? result["deleted"] : '-' );
			$('.cstat_errors').html( result["error"] > 0 ? result["error"] : '-' );
			$('.cstat_time').html( processing_time );

			//show statistics table
			$("#import_coupon_statistics").show();

			// notify error logging
			if ( typeof( result["file_log"] ) != 'undefined' && result["file_log"] ) {
				$('#import_coupon_processing_log').html( result["file_log"] );
			}
		}

		// update the list table
		function UpdateCouponListTable()
		{
			// reset coupon readout table
			$("#export_coupon_table").hide();
			$("#export_coupon_write_wrapper").hide();
			$("#export_coupon_hide_readout").hide();
			$("#export_coupon_show_readout").hide();
		}

		$('#import_coupon_writing').show();
		var data = {
			action:'ImportCoupons',
			ajaxImportCoupons_Nonce: ajax_coupon_localize.ajaxImportCoupons_Nonce
		};
		$.ajax({
			type: 'POST',
			url: m_ajaxurl(),
			data: data,
			success: function( response ) {
				$('#import_coupon_table').html(response.table);
				$('#import_coupon_table').show();
				$('#import_coupon_writing').hide();
				$('#import_coupon_processing').html(response.html);
				$('#import_coupon_start_writing').attr('disabled', 'disabled');
				ShowStatistics( response.result );
				UpdateCouponListTable();
			},
			error: function( response ) {
				$('#import_coupon_writing').hide();
				$('#import_coupon_processing').html(ajax_coupon_localize.ajaxImportCoupons_Error);
				$('#import_coupon_processing').show();
			}
		});
	});

});


/**
 *	Reading CSV filename
 */

function redirect_coupon()
{
	if ( document.getElementById('coupon_file').value == '' )
		return;

	document.getElementById('import_coupon_browse').target = 'import_coupon_iframe';
	document.getElementById('import_coupon_browse').submit();

	var iFrame = document.getElementById("import_coupon_iframe");
	var loading = document.getElementById("import_coupon_reading");
	iFrame.contentWindow.document.body.innerHTML = "";
	loading.style.display = "inline";

	checkComplete_coupon();
}


function checkComplete_coupon()
{
	var iFrame = document.getElementById("import_coupon_iframe");
	var loading = document.getElementById("import_coupon_reading");

	if ( iFrame.contentWindow.document.body.innerHTML == "" )
	{
		setTimeout ( checkComplete_coupon, 2000 );
	}
	else
	{
		if ( iFrame.contentWindow.document.body.innerHTML == "success" )
		{
			document.getElementById("import_coupon_start_reading").click();
		}
		else
		{
			alert("Error: "+ iFrame.contentWindow.document.body.innerHTML);
			loading.style.display = "none";
		}
	}
}
