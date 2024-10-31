<?php
/*
	Plugin Name: rvw Add Link
	Plugin URI: 
	Description: Add links to a post using shortcode.
	Version:  1.4
	Author: Richard V Woodward
	Author URI:http://rvwood.co.uk/
	License: GPL2
	
	Copyright 2013  Richard V Woodward (email :richard@rvwood.co.uk)
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	*/

/** shortcode section------------------------------------------------------------------------------ */
/** Shortcode for addlink
 * To link to any Page, Post or custom post type -
 *		[addlink postname="name of post"].
 * To link to any valid web address -
 *		[addlink url ="url of link "] 
 * To add a label to the link extend the shortcode with - text="text for link"
 * e.g.	[addlink postname="name of post" text="text for link"]
 * To add a menu that has been created in the Appearance --> Menus panel -
 *		[addlink menuname="name of menu"]
 */
function link_addlink_func( $atts ) {
	if( array_key_exists('menuname', $atts )) {	
		return wp_nav_menu( array('menu' => $atts['menuname'], 'echo'=>'0' ));
		}
	$href = '';
	if( array_key_exists('postname', $atts )) {	
		$args=array('show_ui'   => true,); 
		$post_types=get_post_types($args, 'names'); 
		foreach ($post_types as $post_type_name ) {
			if( $post_type_name == 'attachment' ) {continue;}
			$post = get_page_by_title($atts['postname'],OBJECT, $post_type_name);
			if( $post != null ) {break;}
			}
		$href = get_permalink( $post->ID );
		$text = $atts[postname];
		}
	if( array_key_exists('url', $atts) ) {
		$href = $atts['url'];
		$text = $href;
		}
	if( array_key_exists('text', $atts) ) {
		if( $atts['text'] !=  '' ) {$text = $atts['text'];}
		}
	$output = '<a href="'. $href.'">'.$text. '</a>';
	return $output. '   ';
	}
	add_shortcode( 'addlink', 'link_addlink_func' );	
	
/** ui section ----------------------------------------------------------------------------------------*/
/** Add contextual help
 */
function link_contextual_help() {
	
	get_current_screen()->add_help_tab(array(
			'id' => 'link_help',
			'title' => 'Add link',
			'content' => '<p>Clicking the &ldquo;Add link&rdquo; button shows a menu:</p>
			<ul>
			<li>Posts, Pages, to select a link destination.</li>
			<li>Custom link, to type in the url of an external page.</li>
			<li>Menus, to insert a menu that has been created in the Appearance &ndash;> Menus page.</li>
			</ul>
			<p>A label may be added to describe the link.</p>
			<p>All links will be inserted in the post as a &ldquo;shortcode&rdquo;</p>
			<p>
			Menus will be rendered by the WordPress wp_nav_menu() function, this adds classes to various parts of the menu to enable it to be styled using css
			<a href="http://codex.wordpress.org/Function_Reference/wp_nav_menu">more details here.</a>
			</p>',	
						));
	}
			
/** Add "Add link" button and popup window to editor.
 * the popup window is implemented using ui-Dialog- http://api.jqueryui.com/dialog/
 * jQuery functions are linked to this button and popup to add the shortcode to the 
 *		post in the editor.
 */
function link_add_html_to_editor(){
	$post_types=get_post_types(array('show_ui' => true,), 'names'); 
	foreach ($post_types as $post_type_name ) {
		if($post_type_name == 'attachment') {continue;}
		$post_type_display_name = get_post_type_object($post_type_name)->labels->name;
		$div =
		'<h3>' . $post_type_display_name . '<div class="filter-div"  ><span class=" link-icon-search"></span><input type="search" size="8" id="filter-' . $post_type_name . '" class="filter-box " ></div></h3>		
		<div id="'. $post_type_name .'" class="link-post-type link-dropdown"></div>';	
		
		$list_of_post_type_divs .= $div;
		}

	?>
		<a id="add-link-button" class="button wp-media-buttons" href="#" title="Add Link">
			<span class="wp-media-buttons-icon"></span>Add Link
			</a>
			
		<div id="link-dialog" style="display: none;" title="Select link destination">
			<div id="loading" style="display: none;">
				<p><img src="../wp-content/plugins/rvw-add-link/images/ajax-loader.gif" alt="Please wait" height="100" width="100" style="display: block; margin-left:auto; margin-right:auto;"/></p>
				</div>
					
			<div id="link-dialog-content">
				<?php echo $list_of_post_type_divs; ?>
					
				<h3>Custom link</h3>
				<div id="link-custom-content" class="link-dropdown" >
					</div>
					
				<h3>Menus<div class="filter-div" ><span class=" link-icon-search"></span><input type="search" size="8" id="filter-link-menu-content" class="filter-box " ></div></h3>
				
				<div id="link-menu-content" class="link-post-type link-dropdown"><!-- <span id="label-link-menu-content"></span> --></div>
					
				</div><!-- end link-dialog-content -->

				<div id="do-link-box" >
					<p id="link-url-error" style="display: none;">Please enter a valid URL</p>
					<label for="link-insert-url">URL:</label><br/>
					<textarea id="link-insert-url" name="link-insert-url" value="" ></textarea><br/>
					<label for="link-insert-label">Label:</label> <br/>
					<textarea id="link-insert-label" name="link-insert-label" value=""></textarea>
					<p>Click the link to test</p>
					<p id="link-test-link"><a target="_blank" href="" class="link-blue-highlight">Click here</a></p>
					<button id="link-insert-insert" class="link-button">Insert link</button>
					<button id="link-insert-cancel" class="link-button">Cancel</button>
					<p id="link-test-link"></p>
				</div> <!-- end do-link-box -->
			
		</div> <!-- end link-dialog -->
		<?php
	}
