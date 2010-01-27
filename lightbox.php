<?php
/*
Plugin Name: Lightbox M
Plugin URI: http://cheon.info/707
Description: Media types : Images |  Twitter Media |  Social Video |  Flash |  Video |  Audio |  Inline |  HTML. Lightbox M v1.0.4 by <cite><a href="http://cheon.info/707" title="Lightbox M v1.0.3 ">CheonNii</a>.</cite>
Version: 1.0.4
Author: CheonNii
Author URI: http://cheon.info

*/

// Pre-2.6 compatibility
if ( !defined('WP_CONTENT_URL') )
    define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
 
// Guess the location
$lightboxpluginpath = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__)).'/';

function lightbox_init_locale(){
	load_plugin_textdomain('lightbox', $lightboxpluginpath);
}
add_filter('init', 'lightbox_init_locale');

$lightbox_files = Array(
	'css/mediaboxAdvBlack.css',
	'css/mediaboxAdvWhite.css',
	'images/50.gif',
	'images/80.png',
	'images/BlackClose.gif',
	'images/BlackLoading.gif',
	'images/BlackNext.gif',
	'images/BlackPrevious.gif',
	'images/MinimalClose.png',
	'images/MinimalLoading.gif',
	'images/MinimalNext.png',
	'images/MinimalPrevious.png',
	'images/WhiteClose.gif',
	'images/WhiteLoading.gif',
	'images/WhiteNext.gif',
	'images/WhitePrevious.gif',
	'js/mediaboxAdv-1.2.0.js',
	'js/mootools-1.2.4-core-yc.js',
	'swf/NonverBlaster.swf',
	'swf/player.swf',
	'lightbox.php'
);

function lightbox_wp_head() {
	global $lightboxpluginpath, $post;
	$lightboxstyle = get_option("lightbox_style");
	$lightboxlb_resize = get_option("lightbox_lb_resize");
	$lightboxoff = false;
	$lightboxoffmeta = get_post_meta($post->ID,'lightboxoff',true);
	if (!is_admin() && $lightboxoffmeta == "false") {
		echo '<link rel="stylesheet" type="text/css" media="screen" href="' . $lightboxpluginpath . 'css/mediaboxAdv' . $lightboxstyle . '.css" />'."\n";
		echo '<script type="text/javascript"> var lightbox_path = \''.$lightboxpluginpath.'\'; </script>'."\n";
		echo '<script type="text/javascript" src="' . $lightboxpluginpath . 'js/mediaboxAdv-1.2.0.js"></script>'."\n";
	}
}

function lightbox_auto ($content) {
	global $post;
	$pattern[0] = "/<a(.*?)href=('|\")([A-Za-z0-9\/_\.\~\:-]*?)(\.bmp|\.gif|\.jpg|\.jpeg|\.png)('|\")([^\>]*?)>/i";
	$pattern[1] = "/<a(.*?)href=('|\")([A-Za-z0-9\/_\.\~\:-]*?)(\.bmp|\.gif|\.jpg|\.jpeg|\.png)('|\")(.*?)(rel=('|\")lightbox(.*?)('|\"))([ \t\r\n\v\f]*?)((rel=('|\")lightbox(.*?)('|\"))?)([ \t\r\n\v\f]?)([^\>]*?)>/i";
	$replacement[0] = '<a$1href=$2$3$4$5$6 rel="lightbox['.$post->ID.']">';
	$replacement[1] = '<a$1href=$2$3$4$5$6$7>';
	$content = preg_replace($pattern, $replacement, $content);
	return $content;
}

$lightbox_contitionals = get_option('lightbox_conditionals');
if (!is_admin() && is_array($lightbox_contitionals)) {
	wp_enqueue_script('mootools', $lightboxpluginpath . 'js/mootools-1.2.4-core-yc.js', array(), '1.2.4');
	wp_enqueue_script('mootools');
	wp_enqueue_script('swfobject');
	add_action('wp_head', 'lightbox_display_hook');
		function lightbox_display_hook($content='') {
		$conditionals = get_option('lightbox_conditionals');
		if ((is_home()     and $conditionals['is_home']) or
		    (is_single()   and $conditionals['is_single']) or
		    (is_page()     and $conditionals['is_page']) or
		    (is_category() and $conditionals['is_category']) or
			(is_tag() 	   and $conditionals['is_tag']) or
		    (is_date()     and $conditionals['is_date']) or
		    (is_search()   and $conditionals['is_search'])) {
		$content .=lightbox_wp_head();
		}
		if ($conditionals['is_automatic']){
		add_filter('the_content', 'lightbox_auto');
		add_filter('the_excerpt', 'lightbox_auto');
		}
		return $content;
	}
}


// Plugin config
register_activation_hook(__FILE__, 'lightbox_activation_hook');

function lightbox_activation_hook() {
	return lightbox_restore_config(False);
}

