<?php
/*
Plugin Name: RandImage
Plugin URI: http://meandmymac.net/plugins/randimage/
Description: Allows to show random images from the WordPress media library.
Author: Arnan de Gans
Version: 0.4.1
Author URI: http://meandmymac.net
*/ 

register_activation_hook(__FILE__, 'randimage_activate');
register_deactivation_hook(__FILE__, 'randimage_deactivate');

randimage_check_config();
add_action('admin_menu', 'randimage_dashboard'); //Add page menu links
add_action('widgets_init', 'randimage_widget_init'); //Initialize the widget

if(isset($_POST['randimage_submit_options']) AND $_GET['updated'] == "true") {
	add_action('init', 'randimage_options_submit'); //Update Options
}

function randimage_dashboard() {
	add_options_page('RandImage', 'RandImage', 10, basename(__FILE__), 'randimage_options');
}
$randimage_config = get_option('randimage_config'); // Load Options

/*-------------------------------------------------------------
 Name:      randimage_options_page

 Purpose:   Admin options page
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function randimage_options() {
	$randimage_config = get_option('randimage_config');
	$randimage_tracker = get_option('randimage_tracker');
?>
	<div class="wrap">
	  	<h2>RandImage options</h2>
	  	<form method="post" action="<?php echo $_SERVER['REQUEST_URI'];?>&amp;updated=true">
	    	<input type="hidden" name="randimage_submit_options" value="true" />

	    	<table class="form-table">

		      	<tr valign="top">
			        <th scope="row">How much photos in sidebar?</th>
			        <td><input name="randimage_amount" type="text" value="<?php echo stripslashes($randimage_config['amount']);?>" size="3" /> <em>(default: 2)</em></td>
		      	</tr>
		      	<tr valign="top">
			        <th scope="row">Link picture to ...</th>
			        <td><select name="randimage_linkto">
						<?php if($randimage_config['linkto'] == "image") { ?>
						<option value="image">gallery image</option>
						<option value="post">post</option>
						<?php } else { ?>
						<option value="post">post</option>
						<option value="image">gallery image</option>
						<?php } ?>
					</select></td>
		      	</tr>
		      	<tr valign="top">
			        <th scope="row">HTML before</th>
			        <td><input name="randimage_before" type="text" value="<?php echo stripslashes($randimage_config['before']);?>" size="20" /> <em>(default: &lt;li&gt;)</em></td>
		      	</tr>
		      	<tr valign="top">
			        <th scope="row">Spacer</th>
			        <td><input name="randimage_spacer" type="text" value="<?php echo stripslashes($randimage_config['spacer']);?>" size="20" /> <em>(default: &lt;/li&gt;&lt;li&gt;)</em></td>
		      	</tr>
		      	<tr valign="top">
			        <th scope="row">HTML behind</th>
			        <td><input name="randimage_behind" type="text" value="<?php echo stripslashes($randimage_config['behind']);?>" size="20" /> <em>(default: &lt;/li&gt;)</em></td>
		      	</tr>

	    	</table>
	    	
	    	<h3>Registration</h3>	    	
	
	    	<table class="form-table">
			<tr>
				<th scope="row" valign="top">Why</th>
				<td>For fun and as an experiment i would like to gather some information and develop a simple stats system for it. I would like to ask you to participate in this experiment. All it takes for you is to not opt-out. More information is found <a href="http://meandmymac.net/plugins/data-project/" title="http://meandmymac.net/plugins/data-project/ - New window" target="_blank">here</a>. Any questions can be directed to the <a href="http://forum.at.meandmymac.net/" title="http://forum.at.meandmymac.net/ - New window" target="_blank">forum</a>.</td>
				
			</tr>
			<tr>
				<th scope="row" valign="top">Participate</th>
				<td><input type="checkbox" name="randimage_register" <?php if($randimage_tracker['register'] == 'Y') { ?>checked="checked" <?php } ?> /> Allow Meandmymac.net to collect some data about the plugin usage and your blog.<br /><em>This includes your blog name, blog address, email address and a selection of triggered events as well as the name and version of this plugin.</em></td>
			</tr>
			<tr>
				<th scope="row" valign="top">Anonymously</th>
				<td><input type="checkbox" name="randimage_anonymous" <?php if($randimage_tracker['anonymous'] == 'Y') { ?>checked="checked" <?php } ?> /> Your blog name, blog address and email will not be send.</td>
			</tr>
			<tr>
				<th scope="row" valign="top">Agree</th>
				<td><strong>Upon activating the plugin you agree to the following:</strong>

				<br />- All gathered information, but not your email address, may be published or used in a statistical overview for reference purposes.
				<br />- You're free to opt-out or to make any to be gathered data anonymous at any time.
				<br />- All acquired information remains in my database and will not be sold, made public or otherwise spread to third parties.
				<br />- If you opt-out or go anonymous, all previously saved data will remain intact.
				<br />- Requests to remove your data or make everything you sent anonymous will not be granted unless there are pressing issues.
				<br />- Anonymously gathered data cannot be removed since it's anonymous.
				</td>
			</tr>
	    	</table>
	    	
		    <p class="submit">
		      	<input type="submit" name="Submit" value="Update Options &raquo;" />
		    </p>
		</form>
	</div>
<?php
}	

/*-------------------------------------------------------------
 Name:      randimage_sidebar

 Purpose:   Fetch and show random images from the WP media lib
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function randimage_sidebar() {
	global $wpdb, $randimage_config;
		
	$SQL = "SELECT ID, post_parent FROM ".$wpdb->prefix."posts 
		WHERE post_type = 'attachment' 
		AND (post_mime_type = 'image/jpeg' OR post_mime_type = 'image/gif' OR 
		post_mime_type = 'image/png' OR post_mime_type = 'image/bmp' OR 
		post_mime_type = 'image/tiff' OR post_mime_type = 'image/x-icon')
		ORDER BY rand() LIMIT ".$randimage_config['amount'];

	$thumbs = $wpdb->get_results($SQL);

	$output = '';
	$output .= $randimage_config['before'];
	foreach($thumbs as $thumb) {
		if($randimage_config['linkto'] == "image") {
			$linkto = get_permalink($thumb->ID);
		} else {
			$SQL2 = "SELECT ID from ".$wpdb->prefix."posts
				WHERE ID = ".$thumb->post_parent." LIMIT 1";
			$temp_id = $wpdb->get_row($SQL2);
			$linkto = get_permalink($temp_id->ID);
		}
		
		$image = wp_get_attachment_image_src($thumb->ID, 'thumbnail', false);
		if($image) {
			list($src, $width, $height) = $image;
			$hwstring = image_hwstring($width, $height);
			if(is_array($size))
				$size = join('x', $size);
			$output .= '<a href="'.$linkto.'" alt=""><img src="'.attribute_escape($src).'" '.$hwstring.'class="attachment-'.attribute_escape($size).'" alt="" /></a>';
			$output .= $randimage_config['spacer'];
		}
	}
	$output .= $randimage_config['behind'];
	
	echo stripslashes(html_entity_decode($output));
}

/*-------------------------------------------------------------
 Name:      randimage_widget_init

 Purpose:   RandImage widget for the sidebar
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function randimage_widget_init() {

	if ( !function_exists('register_sidebar_widget') )
		return;
	if ( !function_exists('randimage_sidebar') )
		return;

	function randimage_widget($args) {
		extract($args);

		echo $before_widget . $before_title . $after_title;
		$url_parts = parse_url(get_bloginfo('home'));
		randimage_sidebar();
		echo $after_widget;
	}

	$widget_ops = array('classname' => 'randimage_widget', 'description' => "Options are found on the 'settings > RandImage' panel!" );
	wp_register_sidebar_widget('RandImage', 'RandImage', 'randimage_widget', $widget_ops);
}

/*-------------------------------------------------------------
 Name:      randimage_activate

 Purpose:   Activation script
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function randimage_activate() {
	randimage_send_data('Activate');
}

/*-------------------------------------------------------------
 Name:      aqontrol_deactivate

 Purpose:   Deactivation script
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function randimage_deactivate() {
	randimage_send_data('Deactivate');
}

/*-------------------------------------------------------------
 Name:      aqontrol_send_data

 Purpose:   Register events at meandmymac.net's database
 Receive:   $action
 Return:    -none-
-------------------------------------------------------------*/
function randimage_send_data($action) {
	$aqontrol_tracker = get_option('aqontrol_tracker');
	
	// Prepare data
	$date			= date('U');
	$plugin			= 'RandImage';
	$version		= '0.4.1';
	//$action -> pulled from function args
	
	// User choose anonymous?
	if($aqontrol_tracker['anonymous'] == 'Y') {
		$ident 		= 'Anonymous';
		$blogname 	= 'Anonymous';
		$blogurl	= 'Anonymous';
		$email		= 'Anonymous';
	} else {
		$ident 		= md5(get_option('siteurl'));
		$blogname	= get_option('blogname');
		$blogurl	= get_option('siteurl');
		$email		= get_option('admin_email');			
	}
	
	// Build array of data
	$post_data = array (
		'headers'	=> null,
		'body'		=> array(
			'ident'		=> $ident,
			'blogname' 	=> base64_encode($blogname),
			'blogurl'	=> base64_encode($blogurl),
			'email'		=> base64_encode($email),
			'date'		=> $date,
			'plugin'	=> $plugin,
			'version'	=> $version,
			'action'	=> $action,
		),
	);

	// Destination
	$url = 'http://stats.meandmymac.net/receiver.php';

	wp_remote_post($url, $post_data);
}

