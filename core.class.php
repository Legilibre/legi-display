<?php
/**
* Core SedLex Plugin
* VersionInclude : 3.0 
*/  

/* Prevent direct access to this file */
if (!defined('ABSPATH')) {
	exit("Sorry, you are not allowed to access this file directly.");
}

if (!class_exists('pluginSedLex')) {

	if (!defined('SL_FRAMEWORK_DIR')) {
		define('SL_FRAMEWORK_DIR', dirname(__FILE__));
	}

	$sedlex_list_scripts = array() ; 
	$sedlex_list_styles = array() ;
	 
	$sedlex_adminJavascript_tobedisplayed = true ;
	$sedlex_adminCSS_tobedisplayed = true ; 
	
	$SLtopLevel_alreadyInstalled = false ; 
	
	$SLpluginActivated = array() ; 
	
	/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
	* This PHP class aims at simplifying the developement of new plugin for Wordpress and especially if you do not know how to develop it.
	* Therefore, your plugin class should inherit from this class. Please refer to the HOW TO manual to learn more.
	* 
	* @abstract
	*/
	
	abstract class pluginSedLex {
	

		/** ====================================================================================================================================================
		 * This is our constructor, which is private to force the use of getInstance()
		 *
		 * @return void
		 */
		protected function __construct() {
			
			if ( is_callable( array($this, '_init') ) ) {
				$this->_init();
			}
						
			//Button for tinyMCE
			add_action('init', array( $this, '_button_editor'));
			add_action('parse_request', array($this,'create_js_for_tinymce') , 1);
			
			add_action('admin_menu',  array( $this, 'admin_menu'));
			add_filter('plugin_row_meta', array( $this, 'plugin_actions'), 10, 2);
			add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);
			add_action('init', array( $this, 'init_textdomain'));
			add_action('init', array( $this, 'update_plugin'));
			
			// Public Script
			add_action('wp_enqueue_scripts', array( $this, 'javascript_front'), 5);
			add_action('wp_enqueue_scripts', array( $this, 'css_front'), 5);
			if (method_exists($this,'_public_js_load')) {
				add_action('wp_enqueue_scripts', array($this,'_public_js_load'));
			}
			if (method_exists($this,'_public_css_load')) {
				add_action('wp_enqueue_scripts', array($this,'_public_css_load'));
			}
			add_action('wp_enqueue_scripts', array( $this, 'flush_js'), 10000000);
			add_action('wp_enqueue_scripts', array( $this, 'flush_css'), 10000000);

			// Admin Script
			add_action('admin_enqueue_scripts', array( $this, 'javascript_admin'), 5); // Only for SL page
			add_action('admin_enqueue_scripts', array( $this, 'css_admin'), 5);// Only for SL page
			if (method_exists($this,'_admin_js_load')) {
				add_action('admin_enqueue_scripts', array($this,'_admin_js_load'));
			}
			if (method_exists($this,'_admin_css_load')) {
				add_action('admin_enqueue_scripts', array($this,'_admin_css_load'));
			}
			add_action('admin_enqueue_scripts', array( $this, 'flush_js'), 10000000);// Only for SL page
			add_action('admin_enqueue_scripts', array( $this, 'flush_css'), 10000000);// Only for SL page
						
			// We add an ajax call for the translation class
			add_action('wp_ajax_translate_add', array('SLFramework_Translation','translate_add')) ; 
			add_action('wp_ajax_translate_modify', array('SLFramework_Translation','translate_modify')) ; 
			add_action('wp_ajax_translate_create', array('SLFramework_Translation','translate_create')) ; 
			add_action('wp_ajax_send_translation', array('SLFramework_Translation','send_translation')) ; 
			add_action('wp_ajax_update_summary', array('SLFramework_Translation','update_summary')) ; 
			
			// We add an ajax call for the parameter class
			add_action('wp_ajax_del_param', array($this,'del_param_callback')) ; 
			add_action('wp_ajax_add_param', array($this,'add_param_callback')) ; 
			
			// We add an ajax call for the feedback class
			add_action('wp_ajax_send_feedback', array('feedbackSL','send_feedback')) ; 
						
			// Enable the modification of the content and of the excerpt
			add_filter('the_content', array($this,'the_content_SL'), 1000);
			add_filter('get_the_excerpt', array( $this, 'the_excerpt_SL'),1000000);
			add_filter('get_the_excerpt', array( $this, 'the_excerpt_ante_SL'),2);
			
			// We remove some functionalities
			//remove_action('wp_head', 'feed_links_extra', 3); // Displays the links to the extra feeds such as category feeds
			//remove_action('wp_head', 'feed_links', 2); // Displays the links to the general feeds: Post and Comment Feed
			//remove_action('wp_head', 'rsd_link'); // Displays the link to the Really Simple Discovery service endpoint, EditURI link
			//remove_action('wp_head', 'wlwmanifest_link'); // Displays the link to the Windows Live Writer manifest file.
			//remove_action('wp_head', 'index_rel_link'); // index link
			//remove_action('wp_head', 'parent_post_rel_link'); // prev link
			//remove_action('wp_head', 'start_post_rel_link'); // start link
			//remove_action('wp_head', 'adjacent_posts_rel_link_wp_head'); // Displays relational links for the posts adjacent to the current post.
			//remove_action('wp_head', 'wp_generator'); // Displays the XHTML generator that is generated on the wp_head hook, WP version
			
			$this->signature = '<p style="text-align:right;font-size:75%;">&copy; SedLex - <a href="http://www.sedlex.fr/">http://www.sedlex.fr/</a></p>' ; 
			
			$this->frmk = new coreSLframework() ;
			$this->excerpt_called_SL = false ; 			
		}
				
		/** ====================================================================================================================================================
		* In order to install the plugin, few things are to be done ...
		* This function is not supposed to be called from your plugin : it is a purely internal function called when you activate the plugin
		*  
		* If you have to do some stuff when the plgin is activated (such as update the database format), please create an _update function in your plugin
		* 
		* @access private
		* @see subclass::_update 
		* @see pluginSedLex::uninstall
		* @see pluginSedLex::deactivate
		* @param boolean $network_wide true if a network activation is in progress (see http://core.trac.wordpress.org/ticket/14170#comment:30)
		* @return void
		*/
		
		public function install ($network_wide) {
			global $wpdb;
			
			// If the website is multisite, we have to call each install manually to create the table because it is called only for the main site.
			// (see http://core.trac.wordpress.org/ticket/14170#comment:18) 

			if (function_exists('is_multisite') && is_multisite() && $network_wide ){
				$old_blog = $wpdb->blogid;
				$old_prefix = $wpdb->prefix ; 
				// Get all blog ids
				$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
				foreach ($blogids as $blog_id) {
					switch_to_blog($blog_id);
					$this->singleSite_install(str_replace($old_prefix, $wpdb->prefix, $this->table_name)) ; 
				}
				switch_to_blog($old_blog);
			} else {
				$this->singleSite_install($this->table_name) ; 
			}
		}
		
		/** ====================================================================================================================================================
		* In order to install the plugin, few things are to be done ...
		* This function is not supposed to be called from your plugin : it is a purely internal function called when you activate the plugin
		* 
		* @access private
		* @see subclass::_update 
		* @see pluginSedLex::uninstall_removedata
		* @see pluginSedLex::deactivate
		* @param string $table_name the SQL table name for the plugin
		* @return void
		*/

		public function singleSite_install($table_name) {
			global $wpdb ; 
			global $db_version;
			
			if (strlen(trim($this->tableSQL))>0) {
				if($wpdb->get_var("show tables like '".$table_name."'") != $table_name) {
					$sql = "CREATE TABLE " . $table_name . " (".$this->tableSQL. ") DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;";
			
					require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
					dbDelta($sql);
			
					add_option("db_version", $db_version);
					
					// Gestion de l'erreur
					ob_start() ; 
					$wpdb->print_error();
					$result = ob_get_clean() ; 
					if (strlen($result)>0) {
						echo $result ; 
						die() ; 
					}
				}
			}
				
			if (method_exists($this,'_update')) {
				$this->_update() ; 
			}
		}
		
		/** ====================================================================================================================================================
		* In order to update the plugin, few things are to be done ...
		* This function is not supposed to be called from your plugin : it is a purely internal function called when you activate the plugin
		* 
		* @access private
		* @see subclass::_update 
		* @see pluginSedLex::uninstall_removedata
		* @see pluginSedLex::deactivate
		* @param string $table_name the SQL table name for the plugin
		* @return void
		*/

		public function update_plugin() {
			if (method_exists($this,'_update')) {
				$this->_update() ; 
			}
		}

		/** ====================================================================================================================================================
		* Get the plugin ID
		* 
		* @return string the plugin ID string. the string will be empty if it is not a plugin (i.e. the framework)
		*/
		public function getPluginID () {
			$tmp = $this->pluginID ; 
			if ($tmp=="coreSLframework") 
				return "" ; 
			return $tmp ; 
		}
	
		/** ====================================================================================================================================================
		* In order to deactivate the plugin, few things are to be done ... 
		* This function is not supposed to be called from your plugin : it is a purely internal function called when you de-activate the plugin
		* 
		* For now the function does nothing (but have to be declared)
		* 
		* @access private
		* @see pluginSedLex::install
		* @see pluginSedLex::uninstall_removedata
		* @return void
		*/
		public function deactivate () {
			//Nothing to do
		}
		
		/** ====================================================================================================================================================
		* Get the value of an option of the plugin
		* 
		* For instance: <code> echo $this->get_param('opt1') </code> will return the value of the option 'opt1' stored for this plugin. Please note that two different plugins may have options with the same name without any conflict.
		*
		* @see  pluginSedLex::set_param
		* @see  pluginSedLex::get_name_params
		* @see  pluginSedLex::del_param
		* @see SLFramework_Parameters::SLFramework_Parameters
		* @param string $option the name of the option
		* @return mixed  the value of the option requested
		*/
		
		public function get_param($option) {
		
			if (is_multisite() && preg_match('/^global_/', $option)) {
				$options = get_site_option($this->pluginID.'_options');
			} else {
				$options = get_option($this->pluginID.'_options');
			}
			
			if (!isset($options[$option])) {
				if ( (is_string($this->get_default_option($option))) && (substr($this->get_default_option($option), 0, 1)=="*") ) {
					$options[$option] = substr($this->get_default_option($option), 1) ; 
				} else {
					$options[$option] = $this->get_default_option($option) ; 
				}
				// Update with the default value
				if (is_multisite() && preg_match('/^global_/', $option)) {
					update_site_option($this->pluginID.'_options', $options);
				} else {
					update_option($this->pluginID.'_options', $options);
				}
			} 
			
			return $options[$option] ;
		}
		
		/** ====================================================================================================================================================
		* Get the value of an option of the plugin (macro)
		* 
		* For instance: <code> echo $this->get_param_macro('opt1') </code> will return all values of the option 'opt1' stored for this plugin. Please note that two different plugins may have options with the same name without any conflict.
		*
		* @see  pluginSedLex::set_param
		* @see  pluginSedLex::get_name_params
		* @see  pluginSedLex::del_param
		* @see SLFramework_Parameters::SLFramework_Parameters
		* @param string $option the name of the option
		* @return mixed  the value of the option requested
		*/
		
		public function get_param_macro($option) {
			$i = 0 ; 
			$results = array() ; 
			
			if (is_multisite() && preg_match('/^global_/', $option)) {
				$options = get_site_option($this->pluginID.'_options');
			} else {
				$options = get_option($this->pluginID.'_options');
			}

			while (isset($options[$option."_macro".($i)])) {
				$results[] = $options[$option."_macro".($i)] ; 
				$i++ ; 
			}
			
			return $results ; 
		}

		
		/** ====================================================================================================================================================
		* Get name of all options
		* 
		* For instance: <code> echo $this->get_name_params() </code> will return an array with the name of all the options of the plugin
		*
		* @see  pluginSedLex::set_param
		* @see  pluginSedLex::get_param
		* @see  pluginSedLex::del_param
		* @see SLFramework_Parameters::SLFramework_Parameters
		* @return array an array with all option names
		*/
		
		public function get_name_params() {
			if (is_multisite()) {
				$options = get_site_option($this->pluginID.'_options');
			} else {
				$options = get_option($this->pluginID.'_options');
			}
			
			if (is_array($options)) {
				$results = array() ; 
				foreach ($options as $o => $v) {
					$results[] = $o ; 
				}
				return $results ; 
			} else {
				return array() ; 
			}
		}
		
		/** ====================================================================================================================================================
		* Delete an option of the plugin
		* 
		* For instance: <code> echo $this->get_param('opt1') </code> will return the value of the option 'opt1' stored for this plugin. Please note that two different plugins may have options with the same name without any conflict.
		*
		* @see  pluginSedLex::set_param
		* @see  pluginSedLex::get_name_params
		* @see  pluginSedLex::gel_param
		* @see SLFramework_Parameters::SLFramework_Parameters
		* @param string $option the name of the option
		* @param string $pluginID the plugin ID (or the current plugin ID by default)
		* @return void
		*/
		
		public function del_param($option, $pluginID="") {
			if ($pluginID=="") {
				$pluginID = $this->pluginID ; 
			}
			
			if (is_multisite()) {
				$options = get_site_option($pluginID.'_options');
			} else {
				$options = get_option($pluginID.'_options');
			}
		
			// We handle the case where it is a macro  param
			if (preg_match("/^(.*)_macro([0-9]*)$/", $option, $match)) {
				
				$name_param = $match[1] ; 
				$from_int = intval($match[2]) ; 
				$i = $from_int+1 ; 
				// We shift all the variable name
				while (isset($options[$name_param."_macro".($i)])) {
					$options[$name_param."_macro".($i-1)] = $options[$name_param."_macro".($i)] ; 
					$i++ ; 
				}
				if (isset($options[$name_param."_macro".($i-1)])) {
					unset($options[$name_param."_macro".($i-1)]) ; 
				}
				if ($i==1) {
					$instance_plugin = call_user_func(array($pluginID, 'getInstance'));  ; 
					$options[$name_param."_macro0"] = $instance_plugin->get_default_option($name_param) ; 
				}
			} else {
				// It is not a macro param, then we just unset it
				if (isset($options[$option])) {
					unset($options[$option]) ; 
				}
			}
			
			if (is_multisite()) {
				update_site_option($pluginID.'_options', $options);
			} else {
				update_option($pluginID.'_options', $options);
			}
			return ;
		}
		
		/** ====================================================================================================================================================
		* Callback to remove a parameter 
		*
		* It will also remove any comment for the same
		*
		* @access private
		* @return void
		*/

		function del_param_callback()  {
			global $_POST ; 
			$options = $_POST['param'] ; 
			$pluginID = $_POST['pluginID'] ; 
			
			foreach ($options as $o) {
				$this->del_param($o, $pluginID) ; 
			}

			echo "ok" ; 
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Callback to add a parameter 
		*
		* It will also remove any comment for the same
		*
		* @access private
		* @return void
		*/

		function add_param_callback()  {
			global $_POST ; 
			$options = $_POST['param'] ; 
			$pluginID = $_POST['pluginID'] ; 
			
			if (is_multisite()) {
				$options_to_be_updated = get_site_option($pluginID.'_options');
			} else {
				$options_to_be_updated = get_option($pluginID.'_options');
			}
			
			foreach ($options as $o) {
				// We handle the case where it is a macro  param
				if (preg_match("/^(.*)_macro$/", $o, $match)) {
					$name_param = $match[1] ; 
					$i = 1 ; 
					// We shift all the variable name
					while (isset($options_to_be_updated[$name_param."_macro".($i)])) {
						$i++ ; 
					}
					$instance_plugin = call_user_func(array($pluginID, 'getInstance'));  ; 
					$options_to_be_updated[$name_param."_macro".($i)] = $instance_plugin->get_default_option($name_param) ;
				} else {
					// It is not a macro param, then we just set it
					$instance_plugin = call_user_func(array($pluginID, 'getInstance'));  ; 
					$options_to_be_updated[$name_param] = $instance_plugin->get_default_option($name_param) ;
				}
			
				if (is_multisite()) {
					update_site_option($pluginID.'_options', $options_to_be_updated);
				} else {
					update_option($pluginID.'_options', $options_to_be_updated);
				}	
			}

			echo "ok" ; 
			die() ; 
		}
		
		/** ====================================================================================================================================================
		* Set the option of the plugin
		*
		* For instance, <code>$this->set_param('opt1', 'val1')</code> will store the string 'val1' for the option 'opt1'. Any object may be stored in the options
		* 
		* @see  pluginSedLex::get_param
		* @see SLFramework_Parameters::SLFramework_Parameters
		* @param string $option the name of the option
		* @param mixed $value the value of the option to be saved
		* @return void
		*/
		public function set_param($option, $value) {
			if (is_multisite() && preg_match('/^global_/', $option)) {
				$options = get_site_option($this->pluginID.'_options');
			} else {
				$options = get_option($this->pluginID.'_options');
			}
			
			$options[$option] = $value ; 
			
			if (is_multisite() && preg_match('/^global_/', $option)) {
				update_site_option($this->pluginID.'_options', $options);
			} else {
				update_option($this->pluginID.'_options', $options);
			}
		}
		
		/** ====================================================================================================================================================
		* Create the menu & submenu in the admin section
		* This function is not supposed to be called from your plugin : it is a purely internal function called when you de-activate the plugin
		* 
		* @access private
		* @return void
		*/
		public function admin_menu() {   
		
			global $menu,$SLtopLevel_alreadyInstalled,$SLpluginActivated ;
			
			$tmp = explode('/',plugin_basename($this->path)) ; 
			$plugin = $tmp[0]."/".$tmp[0].".php" ; 
			$topLevel = "sedlex.php" ; 
			
			
			$glp = $this->frmk->get_param('global_location_plugin') ;
			$selection_pos = "std" ;  
			foreach ($glp as $a) {
				if (substr($a[0],0,1)=="*") {
					$selection_pos = $a[1] ; 
				}
			}
			
			if (!$SLtopLevel_alreadyInstalled) {
				$SLtopLevel_alreadyInstalled = true ; 
				
				if ($selection_pos=="plugins") {
					$page = add_submenu_page('plugins.php', __('About SL plugins...', 'SL_framework'), __('About SL plugins...', 'SL_framework'), 'activate_plugins', $topLevel, array($this,'sedlex_information'));
				} else if ($selection_pos=="tools") {
					$page = add_submenu_page('tools.php', __('About SL plugins...', 'SL_framework'), __('About SL plugins...', 'SL_framework'), 'activate_plugins', $topLevel, array($this,'sedlex_information'));
				} else if ($selection_pos=="settings") {
					$page = add_submenu_page('options-general.php', __('About SL plugins...', 'SL_framework'), __('About SL plugins...', 'SL_framework'), 'activate_plugins', $topLevel, array($this,'sedlex_information'));
				} else {
					//add main menu
					add_object_page('SL Plugins', 'SL Plugins', 'activate_plugins', $topLevel, array($this,'sedlex_information'));
					$page = add_submenu_page($topLevel, __('About...', 'SL_framework'), __('About...', 'SL_framework'), 'activate_plugins', $topLevel, array($this,'sedlex_information'));
				}
			}
		
			//add sub menus
			$number = "" ; 
			if (method_exists($this,'_notify')) {
				$number = $this->_notify() ; 
				if (is_numeric($number)) {
					if ($number>0) {
						$number = "<span class='update-plugins count-1' title='title'><span class='update-count'>".$number."</span></span>" ; 
					} else {
						$number = "" ; 
					}
				} else {
					$number = "" ; 
				}
			}
			
			$SLpluginActivated[] = $plugin ; 
			
			if ($selection_pos=="plugins") {
				$page = add_submenu_page('plugins.php', $this->pluginName, $this->pluginName . $number, 'activate_plugins', $plugin, array($this,'configuration_page'));			
			} else if ($selection_pos=="tools") {
				$page = add_submenu_page('tools.php', $this->pluginName, $this->pluginName . $number, 'activate_plugins', $plugin, array($this,'configuration_page'));			
			} else if ($selection_pos=="settings") {
				$page = add_submenu_page('options-general.php', $this->pluginName, $this->pluginName . $number, 'activate_plugins', $plugin, array($this,'configuration_page'));			
			} else {
				$page = add_submenu_page($topLevel, $this->pluginName, $this->pluginName . $number, 'activate_plugins', $plugin, array($this,'configuration_page'));			
			}
		}
		
		/** ====================================================================================================================================================
		* Add a link in the new link along with the standard activate/deactivate and edit in the plugin admin page.
		* This function is not supposed to be called from your plugin : it is a purely internal function 
		* 
		* @access private
		* @param array $links links such as activate/deactivate and edit
		* @param string $file the related file of the plugin 
		* @return array of new links set with a Settings link added
		*/
		public function plugin_actions($links, $file) { 
		
			// Ne pas mettre de lien pour la partie admin
			if (is_network_admin()) {
				return $links ;
			}
			
			$tmp = explode('/',plugin_basename($this->path)) ; 
			$plugin = $tmp[0]."/".$tmp[0].".php" ; 
			if ($file == $plugin) {
				return array_merge(
					$links,
					array( '<a href="admin.php?page='.$plugin.'">'. __('Settings', 'SL_framework') .'</a>')
				);
			}
			
			return $links;
		}
		
		/** ====================================================================================================================================================
		* Handler for the 'plugin_action_links' hook. Adds a "Settings" link to this plugin's entry
		* on the plugin list.
		*
		* @access private
		* @param array $links
		* @param string $file
		* @return array
		*/
		function plugin_action_links($links, $file) {
			$tmp = explode('/',plugin_basename($this->path)) ; 
			$plugin = $tmp[0]."/".$tmp[0].".php" ; 
			if ($file == $plugin) {
				return array_merge(
					$links,
					array( '<a href="admin.php?page='.$plugin.'">'. __('Settings', 'SL_framework') .'</a>')
				);
			}
			return $links;
		}
		
		/** ====================================================================================================================================================
		* Translate the plugin with international settings
		* This function is not supposed to be called from your plugin : it is a purely internal function
		*
		* In order to enable translation, please add .mo and .po files in the /lang folder of the plugin
		*		
		* @access private
		* @return void
		*/
		public function init_textdomain() {
			load_plugin_textdomain($this->pluginID, false, dirname( plugin_basename( $this->path ) ). '/lang/') ;
			load_plugin_textdomain('SL_framework', false, dirname( plugin_basename( $this->path ) ). '/core/lang/') ;
		}
		
		/** ====================================================================================================================================================
		* Functions to add a button in the TinyMCE Editor
		*
		* @access private
		* @return void
		*/
		
		function _button_editor() {
			// Do not modify this function
			if(is_admin()){
				if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
					return;
					
				if (is_callable( array($this, 'add_tinymce_buttons') ) ) {
					if (count($this->add_tinymce_buttons())>0) {
						if ( get_user_option('rich_editing') == 'true') {
							add_filter('mce_external_plugins', array($this, 'add_custom_button'));
							add_filter('mce_buttons', array($this, 'register_custom_button'), 999 );
							add_filter('tiny_mce_version', array($this, 'my_refresh_mce'));
						}
					}
				}
			}
		}
		
		function register_custom_button($buttons) {
			// Do not modify this function
			if (is_callable( array($this, 'add_tinymce_buttons') ) ) {
				if (count($this->add_tinymce_buttons())>0) {
					array_push($buttons, "|");
				}
				$i = 0 ; 
				foreach ($this->add_tinymce_buttons() as $button) {
					$i++ ; 
					array_push($buttons, "customButton_".$this->pluginID."_".$i) ;
				}
			}
			
			return $buttons;
		}
	
		function add_custom_button($plugin_array) {
			if (is_callable( array($this, 'add_tinymce_buttons') ) ) {
				if (count($this->add_tinymce_buttons())>0) {
					$plugin_array["customPluginButtons_".$this->pluginID] = site_url()."/?output_js_tinymce=customPluginButtons_".$this->pluginID ; 
				}
			}
			return $plugin_array;
		}
		
		function my_refresh_mce($ver) {
			if (is_callable( array($this, 'add_tinymce_buttons') ) ) {
				if (count($this->add_tinymce_buttons())>0) {
					$ver += 1;
				}
			}
			return $ver;
		}
		
		function create_js_for_tinymce() {
			if ((isset($_GET["output_js_tinymce"]))&&($_GET["output_js_tinymce"]=="customPluginButtons_".$this->pluginID)) {
				?>
				(function(){
					tinymce.create('tinymce.plugins.<?php echo "customPluginButtons_".$this->pluginID ; ?>', {
				 
						init : function(ed, url){
						<?php 
						$i = 0 ; 	
						foreach ($this->add_tinymce_buttons() as $button) { 
							$i++ ; 
						?>
							ed.addCommand('<?php echo "customButton_".$this->pluginID."_".$i ; ?>', function(){
								selected_content = tinyMCE.activeEditor.selection.getContent();
								tinyMCE.activeEditor.selection.setContent('<?php echo $button[1] ; ?>' + selected_content + '<?php echo $button[2] ; ?>');
							});
							
							ed.addButton('<?php echo "customButton_".$this->pluginID."_".$i ; ?>', {
								title: '<?php echo $button[0] ; ?>',
								image: '<?php echo $button[3] ; ?>',
								cmd: '<?php echo "customButton_".$this->pluginID."_".$i ; ?>'
							});
						<?php } ?>
						},
						createControl : function(n, cm){
							return null;
						}
					});
					tinymce.PluginManager.add('<?php echo "customPluginButtons_".$this->pluginID ; ?>', tinymce.plugins.<?php echo "customPluginButtons_".$this->pluginID ; ?>);
				})();
				<?php
				die() ; 
			}
		}
		
		/** ====================================================================================================================================================
		* Add a javascript file in the header
		* 
		* For instance, <code> $this->add_js('http://www.monserveur.com/wp-content/plugins/my_plugin/js/foo.js') ; </code> will add the 'my_plugin/js/foo.js' in the header.
		* In order to save bandwidth and boost your website, the framework will concat all the added javascript (by this function) and serve the browser with a single js file 
		* Note : you have to call this function in function <code>your_function</code> called by <code>add_action('wp_print_scripts', array( $this, 'your_function'));</code>
		*
		* @param string $url the complete http url of the javascript (this javascript should be an internal javascript i.e. stored by your blog and not, for instance, stored by Google) 
		* @see pluginSedLex::add_inline_js
		* @see pluginSedLex::flush_js
		* @return void
		*/
		
		public function add_js($url) {
			global $sedlex_list_scripts ; 
			$sedlex_list_scripts[] = str_replace(plugin_dir_url("/"),WP_PLUGIN_DIR,$url) ; 
		}
		
		/** ====================================================================================================================================================
		* Add inline javascript in the header
		* 
		* For instance <code> $this->add_inline_js('alert("foo");') ; </code>
		* In order to save bandwidth and boost your website, the framework will concat all the added javascript (by this function) and serve the browser with a single js file 
		* Note : you have to call this function in function <code>your_function</code> called by <code>add_action('wp_print_scripts', array( $this, 'your_function'));</code>
		*
		* @param string $text the javascript to be inserted in the header (without any <script> tags)
		* @see pluginSedLex::add_js
		* @see pluginSedLex::flush_js
		* @return void
		*/
		
		public function add_inline_js($text) {
			global $sedlex_list_scripts ; 
			$id = sha1($text) ; 
			// Repertoire de stockage des css inlines
			$path =  WP_CONTENT_DIR."/sedlex/inline_scripts";
			$path_ok = false ; 
			if (!is_dir($path)) {
				if (@mkdir("$path", 0755, true)) {
					$path_ok = true ; 				
				} else {
					SLFramework_Debug::log(get_class(), "The folder ". WP_CONTENT_DIR."/sedlex/inline_scripts"." cannot be created", 2) ; 
				}
			} else {
				$path_ok = true ; 
			}
			
			// On cree le machin
			if ($path_ok) {
				$css_f = $path."/".$id.'.js' ; 
				if (!@is_file($css_f)) {
					@file_put_contents($css_f, $text) ; 
				}
				@chmod($css_f, 0755);
				$sedlex_list_scripts[] = $css_f ; 
			} else {
				echo "\n<script type='text/javascript'>\n" ; 
				echo $text ; 
				echo "\n</script>\n" ; 
			}
		}
		
		/** ====================================================================================================================================================
		* Insert the  'single' javascript file in the page
		* This function is not supposed to be called from your plugin. This function is called automatically once during the rendering
		* 
		* @access private
		* @see pluginSedLex::add_inline_js
		* @see pluginSedLex::add_js
		* @return void
		*/
		
		public  function flush_js($hook) {
			global $sedlex_list_scripts ; 
						
			// If it not a plugin page SL page
			if (is_admin()) {
				$plugin = explode("_", $hook) ; 
				if (!isset($plugin[count($plugin)-1])) {
					return ; 
				}
				if ($plugin[count($plugin)-1]!="sedlex") {
					$plugin = explode("/", $plugin[count($plugin)-1]) ; 
					if ((!isset($plugin[0]))||(!@is_file(WP_PLUGIN_DIR."/".$plugin[0]."/core.class.php")))
						return;
				}
			}

			
			// Repertoire de stockage des css inlines
			$path =  WP_CONTENT_DIR."/sedlex/inline_scripts";
			if (!is_dir($path)) {
				if (!@mkdir("$path", 0755, true)) {
					SLFramework_Debug::log(get_class(), "The folder ". WP_CONTENT_DIR."/sedlex/inline_scripts"." cannot be created", 2) ; 
				}
			}
			
			if (!empty($sedlex_list_scripts)) {
				// We create the file if it does not exist
				$out = "" ; 
				foreach( $sedlex_list_scripts as $file ) {
					if (@is_file($file)) {
						$out .=  "\n/*====================================================*/\n";
						$out .=  "/* FILE ".str_replace(WP_CONTENT_DIR,"",$file)  ."*/\n";
						$out .=  "/*====================================================*/\n";
						$out .= @file_get_contents($file) . "\n";
					} else {
						$out .=  "\n/*====================================================*/\n";
						$out .=  "/* FILE NOT FOUND ".str_replace(WP_CONTENT_DIR,"",$file)  ."*/\n";
						$out .=  "/*====================================================*/\n";						
					}
				}
				$md5 = sha1($out) ; 
				if (!@is_file(WP_CONTENT_DIR."/sedlex/inline_scripts/".$md5.".js")) {
					@file_put_contents(WP_CONTENT_DIR."/sedlex/inline_scripts/".$md5.".js", $out) ; 
				}				
				@chmod(WP_CONTENT_DIR."/sedlex/inline_scripts/".$md5.".js", 0755);
				
				//$url = plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__)).'core/load-scripts.php?c=0&load='.$md5 ; 
				$url = content_url("/").'sedlex/inline_scripts/'.$md5.".js" ; 
				wp_enqueue_script('sedlex_scripts', $url, array() ,date('Ymd'));
				$sedlex_list_scripts = array(); 
			}
		}
				
		/** ====================================================================================================================================================
		* Insert the  admin javascript files which is located in the core (you may NOT modify these files) 
		* This function is not supposed to be called from your plugin. This function is called automatically when you are in the admin page of the plugin
		* 
		* @access private
		* @return void
		*/
		
		public function javascript_admin($hook) {
			global $sedlex_adminJavascript_tobedisplayed ; 
			
			// If it not a plugin page SL page
			$plugin = explode("_", $hook) ; 
			if (!isset($plugin[count($plugin)-1])) {
				return ; 
			}
			if ($plugin[count($plugin)-1]!="sedlex") {
				$plugin = explode("/", $plugin[count($plugin)-1]) ; 
				if ((!isset($plugin[0]))||(!@is_file(WP_PLUGIN_DIR."/".$plugin[0]."/core.class.php")))
					return;
			}	
			
			if ($sedlex_adminJavascript_tobedisplayed) {
				$sedlex_adminJavascript_tobedisplayed = false ; 
				
			//if (str_replace(basename( __FILE__),"",plugin_basename( __FILE__))==str_replace(basename( $this->path),"",plugin_basename($this->path))) {
				// For the tabs of the admin page
				wp_enqueue_script('jquery');   
				wp_enqueue_script('jquery-ui-core', '', array('jquery'), false );   
				wp_enqueue_script('jquery-ui-dialog', '', array('jquery'), false );
				wp_enqueue_script('jquery-ui-tabs', '', array('jquery'), false );
				wp_enqueue_script('jquery-ui-sortable', '', array('jquery'), false );
				wp_enqueue_script('jquery-ui-effects', '', array('jquery', 'jquery-ui'), false );
				
				// Pour accéder au media library
				wp_enqueue_script('media-upload');
				wp_enqueue_script('thickbox');
				
				echo '<script> addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!=\'function\'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};</script>'."\r\n" ; 
			
				@chmod(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/js/', 0755);
				
				$dir = @opendir(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/js/'); 
				if ($dir !== false) {
					while($file = readdir($dir)) {
						if (preg_match('@\.js$@i',$file)) {
							$path = WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/js/'.$file ; 
							$url = plugin_dir_url("/").'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/js/'.$file ; 
							if (@filesize($path)>0) {
								$this->add_js($url) ; 
							}				
						}
					}
				}
			}
			
			$name = 'js/js_admin.js' ; 
			$url = plugin_dir_url("/").'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			$path = WP_PLUGIN_DIR.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			if (file_exists($path)) {
				if (@filesize($path)>0) {
					$this->add_js($url) ; 
				}
			}
		}
				
		/** ====================================================================================================================================================
		* Insert the  admin javascript file which is located in js/js_front.js (you may modify this file in order to customize the rendering) 
		* This function is not supposed to be called from your plugin. This function is called automatically.
		* 
		* @access private
		* @return void
		*/
		
		public function javascript_front() {
			$name = 'js/js_front.js' ; 
			$url = plugin_dir_url("/").'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			$path = WP_PLUGIN_DIR.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			if (file_exists($path)) {
				if (@filesize($path)>0) {
					$this->add_js($url) ; 
				}
			}
		}
		
		/** ====================================================================================================================================================
		* Add a CSS file in the header
		* 
		* For instance,  <code>$this->add_css('http://www.monserveur.com/wp-content/plugins/my_plugin/js/foo.css') ;</code> will add the 'my_plugin/js/foo.css' in the header.
		* In order to save bandwidth and boost your website, the framework will concat all the added css (by this function) and serve the browser with a single css file 
		* Note : you have to call this function in function <code>your_function</code> called by <code>add_action('wp_print_styles', array( $this, 'your_function'));</code>
		*
		* @param string $url the complete http url of the css file (this css should be an internal javascript i.e. stored by your blog and not, for instance, stored by Google) 
		* @see pluginSedLex::add_inline_css
		* @see pluginSedLex::flush_css
		* @return void
		*/
		
		public function add_css($url) {
			global $sedlex_list_styles ; 
			$sedlex_list_styles[] = str_replace(content_url(),WP_CONTENT_DIR,$url) ; 
		}
		
		/** ====================================================================================================================================================
		* Add inline CSS in the header
		*
		* For instance,  <code> $this->add_inline_css('.head { color:#FFFFFF; }') ; </code>
		* In order to save bandwidth and boost your website, the framework will concat all the added css (by this function) and serve the browser with a single css file 
		* Note : you have to call this function in function <code>your_function</code> called by <code>add_action('wp_print_styles', array( $this, 'your_function'));</code>
		*
		* @param string $text the css to be inserted in the header (without any <style> tags)
		* @see pluginSedLex::add_css
		* @see pluginSedLex::flush_css
		* @return void
		*/
		
		public function add_inline_css($text) {
			global $sedlex_list_styles ; 
			$id = sha1($text) ; 
			// Repertoire de stockage des css inlines
			$path =  WP_CONTENT_DIR."/sedlex/inline_styles";
			$path_ok = false ; 
			if (!is_dir($path)) {
				if (@mkdir("$path", 0755, true)) {
					$path_ok = true ; 				
				} else {
					SLFramework_Debug::log(get_class(), "The folder ". WP_CONTENT_DIR."/sedlex/inline_styles"." cannot be created", 2) ; 
				}
			} else {
				$path_ok = true ; 
			}
			
			// On cree le machin
			if ($path_ok) {
				$css_f = $path."/".$id.'.css' ; 
				if (!@is_file($css_f)) {
					@file_put_contents($css_f , $text); 
				} 
				@chmod($css_f, 0755);
				$sedlex_list_styles[] = $css_f ; 
			} else {
				echo "\n<style type='text/css'>\n" ; 
				echo $text ; 
				echo "\n</style>\n" ; 
			}
		}
		
		/** ====================================================================================================================================================
		* Insert the 'single' css file in the page
		* This function is not supposed to be called from your plugin. This function is called automatically once during the rendering
		* 
		* @access private
		* @see pluginSedLex::add_inline_css
		* @see pluginSedLex::add_css
		* @return void
		*/
		
		public function flush_css($hook) {
			global $sedlex_list_styles ; 
						
			// If it not a plugin page SL page
			if (is_admin()) {
				$plugin = explode("_", $hook) ; 
				if (!isset($plugin[count($plugin)-1])) {
					return ; 
				}
				if ($plugin[count($plugin)-1]!="sedlex") {
					$plugin = explode("/", $plugin[count($plugin)-1]) ; 
					if ((!isset($plugin[0]))||(!@is_file(WP_PLUGIN_DIR."/".$plugin[0]."/core.class.php")))
						return;
				}
			}
		
			// Repertoire de stockage des css inlines

			$path =  WP_CONTENT_DIR."/sedlex/inline_styles";
			$path_ok = false ; 
			if (!is_dir($path)) {
				if (!@mkdir("$path", 0755, true)) {
					SLFramework_Debug::log(get_class(), "The folder ". WP_CONTENT_DIR."/sedlex/inline_styles cannot be created", 2) ; 
				}
			}

			if (!empty($sedlex_list_styles)) {
				// We create the file if it does not exist
				$out = "" ; 
				foreach( $sedlex_list_styles as $file ) {
					if (@is_file($file)) {
						$out .=  "\n/*====================================================*/\n";
						$out .=  "/* FILE ".str_replace(WP_CONTENT_DIR,"",$file)  ."*/\n";
						$out .=  "/*====================================================*/\n";
						$content = @file_get_contents($file) . "\n";
						// We proceed to some replacement for the image
						if (strpos($file,'/sedlex/inline_styles')!==false) {
							$out .= $content ; 
						} else if (strpos($file,'/core/css')===false) {
							$temp_path = str_replace(WP_PLUGIN_DIR."/", "", $file) ; 
							while (substr($temp_path, 0, 1)=="/") {
								$temp_path = substr($temp_path, 1) ;
							}
							list($plugin, $void) = explode('/', $temp_path , 2) ;
							$content = str_replace( '../core/img/', plugin_dir_url("/").$plugin.'/core/img/', $content );
							$out .= str_replace( '../img/', plugin_dir_url("/").$plugin.'/img/', $content );
						} else {
							$temp_path = str_replace(WP_PLUGIN_DIR."/", "", $file) ; 
							while (substr($temp_path, 0, 1)=="/") {
								$temp_path = substr($temp_path, 1) ;
							}
							list($plugin, $void) = explode('/', $temp_path, 2) ; 
							$out .= str_replace( '../img/', plugin_dir_url("/").$plugin.'/core/img/', $content );			
						}
					} else {
						$out .=  "\n/*====================================================*/\n";
						$out .=  "/* FILE NOT FOUND ".str_replace(WP_CONTENT_DIR,"",$file)  ."*/\n";
						$out .=  "/*====================================================*/\n";						
					}
				}
				$md5 = sha1($out) ; 
				if (!@is_file(WP_CONTENT_DIR."/sedlex/inline_styles/".$md5.".css")) {
					@file_put_contents(WP_CONTENT_DIR."/sedlex/inline_styles/".$md5.".css", $out) ; 
				}
				
				@chmod(WP_CONTENT_DIR."/sedlex/inline_styles/".$md5.".css", 0755);
				
				//$url = plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename( __FILE__)).'core/load-styles.php?c=0&load='.$md5 ; 
				$url = content_url("/").'sedlex/inline_styles/'.$md5.".css" ; 
				wp_enqueue_style('sedlex_styles', $url, array() ,date('Ymd'));

				$sedlex_list_styles = array(); 
			}
		}
		
		
		/** ====================================================================================================================================================
		* Insert the  admin css files which is located in the core (you may NOT modify these files) 
		* This function is not supposed to be called from your plugin. This function is called automatically when you are in the admin page of the plugin
		* 
		* @access private
		* @return void
		*/
		
		public function css_admin($hook) {
			global $sedlex_adminCSS_tobedisplayed ; 

			// If it not a plugin page SL page
			$plugin = explode("_", $hook) ; 
			if (!isset($plugin[count($plugin)-1])) {
				return ; 
			}
			if ($plugin[count($plugin)-1]!="sedlex") {
				$plugin = explode("/", $plugin[count($plugin)-1]) ; 
				if ((!isset($plugin[0]))||(!@is_file(WP_PLUGIN_DIR."/".$plugin[0]."/core.class.php")))
					return;
			}
			
			if ($sedlex_adminCSS_tobedisplayed) {
				$sedlex_adminCSS_tobedisplayed = false ; 
				
				// Pour le media box
				wp_enqueue_style('thickbox');		

			//if (str_replace(basename( __FILE__),"",plugin_basename( __FILE__))==str_replace(basename( $this->path),"",plugin_basename($this->path))) {
			
				wp_enqueue_style('wp-admin');
				wp_enqueue_style('dashboard');
				wp_enqueue_style('plugin-install');
				
				@chmod(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/css/', 0755);
				$dir = @opendir(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/css/'); 
				if ($dir!==false) {
					while($file = readdir($dir)) {
						if (preg_match('@\.css$@i',$file)) {
							$path = WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/css/'.$file ; 
							$url = plugin_dir_url("/").'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/css/'.$file ; 
							if (@filesize($path)>0) {
								$this->add_css($url) ; 
							}			
						}
					}
				}
			}
			
			$name = 'css/css_admin.css' ; 
			$url = plugin_dir_url("/").'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			$path = WP_PLUGIN_DIR.'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			if (file_exists($path)) {
				if (@filesize($path)>0) {
					$this->add_css($url) ; 
				}
			}
			
			$url_low = plugin_dir_url("/").'/'.$plugin[0] ."/img/banner-772x250" ; 
			$path_low = WP_PLUGIN_DIR.'/'.$plugin[0]."/img/banner-772x250" ; 
			$url_high = plugin_dir_url("/").'/'.$plugin[0]."/img/banner-1544x500" ; 
			$path_high = WP_PLUGIN_DIR.'/'.$plugin[0]."/img/banner-1544x500" ; 
			// Add the CSS for the configuration page
			ob_start() ; 
				?>
				.plugin-titleSL h2 {
					font-family: "Helvetica Neue",sans-serif;
					font-weight: bold; 
					font-size: 30px;
					max-width: 682px;
					position: absolute;
					left: 30px;
					bottom: 20px;
					padding: 15px;
					margin-bottom: 4px;
					color: #ffffff;
					background-color: rgba(30, 30, 30, 0.9);
					background-image: none;
					background-repeat: repeat;
					background-attachment: scroll;
					background-position: 0% 0%;
					background-clip: border-box;
					background-origin: padding-box;
					background-size: auto auto;
					text-shadow: 0px 1px 3px rgba(0, 0, 0, 0.4);
					box-shadow: 0px 0px 30px rgba(255, 255, 255, 0.1);
					border-top-left-radius: 8px;
					border-top-right-radius: 8px;
					border-bottom-right-radius: 8px;
					border-bottom-left-radius: 8px; 				
				}
				<?php if (is_file($path_low.".png")) { ?>
				.plugin-titleSL {
					position: absolute;
					top: 0px;
					left: 0px;
					width:772px;
					height:250px;
					background-image: url(<?php echo $url_low.".png" ; ?>);
					background-size:772px 250px;
					box-shadow: 0px 0px 50px 4px rgba(0, 0, 0, 0.2) inset, 0px -1px 0px rgba(0, 0, 0, 0.1) inset;
					z-index:-100;
				}
				.plugin-contentSL {
					margin-bottom:20px;					
					padding:20px ; 
					padding-top:270px ; 
				}
				
				<?php } elseif (is_file($path_low.".jpg")) { ?>
				.plugin-titleSL {
					position: absolute;
					top: 0px;
					left: 0px;
					width:772px;
					height:250px;
					background-image: url(<?php echo $url_low.".jpg" ; ?>);
					background-size:772px 250px;
					box-shadow: 0px 0px 50px 4px rgba(0, 0, 0, 0.2) inset, 0px -1px 0px rgba(0, 0, 0, 0.1) inset;
					z-index:-100;
				}
				.plugin-contentSL {
					margin-bottom:20px;					
					padding:20px ; 
					padding-top:270px ; 
				}

				<?php } else { ?>
				.plugin-titleSL {
					position: absolute;
					top: 0px;
					left: 0px;
					height:110px;
					width:772px;
					background-image: none;
					background-size:772px 110px;
					z-index:-100;
				}	
				.plugin-contentSL {
					margin-bottom:20px;					
					padding:20px ; 
					padding-top:110px ; 
				}
				<?php } ?>


				<?php if (is_file($path_high.".png")) { ?>
				@media only screen and (-webkit-min-device-pixel-ratio: 1.5) {
					.plugin-titleSL {
						background-image: url(<?php echo $url_high.".png" ; ?>);
					}
				}
				<?php } elseif (is_file($path_high.".jpg")) { ?>
				@media only screen and (-webkit-min-device-pixel-ratio: 1.5) {
					.plugin-titleSL {
						background-image: url(<?php echo $url_high.".jpg" ; ?>);
					}
				}
				<?php
				}
			$this->add_inline_css(ob_get_clean()) ;

		}

		/** ====================================================================================================================================================
		* Insert the  admin css file which is located in css/css_front.css (you may modify this file in order to customize the rendering) 
		* This function is not supposed to be called from your plugin. This function is called automatically.
		* 
		* @access private
		* @return void
		*/
		
		function css_front() {
			$name = 'css/css_front.css' ; 
			$url = plugin_dir_url("/").'/'.str_replace(basename( $this->path),"",plugin_basename($this->path)) .$name ; 
			$path = WP_PLUGIN_DIR.'/'.str_replace(basename($this->path),"",plugin_basename($this->path)) .$name ; 
			if (file_exists($path)) {
				if (@filesize($path)>0) {
					$this->add_css($url) ; 
				}
			}
		}
		
		/** ====================================================================================================================================================
		* This function displays the configuration page of the core 
		* 
		* @access private
		* @return void
		*/
		function sedlex_information() {
			global $submenu;
			global $blog_id ; 
			global $SLpluginActivated ; 
			
			if (((is_multisite())&&($blog_id == 1))||(!is_multisite())) {
				ob_start() ; 
				$params = new SLFramework_Parameters ($this->frmk) ;
				$params->add_title (__('Log options','SL_framework')) ; 
				$params->add_param ("debug_level", __('What is the debug level:','SL_framework')) ; 
				$params->add_comment ("<a href='".str_replace(WP_CONTENT_DIR, content_url(), SLFramework_Debug::get_log_path())."' target='_blank'>".__('See the debug logs','SL_framework')."</a>") ; 
				$params->add_comment (__('1=log only the critical errors;','SL_framework')) ; 
				$params->add_comment (__('2=log only the critical errors and the standard errors;','SL_framework')) ; 
				$params->add_comment (__('3=log only the critical errors, the standard errors and the warnings;','SL_framework')) ; 
				$params->add_comment (__('4=log information;','SL_framework')) ; 
				$params->add_comment (__('5=log verbose;','SL_framework')) ; 
				
				if (is_multisite()) {
					$params->add_title (__('Multisite Management','SL_framework')) ; 
					$params->add_param ("global_allow_translation_by_blogs", __('Do you want to allow sub-blogs to modify the translations of the plugins:','SL_framework')) ; 
					$params->add_comment (__("If this option is unchecked, the translation tab won't be displayed in the blog administration panel.",'SL_framework')) ; 
				}
				
				$params->add_title (__('Location of the SL plugins','SL_framework')) ; 
				$params->add_param ("global_location_plugin", __('Where do you want to display the SL plugins:','SL_framework')) ; 
	
				echo $params->flush() ; 
				$paramSave = ob_get_clean() ; 
				
				echo "<a name='top'></a>" ; 
			}
						
			//Information about the SL plugins
			?>
			<div class="wrap">
				<div id="icon-themes" class="icon32"><br/></div>
				<h2><?php echo __('Summary page for the plugins developped with the SL framework', 'SL_framework')?></h2>
			</div>
			<div style="padding:20px;">
				<?php echo $this->signature; 
				?>
				<p>&nbsp;</p>
				<?php
				
				$plugins = get_plugins() ; 
				$all_nb = 0 ; 
				foreach($plugins as $url => $data) {
					if (is_plugin_active($url)) {
						$all_nb++ ; 
					}
				}
				$sl_count = 0 ; 
				foreach ($SLpluginActivated as $ov) {
					$sl_count ++ ; 
				}
?>
				<p><?php printf(__("For now, you have installed %s plugins including %s plugins developped with the SedLex's framework",'SL_framework'), $all_nb, $sl_count-1)?><p/>
				<p><?php printf(__("The core plugin is located at %s",'SL_framework'), "<code>".str_replace(ABSPATH, "", SL_FRAMEWORK_DIR)."</code>")?><p/>
<?php
				
				//======================================================================================
				//= Tab listing all the plugins
				//======================================================================================
		
				$tabs = new SLFramework_Tabs() ; 
									
				ob_start() ; 
					$table = new SLFramework_Table() ; 
					$table->title(array(__("Plugin name", 'SL_framework'), __("Description", 'SL_framework'))) ; 
					$ligne=0 ; 
					foreach ($SLpluginActivated as $i => $url) {
						$ligne++ ; 

						$plugin_name = explode("/",$url) ;
						if (isset($plugin_name[count($plugin_name)-2])) {
							$plugin_name = $plugin_name[count($plugin_name)-2] ; 
						} else {
							$plugin_name = "?" ; 
						}
						if ($i != 0) {
							$info = pluginSedlex::get_plugins_data(WP_PLUGIN_DIR."/".$url);
							ob_start() ; 
							?>
								<p><b><?php echo $info['Plugin_Name'] ; ?></b></p>
								<p><a href='admin.php?page=<?php echo $url  ; ?>'><?php echo __('Settings', 'SL_framework') ; ?></a> | <?php echo SLFramework_Utils::byteSize(SLFramework_Utils::dirSize(dirname(WP_PLUGIN_DIR.'/'.$url ))) ;?></p>
							<?php

							$cel1 = new adminCell(ob_get_clean()) ; 
							
							ob_start() ; 
								$database = "" ; 
								if ($info['Database']!="") {
									$database = "<img src='".plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/database.png"."' alt='".__('There is a SQL database for this plugin', 'SL_framework')."'/>" ; 
								}
								?>
								<p><?php echo str_replace("<ul>", "<ul style='list-style-type:circle; padding-left:1cm;'>", $info['Description']) ; ?></p>
								<p><?php echo sprintf(__('Version: %s by %s', 'SL_framework'),$info['Version'],$info['Author']) ; ?> (<a href='<?php echo $info['Author_URI'] ; ?>'><?php echo $info['Author_URI'] ; ?></a>)<?php echo $database ; ?></p>
								<?php
							$cel2 = new adminCell(ob_get_clean()) ; 
							
							$table->add_line(array($cel1, $cel2), '1') ; 
						}
					}
					echo $table->flush() ; 
				$tabs->add_tab(__('List of SL plugins',  'SL_framework'), ob_get_clean(), plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_list.png" ) ; 
				
				if (((is_multisite())&&($blog_id == 1))||(!is_multisite())) {

					//======================================================================================
					//= Tab for parameters
					//======================================================================================
					
							
					$tabs->add_tab(__('Parameters of the framework',  'SL_framework'),  $paramSave, plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_param.png") ; 					
					
				}
				
				if (((is_multisite())&&($blog_id == 1))||(!is_multisite())||($this->frmk->get_param('global_allow_translation_by_blogs'))) {
					
					//======================================================================================
					//= Tab for the translation
					//======================================================================================
										
					ob_start() ; 
						$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
						$trans = new SLFramework_Translation("SL_framework", $plugin) ; 
						$trans->enable_translation() ; 
					$tabs->add_tab(__('Manage translation of the framework',  'SL_framework'), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_trad.png") ; 
				}
								
				echo $tabs->flush() ; 
				
				echo $this->signature; 
				
				?>
			</div>
			<?php
		}
		
		/** ====================================================================================================================================================
		* Get information on the plugin
		* For instance <code> $info = pluginSedlex::get_plugins_data(WP_PLUGIN_DIR.'/my-plugin/my-plugin.php')</code> will return an array with 
		* 	- the folder of the plugin : <code>$info['Dir_Plugin']</code>
		* 	- the name of the plugin : <code>$info['Plugin_Name']</code>		
		* 	- the tags of the plugin : <code>$info['Plugin_Tag']</code>
		* 	- the url of the plugin : <code>$info['Plugin_URI']</code>
		* 	- the description of the plugin : <code>$info['Description']</code>
		* 	- the name of the author : <code>$info['Author']</code>
		* 	- the url of the author : <code>$info['Author_URI']</code>
		* 	- the version number : <code>$info['Version']</code>
		* 	- the email of the Author : <code>$info['Email']</code>
		* 
		* @param string $plugin_file path of the plugin main file. If no paramater is provided, the file is the current plugin main file.
		* @return array information on Name, Author, Description ...
		*/

		static public function get_plugins_data($plugin_file='') {
			if ($plugin_file == "")
				$plugin_file = $this->path ; 
		
			$plugin_data = implode('', file($plugin_file));
			preg_match("|Plugin Name:(.*)|i", $plugin_data, $plugin_name);
			preg_match("|Plugin Tag:(.*)|i", $plugin_data, $plugin_tag);
			preg_match("|Plugin URI:(.*)|i", $plugin_data, $plugin_uri);
			preg_match("|Description:(.*)|i", $plugin_data, $description);
			preg_match("|Author:(.*)|i", $plugin_data, $author_name);
			preg_match("|Author URI:(.*)|i", $plugin_data, $author_uri);
			preg_match("|Author Email:(.*)|i", $plugin_data, $author_email);
			preg_match("|Framework Email:(.*)|i", $plugin_data, $framework_email);
			preg_match('|$this->tableSQL = "(.*)"|i', $plugin_data, $plugin_database);
			if (preg_match("|Version:(.*)|i", $plugin_data, $version)) {
				$version = trim($version[1]);
			} else {
				$version = '';
			}
			
			$plugins_allowedtags = array('a' => array('href' => array()),'code' => array(), 'p' => array() ,'ul' => array() ,'li' => array() ,'strong' => array());

			if (isset($plugin_name[1]))
				$plugin_name = wp_kses(trim($plugin_name[1]), $plugins_allowedtags);
			else 
				$plugin_name = "" ; 
			if (isset($plugin_tag[1]))
				$plugin_tag = wp_kses(trim($plugin_tag[1]), $plugins_allowedtags);
			else 
				$plugin_tag = "" ; 
			if (isset($plugin_uri[1]))
				$plugin_uri = wp_kses(trim($plugin_uri[1]), $plugins_allowedtags);
			else 
				$plugin_uri = "" ; 
			if (isset($description[1]))
				$description = wp_kses(trim($description[1]), $plugins_allowedtags);
			else 
				$description = "" ; 
			if (isset($author_name[1]))
				$author = wp_kses(trim($author_name[1]), $plugins_allowedtags);
			else 
				$author = "" ; 
			if (isset($author_uri[1]))
				$author_uri = wp_kses(trim($author_uri[1]), $plugins_allowedtags);
			else 
				$author_uri = "" ; 
			if (isset($author_email[1]))
				$author_email = wp_kses(trim($author_email[1]), $plugins_allowedtags);
			else 
				$author_email = "" ; 
			if (isset($framework_email[1]))
				$framework_email = wp_kses(trim($framework_email[1]), $plugins_allowedtags);
			else 
				$framework_email = "" ; 
			if (isset($version))
				$version = wp_kses($version, $plugins_allowedtags);
			else 
				$version = "" ; 
			if (isset($plugin_database[1]))
				$database = trim($plugin_database[1]) ; 
			else 
				$database = "" ; 
			
			return array('Dir_Plugin'=>basename(dirname($plugin_file)) , 'Plugin_Name' => $plugin_name,'Plugin_Tag' => $plugin_tag, 'Plugin_URI' => $plugin_uri, 'Description' => $description, 'Author' => $author, 'Author_URI' => $author_uri, 'Email' => $author_email, 'Framework_Email' => $framework_email, 'Version' => $version, 'Database' => $database);
		}
		
		/** ====================================================================================================================================================
		* Ensure that the needed folders are writable by the webserver. 
		* Will check usual folders and files.
		* You may add this in your configuration page <code>$this->check_folder_rights( array(array($theFolderToCheck, "rw")) ) ;</code>
		* If not a error msg is printed
		* 
		* @param array $folders list of array with a first element (the complete path of the folder to check) and a second element (the needed rights "r", "w" [or a combination of those])
		* @return void
		*/
		
		public function check_folder_rights ($folders) {
			$f = array(array(WP_CONTENT_DIR.'/sedlex/',"rw"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'readme.txt',"rw"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'css/',"r"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'js/',"r"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'lang/',"rw"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/',"r"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/img/',"r"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/templates/',"r"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/lang/',"rw"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/js/',"r"), 
					array(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'core/css/',"r")) ; 
			$folders = array_merge($folders, $f) ; 
			
			$result = "" ; 
			foreach ($folders as $f ) {
				if ( (is_dir($f[0])) || (@is_file($f[0])) ) {
					$readable = SLFramework_Utils::is_readable($f[0]) ; 
					$writable = SLFramework_Utils::is_writable($f[0]) ; 
					
					@chmod($f[0], 0755) ; 
					
					$pb = false ; 
					if ((strpos($f[1], "r")!==false) && (!$readable)) {
						$pb = true ; 
					}
					if ((strpos($f[1], "w")!==false) && (!$writable)) {
						$pb = true ; 
					}
					
					if ($pb) {
						if  (is_dir($f[0])) 
							$result .= "<p>".sprintf(__('The folder %s is not %s !','SL_framework'), "<code>".$f[0]."</code>", "<code>".$f[1]."</code>")."</p>" ; 
						if  (@is_file($f[0])) 
							$result .= "<p>".sprintf(__('The file %s is not %s !','SL_framework'), "<code>".$f[0]."</code>", "<code>".$f[1]."</code>")."</p>" ; 
					}
				} else {
					// We check if the last have an extension
					if (strpos(basename($f[0]) , ".")===false) {
						// It is a folder
						if (!@mkdir($f[0],0755,true)) {
							$result .= "<p>".sprintf(__('The folder %s does not exists and cannot be created !','SL_framework'), "<code>".$f[0]."</code>")."</p>" ; 
						}
					} else {
					
						$foldtemp = str_replace(basename($f[0]), "", str_replace(basename($f[0])."/","", $f[0])) ; 
						// We create the sub folders
						if ((!is_dir($foldtemp))&&(!@mkdir($foldtemp,0755,true))) {
							$result .= "<p>".sprintf(__('The folder %s does not exists and cannot be created !','SL_framework'), "<code>".$foldtemp."</code>")."</p>" ; 
						} else {
							// We touch the file
							@chmod($foldtemp, 0755) ; 
							if (@file_put_contents($f[0], '')===false) {
								$result .= "<p>".sprintf(__('The file %s does not exists and cannot be created !','SL_framework'), "<code>".$f[0]."</code>")."</p>" ; 
							}
						}
					}
				}
			}
			if ($result != "") {
				echo "<div class='error fade'><p>".__('There are some issues with folders rights. Please corret them as soon as possible as they could induce bugs and instabilities.','SL_framework')."</p><p>".__('Please see below:','SL_framework')."</p>".$result."</div>" ; 
			}
		}
		
		/** ====================================================================================================================================================
		* Get the displayed content
		* 
		* @return void
		*/
	
		function the_content_SL($content) {
			global $post ; 
			// If it is the loop and an the_except is called, we leave
			if (!is_single()) {
				// If page 
				if (is_page()) {
					if (method_exists($this,'_modify_content')) {
						return $this->_modify_content($content, 'page', false) ; 
					}
					return $content; 	
				// else
				} else {
					// si excerpt
					if ( (method_exists($this,'_modify_content')) && (!$this->excerpt_called_SL)) {
						return $this->_modify_content($content, get_post_type($post->ID), true) ; 
					}
					return $content ; 
				}
			} else {
	
				if ( (method_exists($this,'_modify_content')) && (!$this->excerpt_called_SL)) {
					return $this->_modify_content($content, get_post_type($post->ID), false) ; 
				}
				return $content ; 
			}
		}
		
		/** ====================================================================================================================================================
		* Get the excerpt content
		* 
		* @return void
		*/
		function the_excerpt_ante_SL($content) {
			$this->excerpt_called_SL=true ; 
			return $content ; 
		}
		
		function the_excerpt_SL($content) {
			global $post ; 
			$this->excerpt_called_SL = false ; 
			
			if ( (method_exists($this,'_modify_content')) && (!$this->excerpt_called_SL)) {
				return $this->_modify_content($content, get_post_type($post->ID), true) ; 
			}
			
			return $content ; 
		}
	}
	
	/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
	* This PHP class has only for purpose to fake a plugin class and allow parameters administration for the framework.
	* 
	*/
	class coreSLframework extends pluginSedLex {
	/** ====================================================================================================================================================
	* Plugin initialization
	* 
	* @return void
	*/
	static $instance = false;
	
		/**====================================================================================================================================================
		* Constructor
		*
		* @return void
		*/
		function coreSLframework() {
			$this->path = __FILE__ ; 
			$this->pluginID = get_class() ; 
		}
	
		
		/** ====================================================================================================================================================
		* Define the default option values of the framework
		* 
		* @param string $option the name of the option
		* @return variant of the option
		*/
		public function get_default_option($option) {
			switch ($option) {
				// Alternative default return values (Please modify)
				case 'debug_level' 			: return 3 		; break ; 
				case 'global_allow_translation_by_blogs' : return true ; break ; 
				case 'global_location_plugin'		: return array(		array("*".__("Standard", 'SL_framework'), "std"), 
								array(__("under Plugins", 'SL_framework'), "plugins"),											
								array(__("under Tools", 'SL_framework'), "tools"),
								array(__("under Settings", 'SL_framework'), "settings")
						   ) ; break ; 
			}
			return null ;
		}	
	}

}
						


?>