// restore defaults
function lightbox_restore_config($force=False) {

	// style
	if ($force or !is_string(get_option('lightbox_style')))
		update_option('lightbox_style', 'Black');

	// only display on single posts and pages by default
	if ($force or !is_array(get_option('lightbox_conditionals')))
		update_option('lightbox_conditionals', array(
			'is_home' => True,
			'is_single' => True,
			'is_page' => True,
			'is_category' => True,
			'is_tag' => True,
			'is_date' => True,
			'is_search' => True,
			'is_automatic' => False,
		));

}

add_action('admin_menu', 'lightbox_admin_menu');
function lightbox_admin_menu() {
	add_submenu_page('options-general.php', 'lightbox', 'Lightbox M', 8, 'lightbox', 'lightbox_submenu');
}

function lightbox_message($message) {
	echo "<div id=\"message\" class=\"updated fade\"><p>$message</p></div>\n";
}

function lightbox_upload_errors() {
	global $lightbox_files;

	$cwd = getcwd(); // store current dir for restoration
	if (!@chdir('../wp-content/plugins'))
		return __("Couldn't find wp-content/lightbox-m folder. Please make sure WordPress is installed correctly.", 'lightbox');
	if (!is_dir('lightbox-m'))
		return __("Can't find lightbox-m folder.", 'lightbox');
	chdir('lightbox-m');

	foreach($lightbox_files as $file) {
		if (substr($file, -1) == '/') {
			if (!is_dir(substr($file, 0, strlen($file) - 1)))
				return __("Can't find folder:", 'lightbox') . " <kbd>$file</kbd>";
		} else if (!is_file($file))
		return __("Can't find file:", 'lightbox') . " <kbd>$file</kbd>";
	}


	$header_filename = '../../themes/' . get_option('template') . '/header.php';
	if (!file_exists($header_filename) or strpos(@file_get_contents($header_filename), 'wp_head()') === false)
		return __("Your theme isn't set up for lightbox to load its style. Please edit <kbd>header.php</kbd> and add a line reading <kbd>&lt?php wp_head(); ?&gt;</kbd> before <kbd>&lt;/head&gt;</kbd> to fix this.", 'lightbox');

	chdir($cwd); // restore cwd

	return false;
}

function lightbox_meta() {
	global $post;
	$lightboxoff = false;
	$lightboxoffmeta = get_post_meta($post->ID,'lightboxoff',true);
	if ($lightboxoffmeta == "true") {
		$lightboxoff = true;
	}
	?>
	<input type="checkbox" name="lightboxoff" <?php if ($lightboxoff) { echo 'checked="checked"'; } ?>/> Disable Lightbox 
	<?php
}

function lightbox_option() {
	global $post;
	$lightboxoff = false;
	$lightboxoffmeta = get_post_meta($post->ID,'lightboxoff',true);
	if ($lightboxoffmeta == "true") {
		$lightboxoff = true;
	}
	if ( current_user_can('edit_posts') ) { ?>
	<fieldset id="lightboxoption" class="dbx-box">
	<h3 class="dbx-handle">lightbox</h3>
	<div class="dbx-content">
		<input type="checkbox" name="lightboxon" <?php if ($lightboxoff) { echo 'checked="checked"'; } ?>/> lightbox disabled?
	</div>
	</fieldset>
	<?php 
	}
}

function lightbox_meta_box() {
	// Check whether the 2.5 function add_meta_box exists, and if it doesn't use 2.3 functions.
	if ( function_exists('add_meta_box') ) {
		add_meta_box('lightbox','Lightbox','lightbox_meta','post');
		add_meta_box('lightbox','Lightbox','lightbox_meta','page');
	} else {
		add_action('dbx_post_sidebar', 'lightbox_option');
		add_action('dbx_page_sidebar', 'lightbox_option');
	}
}
add_action('admin_menu', 'lightbox_meta_box');

function lightbox_insert_post($pID) {
	if (isset($_POST['lightboxoff'])) {
		add_post_meta($pID,'lightboxoff',"true", true) or update_post_meta($pID, 'lightboxoff', "true");
	} else {
		add_post_meta($pID,'lightboxoff',"false", true) or update_post_meta($pID, 'lightboxoff', "false");
	}
}
add_action('wp_insert_post', 'lightbox_insert_post');

