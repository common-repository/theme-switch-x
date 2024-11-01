<?php
/**
 * Theme Switch X
 * @author  Presslify
 * @link
 * @copyright 2022
 * @package Theme Switch X
 *
 * @wordpress-plugin
 * Plugin Name: Theme Switch X
 * Plugin URI:
 * Author: Presslify
 * Author URI:
 * Version: 1.0
 * Description: Create Child Themes and change the theme quickly.
 * Text Domain: theme-switch-x
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
final class theme_swith_x{

	private static
	/**
	 * Current theme information
	 * @since 	1.0   2022-05-17    Release
	 * @access  private
	 * @var     mixed
	 */
	$current_theme = null,
	/**
	 * List of all installed themes (only names)
	 * @since		1.0		2022-05-17		Release
	 * @access  private
	 * @var     array
	 */
	$themes_list = [],
		/**
		 * List of all installed themes (only names)
		 * @since		1.0		2022-05-17		Release
		 * @access  private
		 * @var     array
		 */
	$themes_array = [],
	/**
	 * The real theme that is active
	 * @since		1.0		2022-05-17		Release
	 * @access  private
	 * @var     string
	 */
	$theme_activated = '',
	/**
	 * Save the extension type of the screenshot
	 * @since		1.0		2022-05-17		Release
	 * @access  private
	 * @var     string
	 */
	$format_screenshot = '',
	/**
	 * Data sent by ajax
	 * @since		.0		2022-05-17		Release
	 * @access	private
	 * @var		array
	 */
	$request = [],
	/**
	 * Plugin url public
	 * @since		1.0		2022-05-17		Release
	 * @access	private
	 * @var		string
	 */
	$plugin_url = '',
	/**
	 * Systems variables
	 * @since		1.0		2022-05-17		Release
	 * @access	private
	 * @var			string
	 */
	$action = '',$stylesheet = '',$themesc_template = '',$themesc_template_change = '',$themesc_stylesheet = '',
	$SERVER_HTTPS = '', $SERVER_HTTP_HOST = '', $SERVER_SCRIPT_NAME = '', $SERVER_REQUEST_URI = '', $HTTP_REFERER = '';

	/**
	 * Obtains the variables of systems in the first instance
	 *
	 * @since	1.0		2022-05-16		Release
	 */
	private static function getVars() {
		if( ! self::$action || ! self::$SERVER_HTTPS ){
			self::$action = sanitize_text_field( (isset($_GET['action']) ? $_GET['action'] : '' ) );
			self::$stylesheet = sanitize_text_field( (isset($_GET['stylesheet']) ? $_GET['stylesheet'] : '' ) );
			self::$themesc_template = sanitize_text_field( (isset($_COOKIE['themesc_template']) ? $_COOKIE['themesc_template'] : '' ) );
			self::$themesc_template_change = sanitize_text_field( (isset($_COOKIE['themesc_template_change']) ? $_COOKIE['themesc_template_change'] : '' ) );
			self::$themesc_stylesheet = sanitize_text_field( (isset($_COOKIE['themesc_stylesheet']) ? $_COOKIE['themesc_stylesheet'] : '' ) );

			self::$SERVER_HTTPS = sanitize_text_field( isset( $_SERVER['HTTPS'] ) ? $_SERVER['HTTPS'] : '' );
			self::$SERVER_HTTP_HOST = sanitize_text_field( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '' );
			self::$SERVER_SCRIPT_NAME = sanitize_text_field( isset( $_SERVER['SCRIPT_NAME'] ) ? $_SERVER['SCRIPT_NAME'] : '' );
			self::$SERVER_REQUEST_URI = sanitize_text_field( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' );
			self::$HTTP_REFERER = sanitize_text_field( isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '' );
		}
	}

	/**
	 * Get all parent and children themes
   * with their respective parameters.
	 *
	 * @since	1.0		2022-05-17		Release
	 *
	 * @return  void
	 */
  private static function get_themes() {
		global $pagenow;

		// ─── Url plugin ────────
		self::$plugin_url = plugin_dir_url( __FILE__ );

		// ─── Get current theme information ────────
		self::$current_theme = wp_get_theme();
		$current_theme_id = self::$current_theme->get_stylesheet();

		// ─── Get all the themes ────────
		$themes = wp_get_themes();
		if ( ! isset( $themes ) || ! is_array( $themes ) ) { return; }

		$count = 1;
		foreach ($themes as $value) {

			$count++;
			$template   = $value->get_template();
			$stylesheet = $value->get_stylesheet();
			$screenshot = $value->get_screenshot();

			$activated_theme = self::$theme_activated == $stylesheet ?  '<span class="tsc-current-actived">ACTIVATED</span>' : '';
			$link_change = $pagenow == 'themes.php' ? '' :  'themeSC_change_theme("'. $stylesheet .'","'. $template .'"); return false;';
			$data = array(
				'id'         => urlencode( str_replace( '/', '-', strtolower( $stylesheet ) ) ),
				'name'       => $value['Name'] . $activated_theme,
				'link'        => '#',
				'template'    => $template,
				'meta'        => array( 'class' => ( $stylesheet == $current_theme_id ? 'theme-sc-current_theme' : '' ), 'onclick' => $link_change , ),
				'stylesheet'  => $stylesheet,
				'img'         => $screenshot,
				'author'      => $value->get('Author'),
				'author_link' => $value->get('AuthorURI'),
				'theme_url'   => $value->get('ThemeURI'),
				'version'     => $value->get('Version'),
				'description' => $value->get('Description'),
				// 'type' is set later
				// 'active' is set later
			);

			// Verify parent or child
			if ($template == $stylesheet) {
				$data['type'] = 'parent';
			} else {
				$data['type'] = 'child';
			}

			// add the theme to the lists
			self::$themes_array[] = $data;
			self::$themes_list [] = $data['name'];

		}

	}

	/**
	 * Create the main menu and submenus of the theme list
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @return  void
	 */
  public static function create_admin_menu(){
		global $wp_admin_bar;

		// ─── Verify if the user is allowed ────────
		if ( ! current_user_can( 'switch_themes' ) ) { return; }

		// ─── Execute the query of installed themes ────────
		self::get_themes();

		// ─── Check if there are themes to display ────────
		if ( empty(self::$themes_array ) ) { return; }

		// ─── Initialize variables ────────
		$child_themes  = [];
		$parent_themes = [];

		// ─── Current theme name ────────
		$menu_label = self::$current_theme->display( 'Name' );
		$menu_label_ex = '<span class="ab-icon dashicons-admin-appearance"></span><span class="ab-label">' .
						sprintf( __( 'Theme: %s', 'theme-switch-x' ) , '<strong>' . $menu_label . '</strong>' ) . '</span>';

		// ─── Put the menu ID ────────
		$menu_id = 'theme-sc-themeswitch';

		// ─── Classify themes by type ────────
		foreach (self::$themes_array as $k => $v ) {
			if ( $v['template'] != $v['stylesheet'] ) {
				$child_themes[] = $v;
			} else {
				$parent_themes[] = $v;
			}
		}

		// ─── If there are Themes children then we add a class in the parent menu ────────
		$class_main_menu = '';
		if( ! empty($child_themes) && count($child_themes)>0 ){
			$class_main_menu = 'theme-sc-parent-children';
		}

		// ─── Add menu in main bar ────────
		$wp_admin_bar->add_node( array(
			'id'    => $menu_id,
			'title' => $menu_label_ex,
			'href'  => admin_url('themes.php'),
			'meta'   => [ 'class' => $class_main_menu ]
		));

		// ─── If there are Theme children then we put the header that these are the parents ────────
		if( ! empty($child_themes) && count($child_themes)>0 ){
			$wp_admin_bar->add_node( array(
				'id'     => 'theme-sc-parent',
				'title'  => 'Parents themes ('.count($parent_themes).')',
				'href'   => '',
				'parent' => $menu_id,
				'meta'   => [ 'class' => 'theme-sc-header' ]
			));
		}

		$show_buttons = ( is_admin() ) ? true : false;
		// ─── Show the Theme parents ────────
		foreach ($parent_themes ?: [] as $value) {
			$wp_admin_bar->add_node( array(
				'id'     => 'theme-sc-' . $value['id'],
				'title'  => $value['name'] . self::get_info_theme( $value, $show_buttons ),
				'href'   => $value['link'],
				'parent' => $menu_id,
				'meta'   => $value['meta'],
			));
		}
		// ───────────────────────────

		// ─── Theme children header ────────
		if( ! empty($child_themes) && count($child_themes)>0 ){
			$wp_admin_bar->add_node( array(
				'id'     => 'theme-sc-child',
				'title'  => 'Children themes ('.count($child_themes).')',
				'href'   => '',
				'parent' => $menu_id,
				'meta'   => [ 'class' => 'theme-sc-header' ]
			));
		}
		// ─── Show the children themes ────────
		foreach ($child_themes ?: [] as $value) {
			$wp_admin_bar->add_node( array(
				'id'     => 'theme-sc-child-' . $value['id'],
				'title'  => $value['name'] . self::get_info_theme( $value, false),
				'href'   => $value['link'],
				'parent' => $menu_id,
				'meta'   => $value['meta'],
			));
		}

	}

	/**
	 * Get extra information from the Theme
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @param   array       $data           Partial Theme Information
	 * @return  string|html
	 */
	private static function get_info_theme( $data, $generate_child = true ){
		global $pagenow;
		$html = '';

		if( ! empty( $data ) ){

			// ─── Set image theme ────────
			$html .= '<div class="theme-sc-screenshot">
				<img src="'. $data['img'] .'" title="'. esc_html( $data['description'] ) .'" name="'. esc_html( $data['name'] ) .'" />';

			// ─── Button to change the current theme temporarily only for the administrator ────────
			$link_change = $pagenow == 'themes.php' ? '' : '<div class="tsc-create-child" onclick="themeSC_change_theme(\''.$data['stylesheet'].'\',\''.$data['template'].'\')"><span class="dashicons dashicons-visibility"></span>Change/See</div>';

			// ─── Set author theme with link ────────
			$author = '';
			$author = $data['author'];
			$link = ! empty( $data['theme_url'] ) ? $data['theme_url'] : ( ! empty( $data['author_link'] ) ? $data['author_link'] : '' );
			$html .= '<div class="theme-sc-screenshot-info">';
				$html .= '<div><span>Author:</span> <span class="theme-sc-screenshot-info--author">' . $author . '</span>  <span class="theme-link dashicons dashicons-external" data-url="'.$link.'"></span> </div>';
				$html .= '<div><span>Version:</span> <span  class="theme-sc-screenshot-info--version">' . $data['version'] . '</span></div>';
				if( $generate_child ){
					$html .= '<div class="theme-sc-tools">
						<div class="tsc-create-child-with-tool"><span class="dashicons dashicons-plus"></span>Custom Child</div>
						'. $link_change .'
						<input type="hidden" id="theme-sc-template-slug" value="' . $data['stylesheet'] . '" />
					</div>';
				}
			$html .= '</div>';

			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Saves the current browsed location before switch
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @return  void
	 */
	public static function before_theme_switch() {
		self::getVars();
		$url = esc_url_raw( self::$HTTP_REFERER );
		$url = parse_url($url);
		if (!empty($url['path'])) set_transient( 'theme-sc_themeswitch_lasturl', $url['path'] . ( !empty($url['query']) ? '?' . $url['query'] : '' ), 60 );
	}

	/**
	 * After changing the theme the information is stored in cookie
	 * to have the current theme updated.
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @return  void
	 */
	public static function after_theme_switch( $theme = null ) {
		self::$current_theme = wp_get_theme();
		$current_theme_stylesheet_id = self::$current_theme->get_stylesheet();
		$current_theme_template_id = self::$current_theme->get_template();
		self::update_cookie( $current_theme_stylesheet_id, 'stylesheet' );
		self::update_cookie( $current_theme_template_id );
	}

	/**
	 * Change the style of the Theme.
   * Through Cookie and only works for the administrator role for testing.
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @param   string  $current_theme  Current active theme from the database
	 * @return  string
	 */
	public static function themesc_swicth_stylesheet( $current_theme ) {
		global $pagenow;
		self::getVars();
		// ─── Get the active theme ────────
		self::$theme_activated = $current_theme;

		// ─── If you are inside the theme change page then the same activated theme returns ────────
		if( $pagenow == 'themes.php' ){
			if( !empty( self::$action ) && !empty( self::$stylesheet ) )
				self::update_cookie( $current_theme, 'stylesheet' );

			return $current_theme;

		}elseif ( ! empty( self::$themesc_template_change ) ){ // if you change the theme through the plugin
			// Use your preview theme instead
			self::delete_cookie();
			$current_theme = self::$themesc_stylesheet;
			self::update_cookie( $current_theme, 'stylesheet' );

		}elseif( ! empty( self::$themesc_stylesheet ) && ! empty(  self::$themesc_template ) ){ // If there is an active theme change cookie, then it returns
			$current_theme = self::$themesc_stylesheet;
		}

		return $current_theme;
	}

	/**
	 * Change the style of the Theme (children in case you have it).
   * Through Cookie and only works for the administrator role for testing.
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @param   string  $current_theme  Current active template from the database
	 * @return  string
	 */
	public static function themesc_swicth_template( $current_theme ) {
		global $pagenow;
		self::getVars();

		// ─── Get the active theme ────────
		self::$theme_activated = $current_theme;

		// ─── If you are inside the theme change page then the same activated theme returns ────────
		if( $pagenow == 'themes.php' ){
			if( ! empty( self::$action ) && ! empty( self::$stylesheet ) )
				self::update_cookie( $current_theme );
			return $current_theme;

		}elseif (  ! empty( self::$themesc_template_change ) && ! empty( self::$themesc_template ) ){ // if you change the theme through the plugin
			// Use your preview theme instead
			self::delete_cookie();
			$current_theme = self::$themesc_template;
			self::update_cookie( $current_theme );

		}elseif( ! empty( self::$themesc_template ) && ! empty( self::$themesc_stylesheet ) ){ // If there is an active theme change cookie, then it returns
			$current_theme = self::$themesc_template;
		}

		return $current_theme;
	}

	/**
	 * Update the theme to be displayed through a cookia for the temporary preview view
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @param   string  $value			Cookie name
	 * @param   string  $prefix			Name Prefix
	 * @return  void
	 */
	private static function update_cookie( $value = '', $prefix = 'template' ){
		setcookie( 'themesc_' . $prefix, $value, time()+84000, self::url() );
	}

	/**
	 * Delete a cookie
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @param   string  $name           Name of the cookie to delete
	 * @return  void
	 */
	private static function delete_cookie( $name = 'themesc_template_change' ){
		unset($_COOKIE[$name]);
		setcookie( $name, null, -1, self::url() );
	}

	/**
	 * Get the current path url
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @return	string
	 */
	private static function url(){
		return "/" ; // . ( self::is_localhost() ?  $parts[0] : '' );
	}

	/**
	 * Get the current domain name
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @return 	string
	 */
	private static function get_host(){
		self::getVars();

		$url = ( self::$SERVER_HTTPS === 'on' ?
				"https" : "http") . "://" . self::$SERVER_HTTP_HOST .
				self::$SERVER_REQUEST_URI;
		$parse = parse_url($url);
		if( self::is_localhost() ){
			$parts = explode("/",ltrim(self::$SERVER_SCRIPT_NAME,"/"));
			return $parse['host'] . '/' . $parts[0];
		}else{
			return $parse['host'];
		}
	}

	/**
	 * Crea el child theme con sus archivos respectivos
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @return	object
	 */
	public static function ajax_create_child_theme(){
		$result['style'] = self::create_style_css();
		$result['function'] = self::create_functions_php();
		$result['screenshot'] = self::create_screenshot_png();
		if( $result['style'] == 1 && $result['function'] == 1 && $result['screenshot'] == 1 ){
			wp_send_json_success( array( 'success' => true, 'result' => $result, 'sheetstyle' => self::$request['text_domain'] ) );
		}else{
			wp_send_json_error( array( 'success' => false, 'error' => $result, 'debug' => $_REQUEST ) );
		}
		wp_die();
	}

	/**
	 * Clean the data that is transported via ajax
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @return 	void
	 */
	private static function sanitize_data_ajax(){
		// Sanitize request ajax
		self::$request = wp_unslash( $_REQUEST );
		array_walk_recursive( self::$request, 'sanitize_text_field' );

		// Urls decodes
		self::$request['theme_url']  = urldecode(self::$request['theme_url']);
		self::$request['author_url'] = urldecode(self::$request['author_url']);
	}

	/**
	 * Create Style.css
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @return	int		1 = It worked, 0 = An error occurred
	 */
	public static function create_style_css() {
		// Call sanitize data
		self::sanitize_data_ajax();

		// style.css header content
		$txt = "";
		$txt .= "/*\n";
		$txt .= "Theme Name:\t\t" . self::$request['title'] . "\n";
		$txt .= "Description:\t" . self::$request['description'] . "\n";
		$txt .= "Theme URI:\t\t" . self::$request['theme_url'] . "\n";
		$txt .= "Author:\t\t\t" . self::$request['author'] . "\n";
		$txt .= "Author URL:\t\t" . self::$request['author_url'] . "\n";
		$txt .= "Template:\t\t" . self::$request['parent'] . "\n";
		$txt .= "Version:\t\t" . self::$request['version'] . "\n";

		// insert GPL License Terms
		//if ( self::$request['include-gpl'] == 'Yes') {
			$txt .= "License:\t\tGNU General Public License v2 or later\n";
			$txt .= "License URI:\thttp://www.gnu.org/licenses/gpl-2.0.html\n";
		//}
		$txt .= "Text Domain:\t" . self::$request['text_domain'] . "\n";
		$txt .= "*/\n\n";
		$txt .= "/* ";
		$txt .=  esc_html__('Write here your own personal stylesheet', 'theme-switch-x');
		$txt .= " */\n";
		$style_root = get_theme_root() . '/' . self::$request['text_domain'];
		$style_root_file = get_theme_root() . '/' . self::$request['text_domain'] ."/style.css";
		if ( ! is_dir( $style_root ) ) {
			// dir doesn't exist, make it
			mkdir($style_root, 0777, true);
		}

		if ( file_put_contents($style_root_file, $txt,  FILE_APPEND | LOCK_EX) ) {
			return 1;
		} else {
			return 0;
		}

	}

	/**
	 * Create functions.php
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @return	int		1 = It worked, 0 = An error occurred
	 */
	public static function create_functions_php() {
		$fn_slug = str_replace( "-", "_", self::$request['text_domain'] );
		// functions.php content
		$txt = "";
		$txt .= "<?php\n";
		$txt .= "/*";
		$txt .= esc_html__('This file is part of ', 'theme-switch-x' );
		$txt .= self::$request['text_domain'] . ", " . self::$request['parent'] . " child theme.\n\n";
		$txt .= esc_html__('All functions of this file will be loaded before of parent theme functions.', 'theme-switch-x') . "\n";
		$txt .= esc_html__('Learn more at ', 'theme-switch-x') . 'https://codex.wordpress.org/Child_Themes.' . "\n\n";
		$txt .= esc_html__('Note: this function loads the parent stylesheet before, then child theme stylesheet', 'theme-switch-x') . "\n";
		$txt .= esc_html__('(leave it in place unless you know what you are doing.)', 'theme-switch-x') . "\n*/\n";
		$txt .= "
	if ( ! function_exists( 'suffice_child_enqueue_child_styles' ) ) {
	function " .  $fn_slug .  "_enqueue_child_styles() {
	// loading parent style
	wp_register_style(
		'parente2-style',
		get_template_directory_uri() . '/style.css'
	);

	wp_enqueue_style( 'parente2-style' );
	// loading child style
	wp_register_style(
		'childe2-style',
		get_stylesheet_directory_uri() . '/style.css'
	);
	wp_enqueue_style( 'childe2-style');
		}
	}\n";
		$txt .= "add_action( 'wp_enqueue_scripts', '" . $fn_slug .  "_enqueue_child_styles' );\n\n";
		$txt .= "/*";
		$txt .= esc_html__('Write here your own functions', 'theme-switch-x') . " */\n";
		$functions_php_root = get_theme_root() . '/' . self::$request['text_domain'];
		$functions_php_file = get_theme_root() . '/' . self::$request['text_domain'] ."/functions.php";

		if ( ! is_dir( $functions_php_root ) ) {
			// dir doesn't exist, make it
			mkdir($functions_php_root, 0777, true);
		}
		if (file_put_contents($functions_php_file, $txt, FILE_APPEND | LOCK_EX)) {
			return 1;
		} else {
			return 0;
		}

	}

	/**
	 * Create screenshot.png
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @return	int		1 = It worked, 0 = An error occurred
	 */
	public static function create_screenshot_png() {
		// check parent-name's screenshot.png
		$parent_screenshot = get_theme_root() . '/' . self::$request['parent'] .'/screenshot.png';

		// child-name's screenshot.png path
		$child_screenshot_path = get_theme_root() . '/' . self::$request['text_domain'] . '/screenshot.png';

		// Set Path to Font Files
		$font_path = plugin_dir_path( __FILE__ ) . 'assets/fonts/Roboto-Black.ttf';

		// Validate an exist file
		if( is_file($parent_screenshot)  ){
			// Create Image From Existing File
			$png_image = self::imagecreatefromfile( $parent_screenshot );
		}else {
			$parent_screenshot = get_theme_root() . '/' . self::$request['parent'] .'/screenshot.jpg';
			if( is_file($parent_screenshot)  ){
				$png_image = self::imagecreatefromfile( $parent_screenshot );
			}
		}

		// Get image width / height
		$dims_img = getimagesize($parent_screenshot);

		// image center coordinates
		$l_center = intval($dims_img['0']) / 2;
		$h_center = intval($dims_img['1']) / 2;

		// image-text dimensions
		$l_text = intval($dims_img['0']);
		$h_text = intval($dims_img['0'])/4;

		// Set rectangle coordinates
		$x_rect_upper_left  = 0;
		$y_rect_upper_left  = $h_center + intval($h_text)/2;
		$x_rect_lower_right = intval($dims_img['0']);
		$y_rect_lower_right = $h_center - intval($h_text)/2;

		// text coordinates
		$x_text_upper_left = $l_text*5/100 ;
		$y_text_upper_left = $h_center - $h_text*3/100;

		// shadow text coordinates
		$x_shadow_upper_left = $x_text_upper_left - 2;
		$y_shadow_upper_left = $y_text_upper_left - 2;

		// Set angle and font size
		$font_size = intval($h_center*10/100);
		$angle = 0;

		// SetUp a colour For The Text
		$white 		= imagecolorallocate( $png_image, 255, 255, 255 );
		$black 		= imagecolorallocate( $png_image,   0,   0,   0 );
		$grey 		= imagecolorallocate( $png_image, 128, 128, 128 );
		$light_blue = imagecolorallocatealpha( $png_image, 69,99,184, 20 );

		// Set Text to Be Printed On Image
		$line_1 = self::$request['title'] . "\n";
		$line_2 = "(a " . self::$request['template_name'] . " child theme)\n";

		// Print Text On Image
		imagefilledrectangle( $png_image,  $x_rect_upper_left, $y_rect_upper_left, $x_rect_lower_right, $y_rect_lower_right, $light_blue );
		imagettftext( $png_image, $font_size, $angle, $x_text_upper_left,   $y_text_upper_left,   $grey,  $font_path, $line_1 );
		imagettftext( $png_image, $font_size, $angle, $x_shadow_upper_left, $y_shadow_upper_left, $white, $font_path, $line_1 );
		imagettftext( $png_image, intval( $font_size*( 1-50/100 ) ), $angle, $x_text_upper_left, $y_text_upper_left + 80, $grey,  $font_path, $line_2 );
		imagettftext( $png_image, intval( $font_size*( 1-50/100) ), $angle, $x_shadow_upper_left, $y_shadow_upper_left + 80, $white, $font_path, $line_2 );
		// Send Image to Folder
		$result = self::$format_screenshot == 'png' ? imagepng( $png_image, $child_screenshot_path ) : imagejpeg( $png_image, $child_screenshot_path );
		if ( $result ) {
			imagedestroy( $png_image ); // Clear Memory
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Get an image type according to its format
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @param		string	$filename		File path
	 * @return	object
	 */
	private static function imagecreatefromfile( $filename ) {
		if (!file_exists($filename)) {
			throw new InvalidArgumentException('File "'.$filename.'" not found.');
		}
		switch ( strtolower( pathinfo( $filename, PATHINFO_EXTENSION ))) {
			case 'jpeg':
			case 'jpg':
				self::$format_screenshot = 'jpeg';
				return imagecreatefromjpeg($filename);
			break;
			case 'png':
				self::$format_screenshot = 'png';
				return imagecreatefrompng($filename);;
			break;
			case 'gif':
				self::$format_screenshot = 'gif';
				return imagecreatefromgif($filename);
			break;
			default:
				throw new InvalidArgumentException('File "'.$filename.'" is not valid jpg, png or gif image.');
			break;
		}
	}

	/**
	 * Function that lets you know if the host under test
	 * is in localhost or a real domain.
	 *
	 * @since		1.0		2022-05-17	Release
	 *
	 * @param   array	$whitelist	Ip localhost list
	 * @return	bool
	 */
	private static function is_localhost($whitelist = ['127.0.0.1', '::1', 'localhost']) {
		return in_array(sanitize_text_field( $_SERVER['REMOTE_ADDR'] ), $whitelist);
	}

    public static function popup(){
		if( ! current_user_can( 'administrator' ) ) return;
		$user_info = get_userdata(1);
		$display_name = $user_info->data->display_name;
        ?>
<div class="tsc-popup" host="<?php echo esc_attr(urlencode(self::get_host())); ?>" data-name-admin="<?php echo esc_attr($display_name); ?>">
	<div class="tsc-popup-wrap">
	<div class="tsc-popup-overlow">
		<img src="<?php echo esc_url( self::$plugin_url . '/assets/images/loader.svg' ); ?>" />
	</div>
    <div class="tsc-popup__header">
		<h3>Create child theme<div class="tsc-popup__header--close"><span></span><span></span></div></h3>
		<div class="tsc-popup__info">
			<span class="tsc-popup__info--title">Parent</span>
			<section class="tsc-popup__info--photo">
				<img />
			</section>
			<section class="tsc-popup__info--desc">
				<strong><span class="tsc-popup__info--desc-name">Name theme:</span> </strong> <span class="tsc-popup__info--desc-desc">Lorem ipsum dolor sit amet consectetur adipisicing elit. Eum corporis voluptatum ad aliquid aut, voluptatem accusamus! Commodi nihil quibusdam eum iure dolor, exercitationem corrupti architecto? Animi libero nesciunt quae a.</span>
				<br />
				<strong>Version: </strong><span class="tsc-popup__info--desc-version"></span> -
                <strong>Author: </strong> <span class="tsc-popup__info--desc-author"></span>
			</section>
		</div>
	</div>
	<form>
		<div class="tsc-popup__content">
			<span class="tsc-popup__info--title">My Child Custom</span>
			<div class="tsc-popup__content--row">
				<div class="tsc-popup__content--label">Name</div>
				<div class="tsc-popup__content--input"><input type="text" name="name" id="_name" /></div>
			</div>
			<div class="tsc-popup__content--row">
				<div class="tsc-popup__content--label">Folder</div>
				<div class="tsc-popup__content--input"><input type="text" name="slug" id="slug" readonly /></div>
			</div>
			<div class="tsc-popup__content--row">
				<div class="tsc-popup__content--label">Description</div>
				<div class="tsc-popup__content--input"><textarea name="description" id="description"></textarea></div>
			</div>
			<div class="tsc-popup__content--row">
				<div class="tsc-popup__content--label">Child theme url</div>
				<div class="tsc-popup__content--input"><input type="text" name="child-url" id="child-url" /></div>
			</div>
			<div class="tsc-popup__content--row">
				<div class="tsc-popup__content--label">Author</div>
				<div class="tsc-popup__content--input"><input type="text" name="author" id="author" /></div>
			</div>
			<div class="tsc-popup__content--row">
				<div class="tsc-popup__content--label">Author Url</div>
				<div class="tsc-popup__content--input"><input type="text" name="author-url" id="author-url" /></div>
			</div>
			<div class="tsc-popup__content--row">
				<div class="tsc-popup__content--label">Version</div>
				<div class="tsc-popup__content--input"><input type="text" name="version" id="version" /></div>
			</div>
		</div>
		<div class="tsc-popup__footer">
			<input type="hidden" name="theme-sc-name-template-current-selected" id="theme-sc-name-template-current-selected" />
			<input type="hidden" name="theme-sc-template-current-selected" id="theme-sc-template-current-selected" />
			<input type="hidden" id="theme-sc-url-page-themes" value="<?php echo urlencode( admin_url('themes.php') ); ?>" />
			<a href="javascript:void(0)" id="theme-sc-button-generate" class="tsc-button">Generate</a>
		</div>
	</form>
	</div>
</div>
<div class="tsc-overlow"></div>
<?php
    }

    /**
     * Function that creates the variable cookie to know what theme to display
     * this will be printed on the footer and only printed for administrator role
     *
     * @since		1.0		2022-05-17		Release
	 *
     * @return  void
     */
    public static function footer(){
        if( ! current_user_can( 'administrator' ) ) return; ?>
<script>
function themesc_createCookie(name,value,days){var expires;if(days){var date=new Date();date.setTime(date.getTime()+(days*24*60*60*1000));expires="; expires="+date.toGMTString()}else{expires=""} document.cookie=encodeURIComponent(name)+"="+encodeURIComponent(value)+expires+"; path=/"}
function themeSC_change_theme( stylesheet, template ){
    themesc_createCookie( 'themesc_stylesheet', stylesheet, 1 );
    themesc_createCookie( 'themesc_template', template, 1 );
    themesc_createCookie( 'themesc_template_change', 1, 1 );
    location.reload();
}
jQuery(document).ready(function( $ ){
    // ─── move the element a higher level to correct that it is not a clickable element ────────
    $('#wp-admin-bar-theme-sc-themeswitch .theme-sc-screenshot').each(function(){
        var final_paste = $(this).parent().parent();
        $($(this).detach()).appendTo(final_paste);
    });

    // ─── Redirect to the Theme or Author page ────────
    $('#wp-admin-bar-theme-sc-themeswitch-default .theme-link').on('click', function(){
        var link = $(this).attr('data-url');
        window.open(link, '_blank');
    });

	// ─── Open the popup ────────
	$('#wp-admin-bar-theme-sc-themeswitch .tsc-create-child-with-tool').on('click', function(){

		// Show popup
		$('.tsc-popup').addClass('tsc-active');
		$('.tsc-overlow').addClass('tsc-active');
		$('#wp-admin-bar-theme-sc-themeswitch').removeClass('hover');

		var stripHtml = function(html){
			var tmp = document.createElement("DIV");
			tmp.innerHTML = html;
			return tmp.textContent || tmp.innerText || "";
		};
		var $wrap = $(this).parent().parent().parent(),
			description = $wrap.find('img').attr('title'),
			image = $wrap.find('img').attr('src'),
			author = $wrap.find('.theme-sc-screenshot-info--author').text(),
			version = $wrap.find('.theme-sc-screenshot-info--version').text(),
			name = stripHtml($wrap.find('img').attr('name')).replace("ACTIVATED", ""),
			template = $wrap.find('#theme-sc-template-slug').val(),
			template_name = name;

        // The basic information of the selected theme is added
		$('.tsc-popup .tsc-popup__header .tsc-popup__info--photo img').attr('src', image);
        $('.tsc-popup .tsc-popup__header .tsc-popup__info--desc-name').html( name );
        $('.tsc-popup .tsc-popup__header .tsc-popup__info--desc-desc').html(description.substring(0, 160) + '...');
        $('.tsc-popup .tsc-popup__header .tsc-popup__info--desc-version').html(version);
        $('.tsc-popup .tsc-popup__header .tsc-popup__info--desc-author').html(author);

        // Information is added to the form for the creation of the child theme
		var name_child = name + ' Child';
		$('.tsc-popup .tsc-popup__content #_name').val(name_child);
		$('.tsc-popup .tsc-popup__content #slug').val( (template + '-child').toLowerCase().replace(" ", "-").replace(" ", "-") );
		$('.tsc-popup .tsc-popup__content #description').val(description);
		$('.tsc-popup .tsc-popup__content #author').val( $('.tsc-popup').attr('data-name-admin') );
		$('.tsc-popup .tsc-popup__content #author-url').val( decodeURIComponent($('.tsc-popup').attr('host'))  );
		$('.tsc-popup .tsc-popup__content #version').val('1.0');
		$('.tsc-popup .tsc-popup__content #watermark_dir').val(5);
		$('.tsc-popup .tsc-popup__content #child-url').val( decodeURIComponent($('.tsc-popup').attr('host')) );
		$('.tsc-popup #theme-sc-template-current-selected').val(template);
		$('.tsc-popup #theme-sc-name-template-current-selected').val(template_name);

		$(document).on('input', '.tsc-popup .tsc-popup__content #_name', function() {
			var value = $(this).val();
			$('.tsc-popup .tsc-popup__content #slug').val(value.replace(/ /g, '-').toLowerCase());
		});

	});

	// Generate Child Theme
	$('#theme-sc-button-generate').on('click', function(){
		// Show popup load
		$('.tsc-popup-overlow').addClass('tsc-active');
		var data = {
			'title'        : $('.tsc-popup .tsc-popup__content #_name').val(),
			'description'  : $('.tsc-popup .tsc-popup__content #description').val(),
			'theme_url'    : encodeURIComponent($('.tsc-popup .tsc-popup__content #child-url').val()),
			'author'       : $('.tsc-popup .tsc-popup__content #author').val(),
			'author_url'   : encodeURIComponent($('.tsc-popup .tsc-popup__content #author-url').val()),
			'parent'       : $('.tsc-popup #theme-sc-template-current-selected').val(),
			'version'      : $('.tsc-popup .tsc-popup__content #version').val(),
			'text_domain'  : $('.tsc-popup .tsc-popup__content #slug').val(),
			'template_name': $('.tsc-popup #theme-sc-name-template-current-selected').val(),
			'action'       : 'create-child-action',
		};

		$.ajax({
			'method': 'post',
			'url': ajaxurl,
			'data': data,
			'dataType': 'json',
			'cache': false,
			'success': function( response, textStatus ){
				if( response.success ){
					setTimeout(() => {
						window.location.href= decodeURIComponent( $('#theme-sc-url-page-themes').val() ) + '?theme=' + response.data.sheetstyle;
					}, 2500);
				}else{
					alert( "Error, please check console" );
				}
			},
			'error':function(jqXHR, textStatus, errorThrown){
				console.log(jqXHR, textStatus, errorThrown);
			}
		});
	});

	// Closed popup
	$('.tsc-popup__header--close').on('click', function(){
		$('.tsc-popup').removeClass('tsc-active');
		$('.tsc-overlow').removeClass('tsc-active');
	});
});
</script>
    <?php
    }

	/**
	 * Set the necessary CSS for the themes menu to look correctly
     * this will be printed on the footer and only printed for administrator role
	 *
	 *  @since	1.0		2022-05-17		Release
	 *
	 * @return  void
	 */
	public static function header(){
		// ─── Verify if the user is allowed ────────
		if( ! current_user_can( 'administrator' ) ) return;
?>
<style>
#wp-admin-bar-theme-sc-themeswitch-default.ab-submenu{
    padding: 0;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-header{
    background: #2b2b2b;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-header .ab-empty-item{
    height: 20px!important;
    line-height: 20px!important;
    font-size: 11px;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-screenshot{
    display: none;
    position: absolute;
    right: -250px;
    top: 0;
    width: 250px;
    height: auto;
    overflow: hidden;
    box-shadow: 1px 1px 1px #1b1b1bf5;
    background: #1b1b1bf5;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-screenshot img{
    display:block;
    max-width: 100%;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-screenshot .theme-sc-screenshot-info{
    padding: 5px;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-screenshot .theme-sc-screenshot-info > div{
    height: 18px;
    line-height: 18px;
    color:#bbbbbb;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-screenshot .theme-sc-screenshot-info > div span{
    height: 18px;
    line-height: 18px;
    color:gray;
    font-style: italic;
    margin-right: 2px;
}
#wp-admin-bar-theme-sc-themeswitch .ab-submenu li:hover .theme-sc-screenshot{
    display:block;
}
#wp-admin-bar-theme-sc-themeswitch .ab-submenu li:last-child{
    padding-bottom: 5px;
}
#wp-admin-bar-theme-sc-themeswitch .ab-submenu > li:not(.theme-sc-header) a{
    margin-left: 15px!important;
	position: relative;
}
#wp-admin-bar-theme-sc-themeswitch .ab-submenu > li:not(.theme-sc-header):hover a:after{
	position: absolute;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 0px 0px 20px 12px;
    border-color: transparent transparent transparent #32373C;
    right: -11px;
    z-index: 1;
	content: '';
}
#wp-admin-bar-theme-sc-themeswitch-default li.theme-sc-current_theme a:before{
    /* content: '✔' */
    content: '\f177';
    position: absolute;
    left: -11px;
    top: 6px;
    font-size: 11px;
    border: 1px solid;
    padding: 2px 3px 4px 2px;
    width: 11px;
    height: 9px;
}
#wp-admin-bar-theme-sc-themeswitch .ab-submenu > li:not(.theme-sc-header) .tsc-current-actived{
    /* padding: 0px 5px 2px; */
    /* background: red; */
    margin-left: 5px;
    /* padding: 10px; */
    /* background: linear-gradient(to bottom, #404040 60%,#323232 99%); */
    height: 10px;
    /* margin: 0; */
    padding: 3px 7px 3px 7px;
    border-radius: 4px;
    font-size: .6em;
    color: #fff;
    position: relative;
    top: -1px;
    letter-spacing: 1px;
    border: 1px solid gray;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-link{
    font-family: dashicons;
    z-index: 999;
    cursor: pointer;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-link:hover:before{
    color:#fff;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-tools{
    display: flex;
    flex-wrap: nowrap;
    justify-content: center;
    align-items: center;
    height: auto!important;
    margin-top: 10px;
    justify-content: space-around;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-tools > div{
	width: 40%;
	padding: 5px;
	line-height: 1.4;
	text-align: center;
	/* font-weight: 700; */
	font-size: 12px;
	color: #fff;
	cursor: pointer;
	margin-right: 1%;
	border-radius: 2px;
	margin: 0 2%;
}
#wp-admin-bar-theme-sc-themeswitch-default .tsc-create-child-with-tool{
    border: 1px solid #5E2F97;
    background: #6C2DBA;
    margin-left: 1%;
    margin-right: 0%!important;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-screenshot .tsc-create-child-with-tool > span,
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-tools .tsc-create-child > span{
	font-family: dashicons;
    font-style: normal!important;
    color: #fff!important;
    position: relative;
    top: 1px;
}
#wp-admin-bar-theme-sc-themeswitch-default .tsc-create-child-with-tool:hover{
    background-color: #8543D5;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-tools .tsc-create-child{
    background: #2DBA61;
    border: 1px solid #3a8857;
}
#wp-admin-bar-theme-sc-themeswitch-default .theme-sc-tools .tsc-create-child:hover{
    background: #62DE90;
}
#wp-admin-bar-theme-sc-themeswitch .ab-icon:before{
    top: 2px;
}
/* Popup */
.tsc-popup{
	position: fixed;
	width: 450px;
	top: 40px;
	left: calc( 50% - 225px );
	border: 1px solid #626fe394;
	background: gray;
	visibility: hidden;
	top: -1000px;
	opacity: 0;
	transition: opacity .7s ease, top 300ms linear, visibility 200ms;
	z-index: 10001;
	border-radius: 20px;
	background: #fff;
	box-shadow: 1px 1px 9px #4e4e4e59;
}
.tsc-popup-wrap{
	position: relative;
}
.tsc-popup input[type="text"]:focus,.tsc-popup textarea:focus{
	border-color: #454EA0;
    box-shadow: 0 0 0 1px #626fe3;
	outline: 4px solid transparent;
	background-color: #fff;
	/* transition: .7s ease all; */
}
.tsc-popup input[readonly]{
	background-color: silver;
}
.tsc-popup.tsc-active{
	visibility: visible;
	opacity: 1;
	top: 40px;
}
.tsc-popup input,.tsc-popup textarea{
	border-radius: 0;
	transition: .2s ease box-shadow;
	border: 1px solid #ddd;
}
.tsc-popup__info{
	display: flex;
    flex-wrap: nowrap;
    /* width: 100%; */
    padding: 10px;
    /* border: 1px solid #555d66a3; */
    box-sizing: border-box;
    font-size: .9em;
    margin: 20px;
    border-radius: 10px;
	background: #f9f9f9;
	border-bottom: 5px solid #dfdddd;
	position: relative;
}
.tsc-popup__info--photo{
	width: 25%;
}
.tsc-popup__info--photo img{
	max-width: 100%;
}
.tsc-popup__info--desc{
	width: 68%;
    line-height: 1.3;
    /* float: right; */
    padding-left: 10px;
}
.tsc-popup .tsc-popup__content{
	margin: 20px;
	margin-bottom: 0px;
    /* border: 1px solid #d4d4d4; */
    padding: 20px;
	background: #f9f9f9;
	border-bottom: 5px solid #dfdddd;
	border-radius: 10px;
	position: relative;
}
.tsc-popup .tsc-popup__content .tsc-popup__content--row{
	display: flex;
	flex-wrap: wrap;
	margin-bottom: 10px;
}
.tsc-popup .tsc-popup__content .tsc-popup__content--row .tsc-popup__content--label{
	width: 30%;
    text-align: right;
    padding-right: 20px;
    box-sizing: border-box;
}
.tsc-popup .tsc-popup__content .tsc-popup__content--row .tsc-popup__content--input{
	width: 69%;
    padding-right: 20px;
    box-sizing: border-box;
}
.tsc-popup .tsc-popup__content .tsc-popup__content--row .tsc-popup__content--input input,
.tsc-popup .tsc-popup__content .tsc-popup__content--row .tsc-popup__content--input select,
.tsc-popup .tsc-popup__content .tsc-popup__content--row .tsc-popup__content--input textarea{
	width:100%;
}
.tsc-popup .tsc-popup__content .tsc-popup__content--row .tsc-popup__content--input textarea{
	height: 55px;
    font-size: .9em;
}
.tsc-popup__header h3{
	padding-left: 20px;
	text-transform: capitalize;
}
.tsc-popup__info--title{
	position: absolute;
    top: -10px;
    left: -10px;
    padding: 3px 6px 4px;
    background: #62E3BE;
    color: hsl(0, 0%, 100%);
    line-height: 1;
    font-size: 1em;
}
.tsc-popup__header--close{
	display: inline-block;
    /* float: right; */
    width: 40px;
    height: 40px;
    position: absolute;
    right: 0;
    top: -18px;
	background: #626fe3;
	cursor: pointer;
	border-top-right-radius: 10px;
}
.tsc-popup__header--close span{
	height: 2px;
    display: inline-block;
    width: 80%;
    background: white;
    position: absolute;
    top: 50%;
    left: 10%;;
}
.tsc-popup__header--close span:nth-child(1){
	transform: rotate(45deg);
}
.tsc-popup__header--close span:nth-child(2){
	transform: rotate(-45deg);
}
.tsc-popup .tsc-button {
	cursor: pointer;
	margin: 10px;
	border-radius: 5px;
	text-decoration: none;
	padding: 10px;
	font-size: 15px;
	transition: .3s;
	-webkit-transition: .3s;
	-moz-transition: .3s;
	-o-transition: .3s;
	display: inline-block;
	color: #4563B8;
	border: 2px #4563B8 solid;
	outline: none;


	display: inline-block;
	vertical-align: middle;
	-webkit-transform: translateZ(0);
	transform: translateZ(0);
	box-shadow: 0 0 1px rgba(0, 0, 0, 0);
	-webkit-backface-visibility: hidden;
	backface-visibility: hidden;
	-moz-osx-font-smoothing: grayscale;
	position: relative;
}
.tsc-popup .tsc-button:before {
	content: '';
	position: absolute;
	border: #EBEEFB solid 4px;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	-webkit-transition-duration: 0.3s;
	transition-duration: 0.3s;
	-webkit-transition-property: top, right, bottom, left;
	transition-property: top, right, bottom, left;
}
.tsc-popup .tsc-button:hover:before, .tsc-popup .tsc-button:focus:before, .tsc-popup .tsc-button:active:before {
	top: -8px;
	right: -8px;
	bottom: -8px;
	left: -8px;
}
.tsc-popup .tsc-popup__footer{
	text-align: center;
	margin-bottom: 10px;
}
.tsc-overlow,.tsc-popup-overlow{
	position: absolute;
	z-index: 10000;
	width: 0%;
	height: 0%;
	top: 0;
	left: 0;
	background-color: #ffffff;
	opacity: 0;
	transition: 200ms linear opacity;

	display: flex;
	justify-content: center;
	align-items: center;
}
.tsc-overlow.tsc-active,.tsc-popup-overlow.tsc-active{
	opacity: .8;
	width: 100%;
	height: 100%;
}
.tsc-popup-overlow.tsc-active{
	height: 104%;
    top: -3%;
    border-radius: 15px;
}
</style>
    <?php
}

	/**
	 * HTML of the point to show at the time of installation
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @return 	string|html
	 */
	public static function wp_footer_script_pointer() {
		$pointer_content = '<h3>Notice:</h3>';
		$pointer_content .= '<p><span class="dashicons dashicons-plus"></span> <strong>Create Child Themes</strong> Hover the mouser over a Theme to see the button to quickly create a child theme.</p>';
		$pointer_content .= '<p><span class="dashicons dashicons-visibility"></span> <strong>Change Theme</strong> You can also change the Theme quickly to see your tests, this change will only be for the administrator.</p>';
	?>
	<script type="text/javascript">
	//<![CDATA[
	jQuery(document).ready( function( $ ) {
		$('#wp-admin-bar-theme-sc-themeswitch').pointer({
		pointer_id: 'theme-sc',
			content: '<?php echo $pointer_content; ?>',
			position: 'top',
			close: function( $ ) {
				// Once the close button is hit
				jQuery.post( ajaxurl, {
					user_id: <?php echo esc_attr( get_current_user_id() ); ?>,
					pointer: 'wp500_theme_sc_pointer',
					action: 'current-user-view-point'
				});
			}
		}).pointer('open');
	});
	//]]>
	</script>
	<?php
	}

	/**
	 * Script needed for the administration part
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @return 	void
	 */
	public static function admin_enqueue_scripts() {
		$dismissed = (int) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers_for_theme_sc', true ) ;
		if( empty( $dismissed )  ){
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
			add_action( 'admin_print_footer_scripts', array( __CLASS__, 'wp_footer_script_pointer') );
		}
	}

	/**
	 * Records that the help point was seen by the logged-in user
	 *
	 * @since		1.0		2022-05-17		Release
	 *
	 * @return 	void
	 */
	public static function ajax_point_viwed(){
		$request_sanitize = wp_unslash( $_REQUEST );
		array_walk_recursive( $request_sanitize, 'sanitize_text_field' );

		$user_id = $request_sanitize['user_id'];
		add_user_meta( $user_id, 'dismissed_wp_pointers_for_theme_sc', 1 );
	}
}
/*
|--------------------------------------------------------------------------
| Hooks and Filter that run only in a timely manner
|--------------------------------------------------------------------------
*/
// ─── Hook Backend ────────
add_action( 'admin_footer', ['theme_swith_x','footer'], 10 );
add_action( 'admin_footer', ['theme_swith_x','popup'], 11 );

add_action( 'wp_ajax_create-child-action', ['theme_swith_x','ajax_create_child_theme'] );
add_action( 'wp_ajax_current-user-view-point', ['theme_swith_x','ajax_point_viwed'] );

add_action( 'admin_head', ['theme_swith_x','header'], 10 );
add_action( 'admin_bar_menu',  [ 'theme_swith_x', 'create_admin_menu' ], 100  );

add_action( 'admin_enqueue_scripts', ['theme_swith_x','admin_enqueue_scripts'], 10 );


// ─── Hook Frontend ────────
add_action( 'wp_head', ['theme_swith_x','header'], 10 );
add_action( 'wp_footer', ['theme_swith_x','footer'], 10 );
add_action( 'wp_footer', ['theme_swith_x','popup'], 11 );


// ─── Hook | Both of them ────────
add_action( 'switch_theme', ['theme_swith_x','after_theme_switch']);
add_filter( 'template',  ['theme_swith_x','themesc_swicth_template'], 10 );
add_filter( 'stylesheet', ['theme_swith_x','themesc_swicth_stylesheet'], 10 );