/** Hook all code
 * check we are on the right page.
 * enqueue scripts and styles.
 * hook in contextual help
 * hook in "Add link" button and popup window.
 */
function link_hook_code() {
		$screen_post_type = get_current_screen()->id;
		$link_post_types=get_post_types(array('show_ui' => true,));
		unset($link_post_types['attachment']);
		if(in_array($screen_post_type, $link_post_types)) {
			wp_enqueue_script('add-link-js', plugins_url('/add-link.js',  __FILE__), array('jquery'));			
			wp_enqueue_style('add-link-ui-css',
				plugins_url('/css/smoothness/jquery-ui-1.10.4.custom.min.css', __FILE__));
			wp_enqueue_script('add-link-ui-js', plugins_url('/js/jquery-ui-1.10.4.custom.min.js', __FILE__));
			wp_enqueue_style('add-link-css', plugins_url('/add-link-3-9.css',  __FILE__));

			add_action( 'admin_head', 'link_contextual_help' );
			add_action('media_buttons','link_add_html_to_editor',11);
			}	
		}
		add_action('admin_enqueue_scripts', 'link_hook_code');	
/** ajax section -------------------------------------------------------------------------------------*/
/** Return data to populate the popup window
 * Hooked into the WP ajax system.
 * Responds to ajax ( getJson() ) requests from jQuery code linked to the popup window.
 */
function link_ajax_fn() {
	if( isset($_GET['method']) ) { 
 		$linkajax = new LinkAjax();	
 		$linkajax->select_action($_GET['method']);
 		} else {
			echo json_encode(array ('Error'=>'No data found - link_ajax_fn'));
		}
		die();
	}
	
	add_action("wp_ajax_link_ajax", "link_ajax_fn");

class LinkAjax {
	
	 public function select_action($action) {
		if( $action == 'posttype' ) {$this->get_post_types();}
		if( $action == 'post' ) {$this->get_posts();}
		if( $action == 'menu' ) {$this->get_menu_names();}
		if( $action == 'menuhtml' ) {$this->get_menu_html();}
		}
		
 	private function get_post_types() {		
		$post_types=get_post_types(array('show_ui' => true,), 'names'); 
		foreach ($post_types as $post_type_name ) {
			if($post_type_name == 'attachment') {continue;}
			$list_of_post_types[$post_type_name] = 			get_post_type_object($post_type_name)->labels->name;
			}
		echo json_encode($list_of_post_types);
		die();
		} // end funtion get_post_types

 	private function get_posts() {				
		if( isset($_GET['type']) ) { 
			$post_type = $_GET['type'];	
			$args = array('post_type' => $post_type, 'orderby'=>'date');
			$content_query = new WP_Query($args);
			if ($content_query -> have_posts()) { 
				while ($content_query -> have_posts()) { 
					$content_query -> the_post();  
					$list_of_posts[get_the_title()] = get_permalink();
					}; // end while
					echo json_encode($list_of_posts);
				} // end ;if ($content_query -> have_posts())
			die();
			} else {
				echo json_encode(array ('Error'=>'No data found - get_posts'));
				}
			die();
		} // end function get_posts()
		
	private function get_menu_names() {
		$m = wp_get_nav_menus( array('orderby' => 'name') );	
		foreach ($m as $menu) {
			$menu_names[] = $menu->name;
			}
		echo json_encode($menu_names);
		die();
		} // end funtion get_menu_names
		
	private function get_menu_html() {
		if( isset($_GET['menuname']) ) { 
			$menu_name = $_GET['menuname'];	
			$menu[] = wp_nav_menu( array('menu' => $menu_name, 'echo'=> '0', ));
			echo json_encode($menu);		
				} else {
					echo json_encode(array ('Error'=>'No menu found'));
			}
		die();		
		} // end function get_menu_html
	
	} // end class		
?>
