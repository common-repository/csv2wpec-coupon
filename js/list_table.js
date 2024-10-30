
jQuery(document).ready(function($){

	/**
	 *	WP_List_Page ajaxified
	 */

	// Pagination
	// -------------------------------------------------

	$(".current-page").live( 'keydown', function(e)
 	{
		if ( e.keyCode == 13 ) {

			e.preventDefault(); // no redirect

			var form = $(this).closest('form')[0];
			var paged = $(this).attr("value");

			var collapse_anchor = $("#"+form.id).find('.expand_table');
			collapse_url = collapse_anchor.attr('href');
			var collapse_params = UrlStringParameters( collapse_url );
			var query_string = collapse_params + '&paged=' + paged;

			UpdateListTable( query_string, form.id );
		}
	});


 	$( ".first-page, .next-page, .prev-page, .last-page" ).live( 'click', function(e)
 	{
		e.preventDefault(); // no redirect

		var form = $(this).closest('form')[0];
		var url = $(this).attr('href');
		var query_string = UrlStringParameters( url );
		if ( !query_string ) query_string = '&paged=0';
		UpdateListTable( query_string, form.id );
	});


	// Sort
	// -------------------------------------------------

	$("th.sortable, th.sorted").live( 'click', function(e)
 	{
		e.preventDefault(); // no redirect

		var form = $(this).closest('form')[0];
		var anchor = $(this).children("a");
		var url = $(anchor).attr('href');
		var query_string = UrlStringParameters( url );
		UpdateListTable( query_string, form.id );
	});


	// Bulk actions
	// -------------------------------------------------

	$(".action").live( 'click', function(e)
 	{
		e.preventDefault(); // no redirect

		var form = $(this).closest('form')[0];
		var form_id = form.id;
		var query_string = $( "#"+form_id ).serialize();

		// replace WP_List_Table key 'action' and 'action2' with 'list_action'
		// and 'list_action2' respectively, to prevent the WP action hook be overriden;
		// function WP_List_Table::current_action() needs to be overriden in
		// derived classes (sic)
		query_string = query_string.replace("&action=", "&list_action=");
		query_string = query_string.replace("&action2=", "&list_action2=");

		UpdateListTable( query_string, form_id );
	});


	// Row actions
	// -------------------------------------------------

 	$( "span.show_error, span.delete" ).live( 'click', function(e)
 	{
		e.preventDefault(); // no redirect

		var form = $(this).closest('form')[0];
		var anchor = $(this).children("a");
		var url = $(anchor).attr('href');
		var query_string = UrlStringParameters( url );

		// replace WP_List_Table key 'action' and 'action2' with 'list_action'
		// and 'list_action2' respectively, to prevent the WP action hook be overriden;
		// function WP_List_Table::current_action() needs to be overriden in
		// derived classes (sic)
		query_string = query_string.replace("&action=", "&list_action=");
		query_string = query_string.replace("&action2=", "&list_action2=");

		UpdateListTable( query_string, form.id );
	});


	// Link actions
	// -------------------------------------------------

  	$( ".expand_table" ).live( 'click', function(e)
 	{
		e.preventDefault(); // no redirect

		var form = $(this).closest('form')[0];
		var url = $(this).attr('href');
		var query_string = UrlStringParameters( url );
		UpdateListTable( query_string, form.id );
	});


	// Show Error List
	// -------------------------------------------------

 	$( ".active_key" ).live( 'click', function(e)
 	{
		e.preventDefault(); // no redirect

		var anchor = $(this).children("a");
		var url = $(anchor).attr('href');
		var query_string = UrlStringParameters( url );

		var params = UrlParameters( query_string );
		if ( params['action'] == 'show_data_errors' )
			$( "#" + params['type'] + '_data_errors_' + params['item'] ).toggle();
	});

 	$( ".data_errors" ).live( 'click', function(e)
 	{
		e.preventDefault(); // no redirect

		$(this).hide();
	});


	// List table handler
	// -------------------------------------------------

	function UpdateListTable( param_list, form_id )
 	{
		param_list += '&action=' + 'UpdateListTable';
		param_list += '&page=' + ajax_list_table_localize.ajaxPluginPage;
		param_list += '&form_id=' + form_id;
		param_list += '&_wpnonce=' + ajax_list_table_localize.ajaxUpdateListTable_Nonce;

		$("body").css("cursor", "wait");

		$.ajax({
			type: "GET",
			url: ajaxurl,
			data: param_list,
			success: function( response ) {
				table_wrapper = response.table_wrapper;
				$("#" + table_wrapper).html( response.table );
				$("body").css("cursor", "default");
			},
			error: function( response ) {
				$("body").css("cursor", "default");
			}
		});
	}


	// Extract parameters strig from an URL string
	// -------------------------------------------------

	function UrlStringParameters( sURL )
	{
		return ( sURL && sURL.indexOf("?") >= 0 ) ? sURL.split("?")[1] : "";
	}


	function UrlParameters(url)
	{
		var param_list = [];
		var params = url.split("&");
		for ( var i = 0; i < params.length; i++ )
		{
			var param_item = params[i].split("=");
			param_list[param_item[0]] = unescape(param_item[1]);
		}
		return param_list;
	}


	function GetUrlParameter(url, param)
	{
		var param_list = QueryUrlParameters(url);
		return param_list[param];
	}

});