/*-------------------------------------------------------------
 Name:      randimage_check_config

 Purpose:   Create or update the options
 Receive:   -none-
 Return:    -none-
-------------------------------------------------------------*/
function randimage_check_config() {
	if ( !$option = get_option('randimage_config') ) {
		// Default Options
		$option['amount'] 					= 2;
		$option['before'] 					= '<li>';
		$option['spacer']					= '</li><li>';
		$option['behind'] 					= '</li>';
		$option['linkto'] 					= 'image';
		update_option('randimage_config', $option);
	}
	
	if ( !$tracker = get_option('randimage_tracker') ) {
		$tracker['register']				= 'Y';
		$tracker['anonymous']				= 'N';
		update_option('randimage_tracker', $tracker);
	}
}

/*-------------------------------------------------------------
 Name:      randimage_options_submit

 Purpose:   Save options
 Receive:   $_POST
 Return:    -none-
-------------------------------------------------------------*/
function randimage_options_submit() {
	$buffer = get_option('randimage_config');

	//options page
	$option['amount'] 				= trim($_POST['randimage_amount'], "\t\n ");
	$option['before'] 				= htmlspecialchars(trim($_POST['randimage_before'], "\t\n "), ENT_QUOTES);
	$option['spacer'] 				= htmlspecialchars(trim($_POST['randimage_spacer'], "\t\n "), ENT_QUOTES);
	$option['behind'] 				= htmlspecialchars(trim($_POST['randimage_behind'], "\t\n "), ENT_QUOTES);
	$option['linkto'] 				= trim($_POST['randimage_linkto'], "\t\n ");
	$tracker['register']			= $_POST['randimage_register'];
	$tracker['anonymous'] 			= $_POST['randimage_anonymous'];
	if($tracker['register'] == 'N' AND $buffer['register'] == 'Y') { randimage_send_data('Opt-out'); }
	update_option('randimage_config', $option);
	update_option('randimage_tracker', $tracker);
}
?>