// The admin page
function lightbox_submenu() {
	global $lightbox_known_sites, $lightbox_date, $lightbox_files, $lightboxpluginpath;

	// update options in db if requested
	if ($_REQUEST['restore']) {
		check_admin_referer('lightbox-config');
		lightbox_restore_config(True);
		lightbox_message(__("Restored all settings to defaults.", 'lightbox'));
	} else if ($_REQUEST['save']) {

		check_admin_referer('lightbox-config');

		if ($_POST['usetargetblank']) {
			update_option('lightbox_usetargetblank',true);
		} else {
			update_option('lightbox_usetargetblank',false);
		}
		
		// update conditional displays
		$conditionals = Array();
		if (!$_POST['conditionals'])
			$_POST['conditionals'] = Array();
		
		$curconditionals = get_option('lightbox_conditionals');
		foreach($curconditionals as $condition=>$toggled)
			$conditionals[$condition] = array_key_exists($condition, $_POST['conditionals']);
			
		update_option('lightbox_conditionals', $conditionals);

		// update style
		if (!$_REQUEST['style'])
			$_REQUEST['style'] = "";
		update_option('lightbox_style', $_REQUEST['style']);

		lightbox_message(__("Saved changes.", 'lightbox'));
	}

	if ($str = lightbox_upload_errors())
		lightbox_message("$str</p><p>" . __("In your plugins/lightbox-m folder, you must have these files:", 'lightbox') . ' <pre>' . implode("\n", $lightbox_files) ); 

	// load options from db to display
	$style 		= stripslashes(get_option('lightbox_style'));
	$conditionals 	= get_option('lightbox_conditionals');
	$updated 		= get_option('lightbox_updated');
	$usetargetblank = get_option('lightbox_usetargetblank');
	// display options
?>
<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('lightbox-config');
?>

<div class="wrap">
	<h2><?php _e("Lightbox options", 'lightbox'); ?></h2>
	<table class="form-table">
	<tr>
		<th scope="row" valign="top">
			Lightbox style:
		</th>
		<td>
			<?php _e("Choose the lightbox style.", 'lightbox'); ?><br/>
			<div class="controlset">
                <select name="style">
                    <option value="Black"<?php if($style == 'Black') echo ' selected="selected"'; ?>><?php _e('Black style(Default)', ''); ?></option>
                    <option value="White"<?php if($style == 'White') echo ' selected="selected"'; ?>><?php _e('White style', ''); ?></option>
                </select>
            </div>
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top">
			<?php _e("Sections:", "lightbox"); ?>
		</th>
		<td>
			<?php _e("Choose which sections you want to enable lightbox on your site:", 'lightbox'); ?>
			<br/>
			<input type="checkbox" name="conditionals[is_home]"<?php echo ($conditionals['is_home']) ? ' checked="checked"' : ''; ?> /> <?php _e("Homepage", 'lightbox'); ?><br/>
			<input type="checkbox" name="conditionals[is_single]"<?php echo ($conditionals['is_single']) ? ' checked="checked"' : ''; ?> /> <?php _e("Individual blog posts", 'lightbox'); ?><br/>
			<input type="checkbox" name="conditionals[is_page]"<?php echo ($conditionals['is_page']) ? ' checked="checked"' : ''; ?> /> <?php _e('Individual Pages', 'lightbox'); ?><br/>
			<input type="checkbox" name="conditionals[is_category]"<?php echo ($conditionals['is_category']) ? ' checked="checked"' : ''; ?> /> <?php _e("Category archives", 'lightbox'); ?><br/>
			<input type="checkbox" name="conditionals[is_tag]"<?php echo ($conditionals['is_tag']) ? ' checked="checked"' : ''; ?> /> <?php _e("Tag listings", 'lightbox'); ?><br/>
			<input type="checkbox" name="conditionals[is_date]"<?php echo ($conditionals['is_date']) ? ' checked="checked"' : ''; ?> /> <?php _e("Date-based archives", 'lightbox'); ?><br/>
			<input type="checkbox" name="conditionals[is_search]"<?php echo ($conditionals['is_search']) ? ' checked="checked"' : ''; ?> /> <?php _e("Search results", 'lightbox'); ?><br/>
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top">
			<?php _e("Auto-lightbox:", "lightbox"); ?>
		</th>
		<td>
			<?php _e("Automatically add rel='lightbox[post-ID]' to images in posts. All images in a post are grouped into a lightbox set.", 'lightbox'); ?>
			<br/>
			<input type="checkbox" name="conditionals[is_automatic]"<?php echo ($conditionals['is_automatic']) ? ' checked="checked"' : ''; ?> /> <?php _e("Enable Automatic", 'lightbox'); ?><br/>
			<?php _e("You can disable the lightbox effect from the Wordpress editor.", 'lightbox'); ?>
		</td>
	</tr>
		<td>&nbsp;</td>
		<td>
			<span class="submit"><input name="save" value="<?php _e("Save", 'lightbox'); ?>" type="submit" /></span>
			<span class="submit"><input name="restore" value="<?php _e("Restore Defaults", 'lightbox'); ?>" type="submit"/></span>

		</td>
	</tr>
</table>
</div>
</form>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
<input type="hidden" name="cmd" value="_donations">
<input type="hidden" name="business" value="paypal@cheon.info">
<input type="hidden" name="lc" value="GB">
<input type="hidden" name="item_name" value="Donate to Lightbox M plugin's auther">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="no_shipping" value="1">
<input type="hidden" name="currency_code" value="USD">
<input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_LG.gif:NonHosted">
<input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online."> U.S. Dollar
</form>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
<input type="hidden" name="cmd" value="_donations">
<input type="hidden" name="business" value="paypal.cn@cheon.info">
<input type="hidden" name="lc" value="C2">
<input type="hidden" name="item_name" value="捐赠给 Lightbox M 插件的作者">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="no_shipping" value="1">
<input type="hidden" name="currency_code" value="CNY">
<input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_LG.gif:NonHosted">
<input type="image" src="https://www.paypal.com/zh_XC/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal — 最安全便捷的在线支付方式！"> 人民币
</form>
<?php
}

?>