<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class create a page with the other plugins of the author referenced
*/

if (!class_exists("SLFramework_OtherPlugins")) {
	class SLFramework_OtherPlugins {
	   
		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @param string $nameAuthor the name of the author for which the plugins has to be listed
		* @param array $exclu a list of excluded plugin (slug name)
		* @return void 
		*/
		
		public function SLFramework_OtherPlugins($nameAuthor="", $exclu=array()) {
			$this->nameAuthor = $nameAuthor ; 
			$this->exclu = $exclu ; 
		}
		
		/** ====================================================================================================================================================
		* Display the list of plugins
		* 
		* @return void 
		*/
		
		public function list_plugins() {
			// On cherche 
			if (!is_file(dirname(__FILE__)."/data/SLFramework_OtherPlugins_".date('Ym').".data")) {
				// On efface les autres SLFramework_OtherPlugins s'ils existent
				$path = dirname(__FILE__)."/data/" ; 
				$files = @scandir($path) ;
				if ($files!==FALSE) {
					foreach ($files as $f) {
						if (preg_match("/^SLFramework_OtherPlugins/i", $f)) {
							@unlink($path.$f) ; 
						} 
					}
				}
				$this->get_list_plugins() ; 
			}
			$plugins = unserialize(@file_get_contents(dirname(__FILE__)."/data/SLFramework_OtherPlugins_".date('Ym').".data")) ;

			$plugins_active = get_plugins() ; 

			echo "<h3>".__("Plugins that you may install",'SL_framework')."</h3>" ; 
			
			echo "<p>".__("The following plugins have been developed by the author and are not yet been installed:",  "SL_framework") ."</p>" ; 
			$table = new SLFramework_Table() ; 
			$table->title(array(__("Plugin not yet installed", "SL_framework"), __("Description and Screenshots", "SL_framework")) ) ;
			$nb = 0 ; 
			foreach ($plugins as $slug => $plug) {	
				$found = false ; 
				// We check if the plugin is installed
				foreach ($plugins_active as $slug_active => $plug_activ) {
					if (count(explode("/", $slug_active,2))==2){
						list($slug_active, $tmp) = explode("/", $slug_active,2) ;
						if ($slug==$slug_active) {
							$found = true ; 
						}
					}
				}
				if (!$found) {
					if (is_ssl()) {
						$plug[1] = str_replace("http://","https://",$plug[1]) ; 
					}
					$cel1 = new adminCell($plug[0]) ;
					$cel2 = new adminCell($plug[1]) ;
					$table->add_line(array($cel1, $cel2), '1') ;
					$nb++ ; 
				}
			}
			if ($nb==0) {
					$cel1 = new adminCell("<p>".__("All author's plugins have been installed. Thank you!",  "SL_framework") ."</p>") ;
					$cel2 = new adminCell("") ;
					$table->add_line(array($cel1, $cel2), '1') ;
			}
			echo $table->flush() ; 
			
			echo "<h3>".__("Installed plugins",'SL_framework')."</h3>" ; 
			echo "<p>".__("You have already installed the following author's plugins:",  "SL_framework") ."</p>" ; 
			$table = new SLFramework_Table() ; 
			$table->title(array(__("Plugin already installed", "SL_framework"), __("Description and Screenshots", "SL_framework")) ) ;
			$nb = 0 ; 
			foreach ($plugins as $slug => $plug) {	
				$found = false ; 
				// We check if the plugin is installed
				foreach ($plugins_active as $slug_active => $plug_activ) {
					if (count(explode("/", $slug_active,2))==2){
						list($slug_active, $tmp) = explode("/", $slug_active,2) ;
						if ($slug==$slug_active) {
							$found = true ; 
						}
					}
				}
				if ($found) {
					$cel1 = new adminCell($plug[0]) ;
					$cel2 = new adminCell($plug[1] ) ;
					$table->add_line(array($cel1, $cel2), '1') ;
					$nb++ ; 
				}
			}
			echo $table->flush() ; 
		}

		/** ====================================================================================================================================================
		* Get the list of plugins and save it on the disk
		* 
		* @return void 
		*/
		
		public function get_list_plugins() {
			$action = "query_plugins" ; 
			$req = new stdClass() ; 
			$req->author = $this->nameAuthor; 
			$req->fields = array('sections') ; 
			
			$to_save = array() ; 
			
			$request = wp_remote_post('http://api.wordpress.org/plugins/info/1.0/', array( 'body' => array('action' => $action, 'request' => serialize($req))) );
			if ( !is_wp_error($request) ) {
				$res = unserialize($request['body']);
				if ( $res ) {
					$pV = array() ; 
					foreach ($res->plugins as $plug) {
						$pV = array_merge($pV, array($plug->name => $plug)) ;  
					}
					ksort($pV) ; 
					$res->plugins = $pV ; 
					
					foreach ($res->plugins as $plug) {
						$found_exclu = false ; 

						foreach($this->exclu as $e) {
							if ($e == $plug->slug) {
								$found_exclu = true ; 
							}
						}
						if (!$found_exclu) {
							ob_start() ; 
								echo "<p><b>".$plug->name."</b></p>" ; 
								echo "<p>".sprintf(__('The Wordpress page: %s', 'SL_framework'),"<a href='http://wordpress.org/extend/plugins/".$plug->slug."'>http://wordpress.org/extend/plugins/".$plug->slug."</a>")."</p>" ; 
								$cells = $this->pluginInfo($plug->slug) ; 
								echo $cells[0] ; 
							$to_save [$plug->slug] = array(ob_get_clean(), $cells[1]) ; 
						}
					}
					@file_put_contents(dirname(__FILE__)."/data/SLFramework_OtherPlugins_".date('Ym').".data", serialize($to_save)) ;
				}
			}
		}		
		
		/** ====================================================================================================================================================
		* Display the plugin Info
		* 
		* @param string $plugin the name of the plugin (slug name)
		* @return array the first cell is for the synthesis, the second is for the description and the screenshot 
		*/
		function pluginInfo($plugin) {

			// $action: query_plugins, plugin_information or hot_tags
			// $req is an object
			$action = "plugin_information" ; 
			$req = new stdClass() ; 
			$req->slug = $plugin;
			$request = wp_remote_post('http://api.wordpress.org/plugins/info/1.0/', array( 'body' => array('action' => $action, 'request' => serialize($req))) );
			if ( is_wp_error($request) ) {
				return  array("<p>".__('An Unexpected HTTP Error occurred during the API request.', 'SL_framework' )."</p>", "");
			} else {
				$res = unserialize($request['body']);
				ob_start() ; 
				$lastUpdate = date_i18n(get_option('date_format') , strtotime($res->last_updated)) ; 
				echo  "<p>".__('Last update:', 'SL_framework' )." ".$lastUpdate."</p>";
				echo  "<div class='inline'>".sprintf(__('Rating: %s', 'SL_framework' ), $res->rating)." &nbsp; &nbsp; </div> " ; 
				echo "<div class='star-holder inline'>" ; 
				echo "<div class='star star-rating' style='width: ".$res->rating."px'></div>" ; 
				echo "<div class='star star5'></div>" ; 
				echo "<div class='star star4'></div>" ; 
				echo "<div class='star star3'></div>" ; 
				echo "<div class='star star2'></div>" ; 
				echo "<div class='star star1'></div>" ; 
				echo "</div> " ; 
				echo " <div class='inline'> &nbsp; (".sprintf(__("by %s persons", 'SL_framework' ),$res->num_ratings).")</div>";
				echo "<br class='clearBoth' />" ; 
				echo  "<p>".__('Number of download:', 'SL_framework' )." ".$res->downloaded."</p>";
				$cell1 = ob_get_clean() ; 
				
				ob_start() ;
				echo "<div class='description_wordpress'>" ; 
				$content = explode("<h", $res->sections['description']) ; 
				echo $content[0] ; 
				echo "</div>" ; 
				if (isset($res->sections['screenshots'])){
					$screen = $res->sections['screenshots'] ; 
					$screen = str_replace("</ol>", "", $screen) ; 
					$screen = str_replace("<ol>", "", $screen) ; 
					$screen = str_replace("<li>", "<div class='screenshot_wordpress'>", $screen) ; 
					$screen = str_replace("</li>", "</div>", $screen) ; 
					$screen = preg_replace('#<img([^>]*)src=\'([^\']*?)\'([^>]*)>#isU', '<a href="$2" target="blank"><img$1src="$2"$3></a>', $screen) ; 
					echo "<div style='padding-left:10px ; '>".$screen."<div style='clear:both;'></div></div>" ; 
				}
				$cell2 = ob_get_clean() ; 
				return array($cell1, $cell2) ; 
			}
		}
	} 
}

?>