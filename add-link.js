/** Javascript / jQuery code for rvw Add Link
 * Requires jQuery and  ui-dialog, ui-autocomplete, ui-accordion http://api.jqueryui.com/
 * and css for smoothness theme
 * Shows the ui-dialog popoup window.
 * populates it using Ajax getJSON requests
 * enters the shortcode in the editor
*/
jQuery(document).ready(function($){	
	/**Initialise the popup dialog*/	
	$( "#link-dialog" ).dialog({ autoOpen: false, modal: true, width: 300, height: 400, my: "center", at: "center", of: window  });		
	$( '.filter-box' ).click(function(ev){
			ev.stopPropagation();
		});
	
	$( '.filter-div' ).hide();

	/**Open the popup dialog */
	$('#add-link-button').click(function(ev){
		ev.preventDefault();	
		$('#do-link-box').hide();					
		$('#link-dialog-content').show();
		$("#link-dialog" ).dialog( "open" );
	 	}); // end add-link-button click

	/** override the autocomplete widget close function to prevent close 
	 * After setup the content will stay dropped down
	 * it will be hidden as required by the accordian widget
	 */
	$.widget( "ui.autocomplete", jQuery.ui.autocomplete, {
			_close: function( event ) {
				 	return false;
			},
		});
	/** Set up #link-dialog content as accordion
	 * This is set up in the php (rvw-add-link.php) to have header and 				  content sections for-
	 * posts, custom post types, menus, custom links
	 * The content sections are empty.
	 * when clicked for the first time they are populated by a JSON call to the server (beforeactivate).
	 * Content is placed in a autocomplete widget to enable filtering.
	 */
	$( '#link-dialog-content' ).accordion({
  		heightStyle: 'content',
  		collapsible: true,
  		active: false,
  		activate: function(event, ui ) {
			$( '.ui-accordion-header.ui-accordion-header-active>div' ).slideDown( 'fast' );
			$( '.ui-accordion-header.ui-accordion-header-active input' ).focus();
 			},
  		beforeActivate: function( event, ui ) {
			var new_panel = ui.newPanel;
			var panel_id = $(new_panel).attr('id');
				$( '.filter-div' ).hide();
			if( ! ui.newPanel.length ) { // close click 
				return; 
				}  			
			if( panel_id === 'link-custom-content' ) {
				$( '#link-dialog-content' ).accordion( 'option', 'active', false );
				$( '#link-dialog-content' ).hide();
				$( '#link-insert-url' ).removeAttr('disabled');
				$( '#do-link-box' ).show();
				$( '#link-insert-url' ).focus();
				return false; 
				} //end if custom link
			if( panel_id === 'link-menu-content'  &&   ! $( new_panel ).hasClass( 'setup-done' )) {
				var url = ajaxurl; // this is already set up in admin pages.
				var data = {action:'link_ajax', 'method':'menu'};
				$('#loading').show();
				var return_array = [];	
				$.getJSON(url, data, function(return_array){
					$('#loading').hide();
					$( '#filter-' + panel_id ).attr( 'value', '' )
							.autocomplete( 'option',  'source', return_array )
							.autocomplete( "option", "appendTo", new_panel )
							.autocomplete( 'search', ''  )
							.show()
							.focus();						
						$(new_panel).addClass('setup-done');	
					}); // end $getJSON				
				} // end if is menu link
				
			if(  ! $( new_panel ).hasClass( 'setup-done' ) )  {	// posts & post types		
					// if list of posts does not exist create it.
					var return_array = [];
					var url = ajaxurl; // this is already set up in admin pages to /wp-admin/admin-ajax.php
					// In the php we have set up a hook - wp_ajax_link_ajax, to handle this request.
					var data = {action: 'link_ajax', 'method': 'post', 'type': panel_id};
					jQuery('#loading').show();
					jQuery.getJSON(url, data, function(return_array){
						jQuery('#loading').hide();
						var items_array =[];
						var xxx = {};
						jQuery.each(return_array, function(key, val) {
							xxx = { label: key,	value: val };
							items_array.push(xxx);
							}); // end each	
						$( '#filter-' + panel_id ).attr( 'value', '' )
							.autocomplete( 'option',  'source', items_array )
							.autocomplete( "option", "appendTo", new_panel )
							.autocomplete( 'search', ''  )
							.show()
							.focus();						
						$(new_panel).addClass('setup-done');							
						}); // end $getJSON
					}; // end posts & post types					
				}, // end beforeselect
  		}); // end $( '#link-dialog-content' ).accordion({

	/** Set up accordian content sections (.filter-box) as autocomplete. */
	$( '.filter-box' ).autocomplete({
		source: [], 
		minLength: 0,
		open: function() {
			$('.ui-menu').css({'width' : '99%', 'height': '200px', 'overflow': 'auto', 'position': 'static'} );
				},
		select: function( event, ui ) {
			if( $( this ).attr( 'id' ) == 'filter-link-menu-content' ) { 	// menu selected
				doMenu( ui.item.label );
				} else {																	// post selected
					doPost( ui.item.label, ui.item.value );
					}
				return false;
			}, //end select
		}); // $( '.filter-box' ).autocomplete({
		
 	/** Insert the menu into the editor. */ 
 	function doMenu( name ) {
		var link_shortcode = '[addlink menuname="' + name + '"]';				
		send_to_editor(link_shortcode);
		$( "#link-dialog" ).dialog( "close" );			
 		}
	/** Set up and show the do-link-box.
	 * This enables the user to edit, test, and insert the link into the editor.
	 */
 	function doPost( lk, ul ) {
 		$('#link-dialog-content').hide();
		$('#link-url-error').hide();
 		$('#link-insert-url').attr('value', ul);
 		$('#link-insert-url').attr('disabled', 'disabled');
 		$('#link-insert-label').attr('value', lk);
 		$('#link-test-link a').attr('href', ul);
		$('#do-link-box').show(); 
 		$('#link-insert-label').select();
 		};
 	
 	/** Set a timer to update #link-test-link on edit label.*/
 		var timer;
 	$('#link-insert-label').focusin(function() {
 			timer = setInterval(function() {
 			var old_label_text;
			var label_text = $('#link-insert-label').attr('value');
			if( label_text != old_label_text ) {
			$('#link-test-link a').html(label_text);
				}
			old_label_text = label_text;
 			}, 100);			
 		});
 	/** Stop the timer */
 	$('#link-insert-label').focusout(function() {
 		clearInterval(timer);
 		});

 	/** Test the link in  the do-link-box.
 	 *Check for a valid url before allowing the link to operate
 	 */
 	$('#link-test-link a').click(function(){
 		var url = $('#link-insert-url').attr('value');
		if( ! isUrl(url) ) {
			$('#link-url-error').show();
			$('#link-insert-url').focus();
			return false;
			}
		$('#link-url-error').hide();
		$('#link-test-link a').attr('href', url);
		});
 		
	/** Cancel button in the do-link-box. */ 	
 	$('#link-insert-cancel').click(function(ev) {
		$('#link-url-error').hide();
 		$('#link-insert-url').attr('value', '');
 		$('#link-insert-label').attr('value', '');
		$('#do-link-box').hide();	
 		$('#link-dialog-content').show( 400, function() {
			$( '.ui-accordion-header.ui-accordion-header-active input' ).focus();
 			});
		});
	
	/** Insert button in the do-link-box. */ 	
 	$('#link-insert-insert').click(function(){
 		var url = $('#link-insert-url').attr('value');
		if( ! isUrl(url) ) {
			$('#link-url-error').show();
			$('#link-insert-url').focus();
			return;
			}
 		var label = $('#link-insert-label').attr('value');
 		var editor_text = '[addlink url="' + url + '" text="' + label + '"]';
		send_to_editor(editor_text);
		$('#link-url-error').hide();
		$('#link-insert-url').attr('value', '');
 		$('#link-insert-label').attr('value', '');
		$( "#link-dialog" ).dialog( "close" );
		});
	/** Test for a valid url. */
	function isUrl(s) {
    	var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
    	return regexp.test(s);
	}
}); //end document ready

