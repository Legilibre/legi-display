<?php
/**
Plugin Name: Legi Display
Plugin Tag: tag
Description: <p>Display French Codes and laws once the XML files are retrieved from ftp://legi@ftp2.journal-officiel.gouv.fr/</p><p>See http://rip.journal-officiel.gouv.fr/index.php/pages/LO for the licence</p>
Version: 1.0.1

Framework: SL_Framework
Author: SedLex
Author URI: http://www.sedlex.fr/
Author Email: sedlex@sedlex.fr
Framework Email: sedlex@sedlex.fr
Plugin URI: http://wordpress.org/plugins/legi-display/
License: GPL3
*/

//Including the framework in order to make the plugin work

require_once('core.php') ; 

/** ====================================================================================================================================================
* This class has to be extended from the pluginSedLex class which is defined in the framework
*/
class legi_display extends pluginSedLex {
	
	/** ====================================================================================================================================================
	* Plugin initialization
	* 
	* @return void
	*/
	static $instance = false;

	protected function _init() {
		global $wpdb ; 

		// Name of the plugin (Please modify)
		$this->pluginName = 'Legi Display' ; 
		
		// The structure of the SQL table if needed (for instance, 'id_post mediumint(9) NOT NULL, short_url TEXT DEFAULT '', UNIQUE KEY id_post (id_post)') 
		$this->tableSQL = "id MEDIUMINT(9) NOT NULL AUTO_INCREMENT, id_code VARCHAR(25), titre_code VARCHAR(255), info_code TEXT DEFAULT '', id_level1 VARCHAR(25), id_level2 VARCHAR(25), id_level3 VARCHAR(25), id_level4 VARCHAR(255), id_level5 VARCHAR(25), id_level6 VARCHAR(25), id_level7 VARCHAR(25), id_level8 VARCHAR(255), id_level9 VARCHAR(25), id_level10 VARCHAR(25), textes_id_level TEXT DEFAULT '', date_debut DATE, date_fin DATE, id_article VARCHAR(25), num_article VARCHAR(25), state VARCHAR(25), version TEXT DEFAULT '', lien_autres_articles TEXT DEFAULT '', texte TEXT DEFAULT '', PRIMARY KEY (id)" ; 

		// The name of the SQL table (Do no modify except if you know what you do)
		$this->table_name = $wpdb->prefix . "pluginSL_" . get_class() ; 

		//Configuration of callbacks, shortcode, ... (Please modify)
		// For instance, see 
		//	- add_shortcode (http://codex.wordpress.org/Function_Reference/add_shortcode)
		//	- add_action 
		//		- http://codex.wordpress.org/Function_Reference/add_action
		//		- http://codex.wordpress.org/Plugin_API/Action_Reference
		//	- add_filter 
		//		- http://codex.wordpress.org/Function_Reference/add_filter
		//		- http://codex.wordpress.org/Plugin_API/Filter_Reference
		// Be aware that the second argument should be of the form of array($this,"the_function")
		// For instance add_action( "wp_ajax_foo",  array($this,"bar")) : this function will call the method 'bar' when the ajax action 'foo' is called
		
		
		// Important variables initialisation (Do not modify)
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		// activation and deactivation functions (Do not modify)
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'deactivate'));
		register_uninstall_hook(__FILE__, array('legi_display','uninstall_removedata'));
		
		add_action( 'wp_ajax_nopriv_updateContenuLegi', array( $this, 'updateContenuLegi'));
		add_action( 'wp_ajax_updateContenuLegi', array( $this, 'updateContenuLegi'));
		
		add_action('wp_head', array( $this, 'add_meta_tags'));

		add_shortcode( 'legi', array( $this, 'legi_shortcode' ) );
		
		add_filter('template_include',array($this,'display_code_change_template'), 1);
		add_filter('the_posts', array($this, 'display_code_define_post'));
		
		
		$url = $this->parse_legi_uri() ; 
		if (is_array($url)) {
			remove_action('template_redirect', 'redirect_canonical');
		}

	}
	
	/** ====================================================================================================================================================
	* In order to uninstall the plugin, few things are to be done ... 
	* (do not modify this function)
	* 
	* @return void
	*/
	
	static public function uninstall_removedata () {
		global $wpdb ;
		// DELETE OPTIONS
		delete_option('legi_display'.'_options') ;
		if (is_multisite()) {
			delete_site_option('legi_display'.'_options') ;
		}
		
		// DELETE SQL
		if (function_exists('is_multisite') && is_multisite()){
			$old_blog = $wpdb->blogid;
			$old_prefix = $wpdb->prefix ; 
			// Get all blog ids
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM ".$wpdb->blogs));
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				$wpdb->query("DROP TABLE ".str_replace($old_prefix, $wpdb->prefix, $wpdb->prefix . "pluginSL_" . 'legi_display')) ; 
			}
			switch_to_blog($old_blog);
		} else {
			$wpdb->query("DROP TABLE ".$wpdb->prefix . "pluginSL_" . 'legi_display' ) ; 
		}
	}
	
	/**====================================================================================================================================================
	* Function called when the plugin is activated
	* For instance, you can do stuff regarding the update of the format of the database if needed
	* If you do not need this function, you may delete it.
	*
	* @return void
	*/
	
	public function _update() {
		SLFramework_Debug::log(get_class(), "Update the plugin." , 4) ; 
	}
	
	/**====================================================================================================================================================
	* Function called to return a number of notification of this plugin
	* This number will be displayed in the admin menu
	*
	* @return int the number of notifications available
	*/
	 
	public function _notify() {
		return 0 ; 
	}
	
	
	/** ====================================================================================================================================================
	* Init javascript for the public side
	* If you want to load a script, please type :
	* 	<code>wp_enqueue_script( 'jsapi', 'https://www.google.com/jsapi');</code> or 
	*	<code>wp_enqueue_script('legi_display_script', plugins_url('/script.js', __FILE__));</code>
	*	<code>$this->add_inline_js($js_text);</code>
	*	<code>$this->add_js($js_url_file);</code>
	*
	* @return void
	*/
	
	function _public_js_load() {	
		global $post ; 
		global $wpdb ; 
		if (!isset($post->post_content)){
			return ; 
		}
		if (preg_match_all("/#LEGI#([\w]*)-([\w]*)#LEGI#/ui", $post->post_content, $matches, PREG_SET_ORDER)) {
			wp_enqueue_script('jquery-ui-slider', '', array('jquery'), false );
			ob_start() ; 

				foreach ($matches as $m) {
					$result = $wpdb->get_results("SELECT * FROM ".$this->table_name." WHERE id_article='".$m[2]."'") ;
					foreach($result as $r) { 
						// GESTION DU SLIDER POUR AFFICHER PLUSIEURS VERSIONS
						if ($r->version!="") {
							$version = @unserialize($r->version) ; 
							if ($version!==false) {
								$ver = array() ; 
								foreach($version as $v) {
									$ver[$v['date_debut']] = array('state'=>$v['state'], 'date_fin'=>$v['date_fin'], 'num_article'=>$v['num_article'], 'id_article'=>$v['id_article']) ; 
								}
								ksort($ver) ; 
								if (count($ver)>1) {
									?>
									var legi_identifiant1 = "<?php echo $m[2];?>" ; 
									var legi_identifiant2 = "<?php echo $m[2];?>" ; 
									
									function updateContenuLegi(identifiant){
										if ((identifiant==legi_identifiant1)&&(identifiant==legi_identifiant2)){
											return ;
										}
										legi_identifiant1 = identifiant ; 
										legi_identifiant2 = identifiant ; 
										
										jQuery('#LegiContent').html("<p>En attente du texte de l'article ... <img src='<?php echo plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif" ?>'/></p>")
										var arguments = {
											id1: identifiant,
											action: 'updateContenuLegi'
										} 
										var ajaxurl2 = "<?php echo admin_url()."admin-ajax.php"?>" ; 
										jQuery.post(ajaxurl2, arguments, function(response) {
											jQuery('#LegiContent').html(response) ; 
										});    
									}
									
									function compareContenuLegi(identifiant1, identifiant2){
										
										if ((identifiant1==legi_identifiant1)&&(identifiant2==legi_identifiant2)){
											return ;
										}
										
										legi_identifiant1 = identifiant1 ; 
										legi_identifiant2 = identifiant2 ; 
										
										jQuery('#LegiContent').html("<p>En attente de la comparaison des articles ... <img src='<?php echo plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif" ?>'/></p>")

										var arguments = {
											id1: identifiant1,
											id2: identifiant2,
											action: 'updateContenuLegi'
										} 
										var ajaxurl2 = "<?php echo admin_url()."admin-ajax.php"?>" ; 
										jQuery.post(ajaxurl2, arguments, function(response) {
											jQuery('#LegiContent').html(response) ; 
										});    
									}
									
									<?php	
									$incr = floor(100/(count($ver))) ; 
									$pos = 0 ; 
									$ii = 0 ; 
									$list_bornes = array() ; 
									foreach($ver as $k => $info) {
										$ii++ ; 
										if ($ii==1){
											$list_bornes[$info['id_article']] = array($pos, $pos+$incr);
											$pos += $incr ;
										} elseif ($ii==count($ver)){
											$incr = 100-$pos ; 
											$list_bornes[$info['id_article']] = array($pos, 100);
											$mid_val = floor(($pos+100)/2) ; 
										} else {
											$list_bornes[$info['id_article']] = array($pos, $pos+$incr);
											$pos += $incr ;
										} 
									}
									echo '
										jQuery(function() {
										jQuery( "#legi_activerComparaison" ).removeAttr("checked");
										jQuery( "#legi_slider" ).slider({
												value: '.$mid_val.',
												slide: function( event, ui ) {
													';
									foreach ($list_bornes as $lk=>$lb) {
										echo 'if ((ui.value<'.$lb[1].')&&(ui.value>='.$lb[0].')){'."\r\n" ;
										echo '    jQuery("#date_'.$lk.'").addClass("date_slider_highlight");'."\r\n" ; 
										foreach ($list_bornes as $t_lk=>$t_lb) {
											if ($lk!=$t_lk){
												echo '    jQuery("#date_'.$t_lk.'").removeClass("date_slider_highlight");'."\r\n" ; 
											}
										}
										echo '}'."\r\n" ;
									}		
									echo '
										        }, 
												stop: function( event, ui ) {
													';
									foreach ($list_bornes as $lk=>$lb) {
										echo 'if ((ui.value<'.$lb[1].')&&(ui.value>='.$lb[0].')){'."\r\n" ;
										echo '    jQuery( "#legi_slider" ).slider({value:'.floor(($lb[1]+$lb[0])/2).'});'."\r\n" ; 
										echo '    updateContenuLegi("'.$lk.'");'."\r\n" ; 
										echo '}'."\r\n" ;
									}		
									echo '													
												}
											});
											
										});';					
								
									?>
									function activerComparaison() {
										if (jQuery( "#legi_activerComparaison" ).is(':checked')) {
											currentValue = jQuery( "#legi_slider" ).slider("value") ; 
											jQuery( "#legi_slider" ).slider("destroy") ; 
											<?php
										
										foreach ($list_bornes as $lk=>$lb) {
											echo 'if ((currentValue<'.$lb[1].')&&(currentValue>='.$lb[0].')){'."\r\n" ;
											foreach ($list_bornes as $lk2=>$lb2) {
												if (($mid_val-2<$lb2[1])&&($mid_val-2>=$lb2[0])){
													echo '    compareContenuLegi("'.$lk.'", "'.$lk2.'");'."\r\n" ; 
												}
											}
											echo '}'."\r\n" ;
										}	
										echo '
											
											jQuery( "#legi_slider" ).slider({
													values: [currentValue+2,'.($mid_val-2).'],
													slide: function( event, ui ) {
														';
										foreach ($list_bornes as $lk=>$lb) {
											echo 'if ((ui.values[0]<'.$lb[1].')&&(ui.values[0]>='.$lb[0].')){'."\r\n" ;
											echo '    jQuery("#date_'.$lk.'").addClass("date_slider_highlight");'."\r\n" ; 
											foreach ($list_bornes as $t_lk=>$t_lb) {
												if ($lk!=$t_lk){
													echo '    jQuery("#date_'.$t_lk.'").removeClass("date_slider_highlight");'."\r\n" ; 
												}
											}
											echo '}'."\r\n" ;
										}		
										echo '
											        }, 
													stop: function( event, ui ) {
														';
										foreach ($list_bornes as $lk=>$lb) {
											echo 'if ((ui.values[0]<'.$lb[1].')&&(ui.values[0]>='.$lb[0].')){'."\r\n" ;
											echo '    otherValue = jQuery( "#legi_slider" ).slider("values", 1) ; '."\r\n" ;
											echo '    jQuery( "#legi_slider" ).slider({values:['.(floor(($lb[1]+$lb[0])/2)+2).', otherValue]});'."\r\n" ; 
											foreach ($list_bornes as $lk2=>$lb2) {
												echo 'if ((ui.values[1]<'.$lb2[1].')&&(ui.values[1]>='.$lb2[0].')){'."\r\n" ;
												echo '    otherValue = jQuery( "#legi_slider" ).slider("values", 0) ; '."\r\n" ;
												echo '    jQuery( "#legi_slider" ).slider({values:[otherValue,'.(floor(($lb2[1]+$lb2[0])/2)-2).']});'."\r\n" ; 
												echo '    compareContenuLegi("'.$lk.'", "'.$lk2.'");'."\r\n" ; 
												echo '}'."\r\n" ;
											}
											echo '}'."\r\n" ;
										}		
										echo '													
													}
												
											});';
											?>
											
										} else {
											currentValue = jQuery( "#legi_slider" ).slider("values", 0) ; 
											jQuery( "#legi_slider" ).slider("destroy") ; 
											
											<?php
											foreach ($list_bornes as $lk=>$lb) {
											echo 'if ((currentValue<'.$lb[1].')&&(currentValue>='.$lb[0].')){'."\r\n" ;
											echo '    updateContenuLegi("'.$lk.'");'."\r\n" ; 
											echo '}'."\r\n" ;
										}
											echo '
												jQuery( "#legi_slider" ).slider({
														value: currentValue-2,
														slide: function( event, ui ) {
															';
											foreach ($list_bornes as $lk=>$lb) {
												echo 'if ((ui.value<'.$lb[1].')&&(ui.value>='.$lb[0].')){'."\r\n" ;
												echo '    jQuery("#date_'.$lk.'").addClass("date_slider_highlight");'."\r\n" ; 
												foreach ($list_bornes as $t_lk=>$t_lb) {
													if ($lk!=$t_lk){
														echo '    jQuery("#date_'.$t_lk.'").removeClass("date_slider_highlight");'."\r\n" ; 
													}
												}
												echo '}'."\r\n" ;
											}		
											echo '
												        }, 
														stop: function( event, ui ) {
															';
											foreach ($list_bornes as $lk=>$lb) {
												echo 'if ((ui.value<'.$lb[1].')&&(ui.value>='.$lb[0].')){'."\r\n" ;
												echo '    jQuery( "#legi_slider" ).slider({value:'.floor(($lb[1]+$lb[0])/2).'});'."\r\n" ; 
												echo '    updateContenuLegi("'.$lk.'");'."\r\n" ; 
												echo '}'."\r\n" ;
											}		
											echo '													
														}
												});';
											?>					
										}
									}
									<?php
								}
							}
						}
						// FIN SLIDER
					}
				}
								
			
			$java = ob_get_clean() ; 
			$this->add_inline_js($java) ; 
		}
		return ; 
	}
	
	/** ====================================================================================================================================================
	* Init css for the public side
	* If you want to load a style sheet, please type :
	*	<code>$this->add_inline_css($css_text);</code>
	*	<code>$this->add_css($css_url_file);</code>
	*
	* @return void
	*/
	
	function _public_css_load() {	
		$this->add_inline_css($this->get_param('css')) ; 
		return ; 
	}
	
	/** ====================================================================================================================================================
	* Init javascript for the admin side
	* If you want to load a script, please type :
	* 	<code>wp_enqueue_script( 'jsapi', 'https://www.google.com/jsapi');</code> or 
	*	<code>wp_enqueue_script('legi_display_script', plugins_url('/script.js', __FILE__));</code>
	*	<code>$this->add_inline_js($js_text);</code>
	*	<code>$this->add_js($js_url_file);</code>
	*
	* @return void
	*/
	
	function _admin_js_load() {	
		return ; 
	}
	
	/** ====================================================================================================================================================
	* Init css for the admin side
	* If you want to load a style sheet, please type :
	*	<code>$this->add_inline_css($css_text);</code>
	*	<code>$this->add_css($css_url_file);</code>
	*
	* @return void
	*/
	
	function _admin_css_load() {	
		return ; 
	}

	/** ====================================================================================================================================================
	* Called when the content is displayed
	*
	* @param string $content the content which will be displayed
	* @param string $type the type of the article (e.g. post, page, custom_type1, etc.)
	* @param boolean $excerpt if the display is performed during the loop
	* @return string the new content
	*/
	
	function _modify_content($content, $type, $excerpt) {
		global $wpdb ; 
		global $_POST ; 
		
		$crawler = "" ; 
		if (!$this->get_param('allow_crawler')){
			$crawler = " rel='nofollow'" ; 
		}
		
		// RECHERCHE
		if (preg_match_all("/#LEGI#([\w]*)_RECHERCHE#LEGI#/ui", $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $m) {
				ob_start() ; 
					// GESTION DU MODULE de RECHERCHE
					$titre_code = $wpdb->get_var("SELECT titre_code FROM ".$this->table_name." WHERE id_code='".$m[1]."' LIMIT 1") ;					
					echo "<div id='legi_breadcrumb'><a".$crawler." href='".$this->id_code_to_url($m[1])."'>".$titre_code."</a></div>" ; 
					echo "<div id='legi_recherche'><form action='".$this->id_code_to_url($m[1])."recherche/' method='post'><p>Rechercher dans le ".$titre_code." <input name='legi_rech_string'></form></p></div>" ;

					// AFFICHAGE DES RESULTATS
					if (isset($_POST['legi_rech_string'])) {
						echo "<h4>Résultat de la recherche</h4>" ; 
						$search_str = strip_tags(html_entity_decode($_POST['legi_rech_string'])) ; 
						$search_str = trim(preg_replace("@[^\w -]@ui", " ", $search_str)) ; 
						$search_results = $this->search_words($m[1], explode(" ", $search_str)) ; 
						if (count($search_results)==0) {
							echo "<p>Aucun résultat</p>" ; 
						} else {
							if (count($search_results)!=20) {
								echo "<p>Les articles retournant un résultat pour '<em>$search_str</em>' sont (les articles sont affichés par ordre de pertinence):<p>" ; 
							} else {
								echo "<p>Les 20 premiers articles retournant un résultat pour '<em>$search_str</em>' sont (les articles sont affichés par ordre de pertinence):<p>" ; 
							}
							echo "<ul>" ; 
							foreach($search_results as $id=>$sr) {
								$num_art = $wpdb->get_var("SELECT num_article FROM ".$this->table_name." WHERE id_article='".$id."'") ;
						    	echo "<li><a".$crawler." href='".$this->id_article_to_url($id)."'>".$num_art."</a></li>" ; 
							}
							echo "</ul>" ; 
						}
					} else {
						echo "Aucune recherche effectuée" ; 
					}
				$content = str_replace($m[0], ob_get_clean(), $content) ; 
			}
		}
		
		// CODE
		if (preg_match_all("/#LEGI#([\w]*)#LEGI#/ui", $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $m) {
				ob_start() ; 
					// GESTION DU MODULE de RECHERCHE
					$titre_code = $wpdb->get_var("SELECT titre_code FROM ".$this->table_name." WHERE id_code='".$m[1]."' LIMIT 1") ;					
					echo "<div id='legi_recherche'><form action='".$this->id_code_to_url($m[1])."recherche/' method='post'><p>Rechercher dans le ".$titre_code." <input name='legi_rech_string'></form></p></div>" ;
					
					// AFFICHAGE TREE
					$array_to_display = $this->get_code_section($m[1]) ; 
					SLFramework_Treelist::render($array_to_display, true, null, "code_hiera") ; 
				$content = str_replace($m[0], ob_get_clean(), $content) ; 
			}
			$info = @unserialize($wpdb->get_var("SELECT info_code FROM ".$this->table_name." WHERE id_code='".$m[1]."' LIMIT 1")) ; 
		}
		
		// ARTICLE
		if (preg_match_all("/#LEGI#([\w]*)-([\w]*)#LEGI#/ui", $content, $matches, PREG_SET_ORDER)) {

			foreach ($matches as $m) {
				ob_start() ; 
					$result = $wpdb->get_results("SELECT * FROM ".$this->table_name." WHERE id_article='".$m[2]."'") ;
					foreach($result as $r) { 
					
						// GESTION DU MODULE de RECHERCHE
						echo "<div id='legi_breadcrumb'><a".$crawler." href='".$this->id_code_to_url($r->id_code)."'>".$r->titre_code."</a></div>" ; 
						echo "<div id='legi_recherche'><form action='".$this->id_code_to_url($r->id_code)."recherche/' method='post'><p>Rechercher dans le ".$r->titre_code." <input name='legi_rech_string'></form></p></div>" ;
						
						// GESTION DU SLIDER POUR AFFICHER PLUSIEURS VERSIONS
						if ($r->version!="") {
							$version = @unserialize($r->version) ; 
							if ($version!==false) {
								$ver = array() ; 
								foreach($version as $v) {
									$ver[$v['date_debut']] = array('state'=>$v['state'], 'date_fin'=>$v['date_fin'], 'num_article'=>$v['num_article'], 'id_article'=>$v['id_article']) ; 
								}
								ksort($ver) ; 
								if (count($ver)>1) {
									echo '<div id="legi_slider_date" style="position:relative;padding:0px;margin:0px;margin-left:5%;width:90%;height:30px;background-color:#EEEEEE;">' ; 
									$incr = floor(100/(count($ver))) ; 
									$pos = 0 ; 
									$ii = 0 ; 
									foreach($ver as $k => $info) {
										$ii++ ; 
										if ($ii==1){
											echo "<div style='position:absolute;left:0%;top:0px;width:".$incr."%;border-right:1px solid black;height:30px;height:30px;' class='date_slider' id='date_".$info['id_article']."'>".$k."</div>" ; 
											$pos += $incr ;
										} elseif ($ii==count($ver)){
											$incr = 100-$pos ; 
											echo "<div style='position:absolute;left:".$pos."%;top:0px;width:".$incr."%;height:30px;border-left:1px solid black;' class='date_slider date_slider_highlight' id='date_".$info['id_article']."'>".$k."</div>" ; 
											$mid_val = floor(($pos+100)/2) ; 
										} else {
											echo "<div style='position:absolute;left:".$pos."%;top:0px;width:".$incr."%;border-right:1px solid black;height:30px;border-left:1px solid black;height:30px;' class='date_slider' id='date_".$info['id_article']."'>".$k."</div>" ; 
											$pos += $incr ;
										} 
									}
									echo '</div>' ; 
									echo '<div id="legi_slider" style="padding:0px;margin:0px;margin-left:5%;width:90%;height:10px;"></div>' ; 							
									echo '<div id="legi_compare" style="padding:0px;margin:0px;margin-left:5%;width:90%;height:10px;text-align:right;color:#AAAAAA;">Activer la comparaison <input id="legi_activerComparaison" onclick="activerComparaison()" type="checkbox"></div>' ; 							
								}
							}
						}
						// FIN SLIDER
						
						echo "<p>&nbsp;</p><div id='LegiContent'>".$this->mise_en_forme_article($r)."</div>";
			
						
					}
					
					$info = @unserialize($r->info_code) ; 
				
					$array_to_display = $this->get_code_section($m[1],$m[2]) ; 
					SLFramework_Treelist::render($array_to_display, true, null, "code_hiera") ; 
				$content = str_replace($m[0], ob_get_clean(), $content) ; 
			}
		}
		// NOTICE
		
		$content .= "<p>&nbsp;</p>" ; 
		$content .= "<div class='notice_legi'>" ; 
		$content .= "<p>Ces informations sont issues de la base de données \"<a href='http://www.data.gouv.fr/fr/dataset/legi-codes-lois-et-reglements-consolides'>LEGI</a>\" mise à disposition par LégiFrance, la direction de l'information légale et administrative et les services du Premier ministre.</p>" ; 
		$content .= "<p>Les données sont reproduites sans modification, si ce n'est celles nécessaires à la mise en forme de la page.</p>" ; 
		if ((isset($info))&&(is_array($info))) {
			$content .= "<p>La dernière mise à jour de ce code a été réalisé le ".$info['derniere_maj'].".</p>" ; 
		}
		$content .= "</div>" ;
		
		return $content; 
	}
		
	/** ====================================================================================================================================================
	* Search text into a code
	* 
	* @return array of results
	*/
	
	function search_words($code, $array_words) {
		global $wpdb ; 
		$initial_results = array() ; 
		
		// On regarde d'abord tous les articles qui contiennet au moins un mot de plus de trois lettres
		foreach($array_words as $aw) {
			if (strlen($aw)>2) {
				$result = $wpdb->get_results("SELECT id_article, texte FROM ".$this->table_name." WHERE id_code='".$code."' AND state='VIGUEUR' AND (texte LIKE '%".$aw."%' OR texte LIKE '%".htmlentities($aw)."%')") ;
				foreach($result as $r) { 
					if (!isset($initial_results[$r->id_article])) {
						$texte = strip_tags(html_entity_decode($r->texte)) ; 
						$texte = trim(preg_replace("@[^\w -]@ui", " ", $texte)) ; 
						$initial_results[$r->id_article] = $texte ; 
					}
				}
			}
		}
				
		// Maintenant, pour chaque texte trouvé, on calcul un score de proximite
		$score_results = array() ; 
		$max_score_cible = 0 ; 
		foreach($initial_results as $k_ir => $ir) { 
			$score = 0 ; 
			$score_cible = 0 ; 
			foreach($array_words as $aw) {
				if (strlen($aw)>0) {
					$score_cible += strlen($aw)*log(2) ; 
					$score += strlen($aw)*log(1+mb_substr_count($ir, $aw)) ; 
				}
			}
			if ($max_score_cible<$score_cible) {
				$max_score_cible = $score_cible ; 
			}
			$score_results[$k_ir] = $score ;
		} 
		
		// On ne garde que les 20 meilleurs résultats
		arsort($score_results) ; 
		$score_results = array_slice($score_results, 0, 20, true) ; 
		
		// On normalise les resultats
		$normalized_results = array() ; 
		foreach ($score_results as $k => $s) {
			$normalized_results[$k] = floor($s/$max_score_cible*100) ; 
		}
		
		return $normalized_results ; 
	}
	
	/** ====================================================================================================================================================
	* Add a button in the TinyMCE Editor
	*
	* To add a new button, copy the commented lines a plurality of times (and uncomment them)
	* 
	* @return array of buttons
	*/
	
	function add_tinymce_buttons() {
		$buttons = array() ; 
		$buttons[] = array(__('List the available LEGI code that you have imported', $this->pluginID), '[legi]', '', plugin_dir_url("/").'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)).'img/legi.png') ; 
		return $buttons ; 
	}
	
	/**====================================================================================================================================================
	* Function to instantiate the class and make it a singleton
	* This function is not supposed to be modified or called (the only call is declared at the end of this file)
	*
	* @return void
	*/
	
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/** ====================================================================================================================================================
	* Define the default option values of the plugin
	* This function is called when the $this->get_param function do not find any value fo the given option
	* Please note that the default return value will define the type of input form: if the default return value is a: 
	* 	- string, the input form will be an input text
	*	- integer, the input form will be an input text accepting only integer
	*	- string beggining with a '*', the input form will be a textarea
	* 	- boolean, the input form will be a checkbox 
	* 
	* @param string $option the name of the option
	* @return variant of the option
	*/
	public function get_default_option($option) {
		switch ($option) {
			// Alternative default return values (Please modify)
			case 'css' 		: return "*" 		; break ; 
			case 'folder_code' 	: return "code_fr" ; break ; 
			case 'allow_crawler' 	: return false ; break ; 
		}
		return null ;
	}

	/** ====================================================================================================================================================
	* The admin configuration page
	* This function will be called when you select the plugin in the admin backend 
	*
	* @return void
	*/
	
	public function configuration_page() {
		global $wpdb;
		global $blog_id ; 

		$blog_fold = "" ; 
		if (is_multisite()) {
			$blog_fold = $blog_id."/" ; 
		}		
		
		SLFramework_Debug::log(get_class(), "Print the configuration page." , 4) ; 

		?>
		<div class="plugin-titleSL">
			<h2><?php echo $this->pluginName ?></h2>
		</div>
		<div class="plugin-contentSL">			
			<?php
			//===============================================================================================
			// After this comment, you may modify whatever you want
			
			// We check rights
			$this->check_folder_rights( array(array(WP_CONTENT_DIR."/sedlex/test/", "rwx")) ) ;
			
			$tabs = new SLFramework_Tabs() ; 
			
			if (isset($_POST['import_code'])) {
				ob_start() ;
					$code = $_POST['import_code'] ;
					if (preg_match("/[A-Z0-9]{20}/i", $code)) {
						$result = $this->import_code($code) ; 
						echo $result['msg'] ; 
					} else {
						echo __('The code does not seems to be an appropriate code.', $this->pluginID) ; 
					}
		
    				if (is_file(WP_CONTENT_DIR."/sedlex/legi-display/".$blog_fold.$code.".array")) {
    					@unlink(WP_CONTENT_DIR."/sedlex/legi-display/".$blog_fold.$code.".array") ; 
    					echo "<p>".sprintf(__("The cache file %s has been deleted: it will be recreated in the next rendering of this code.", $this->pluginID), "<code>".WP_CONTENT_DIR."/sedlex/legi-display/".$blog_fold.$code.".array"."</code>")."</p>" ; 
    				}
    				
					$import_in_progress = new SLFramework_Box (__("Code that has just been imported", $this->pluginID), ob_get_clean()) ; 
			}
						
			ob_start() ;
				echo "<p>".__('Choose a code to import/update in the following list:', $this->pluginID)."</p>" ; 
				$list_code = $this->list_code() ; 
				if (($list_code !== false)&&(count($list_code)>0)) {
					echo "<p><form method='POST'><select name='import_code'>" ; 
				    echo "<option value='none'> </option>" ; 
					foreach ($list_code as $lc_k => $lc) {
						echo "<option value='".$lc_k."'>".$lc."</option>" ; 
					} 
					echo "</select> <input type='submit' name='submit' class='button-primary validButton' value='".__('Import', $this->pluginID)."'/></form></p>" ; 
				} else {
					echo "<p><code>".__('No code detected in your upload folder.', $this->pluginID)."</code></p>" ; 
				}
				
			$import = new SLFramework_Box (__("Import", $this->pluginID), ob_get_clean()) ;
			ob_start() ;
				echo "<p>".__('Here is the list of code that has already been imported:', $this->pluginID)."</p>" ; 
				if (($list_code !== false)&&(count($list_code)>0)) {
					$found = false ; 
					foreach ($list_code as $lc_k => $lc) {
						$nb = $wpdb->get_var("SELECT COUNT(*) FROM ".$this->table_name." WHERE id_code='".esc_sql($lc_k)."'") ; 
						if ($nb>0) {
							echo "<p style='padding-left:30px'><code>".$lc."</code> ($nb)</p>" ; 
							$found = true ; 
						}
					} 
					if (!$found) {
						echo "<p><code>".__('No code has been imported.', $this->pluginID)."</code></p>" ; 
					}	
				} else {
					echo "<p><code>".__('No code has been imported.', $this->pluginID)."</code></p>" ; 
				}
				
			$imported = new SLFramework_Box (__("Already imported", $this->pluginID), ob_get_clean()) ; 
						 
			ob_start() ; 
				if (isset($_POST['import_code'])) {
					echo $import_in_progress->flush() ; 
				}
				echo $imported->flush() ; 
				echo $import->flush() ; 
								
			$tabs->add_tab(__('Import a code',  $this->pluginID), ob_get_clean()) ; 
				
			// HOW To
			ob_start() ;
				echo "<p>".sprintf(__('This plugin Display French Codes and laws once the XML files are retrieved from %s. See %s for the licence.', $this->pluginID), "<a href='ftp://legi@ftp2.journal-officiel.gouv.fr/'>ftp://legi@ftp2.journal-officiel.gouv.fr/</a>", "<a href='http://rip.journal-officiel.gouv.fr/index.php/pages/LO'>http://rip.journal-officiel.gouv.fr/index.php/pages/LO</a>") ."</p>" ;
			$howto1 = new SLFramework_Box (__("Purpose of that plugin", $this->pluginID), ob_get_clean()) ; 
			ob_start() ;
				$upload_dir = wp_upload_dir();
				echo "<p>".sprintf(__('At first, download the file %s on %s.', $this->pluginID), "<code>LicenceFreemium_legi_legi_global_20xxxxxx-xxxxxx.tar.gz</code>", "<a href='ftp://legi@ftp2.journal-officiel.gouv.fr/'>ftp://legi@ftp2.journal-officiel.gouv.fr/</a>" )."</p>" ;
				echo "<p>".sprintf(__('Unpack this file on your website (for instance, with %s if your are on Windows or any other compatible software).', $this->pluginID), "<code>7-Zip</code>")."</p>" ;
				echo "<p>".sprintf(__('The unpacked files should be stored without modification on you website, in the %s folder (and therefore, the path should be something like this %s).', $this->pluginID), "<code>".$upload_dir['basedir']."</code>", "<code>".$upload_dir['basedir']."/legi/global/code_et_TNC_en_vigueur/code_en_vigueur/...</code>")."</p>" ;
				echo "<p>".__('Once this done, we should be able to import code in the Import tab.', $this->pluginID)."</p>" ;
			$howto2 = new SLFramework_Box (__("How to import the codes?", $this->pluginID), ob_get_clean()) ; 

			ob_start() ;
				echo "<p>".sprintf(__('After the importation, you may insert in a page the shortcode %s in order to create a list of all imported codes. There is also a button in the page editor to insert such shortcode.', $this->pluginID), '<code>[legi]</code>')."</p>" ; 
			$howto3 = new SLFramework_Box (__("How to consult a code?", $this->pluginID), ob_get_clean()) ; 
			ob_start() ;
				 echo $howto1->flush() ; 
				 echo $howto2->flush() ; 
				 echo $howto3->flush() ; 
			$tabs->add_tab(__('How To',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_how.png") ; 				

			ob_start() ; 
				$params = new SLFramework_Parameters($this, "tab-parameters") ; 
				$params->add_title(__('Sub-Dir',  $this->pluginID)) ; 
				$params->add_param('folder_code', __('The name of the folder that will be used for displaying the French Code:',  $this->pluginID)) ; 
				$params->add_comment(sprintf(__("If the folder is %s, the url to access the code would be something like %s.",  $this->pluginID), "<code>code_fr</code>", "<code>".site_url()."/code_fr/xxxxx/xxxxx</code>")) ; 
				$params->add_comment(sprintf(__("For consistency, you may create a page accessible through this url and add a shortcode %s to display the list of all code you imported.",  $this->pluginID), "<code>[legi]</code>")) ; 
				
				$params->add_title(__('Web-Crawlers',  $this->pluginID)) ; 
				$params->add_param('allow_crawler', sprintf(__('Allow the web crawlers (%s) to index the codes/articles:',  $this->pluginID), $this->pluginID)) ; 
				$params->add_comment(__("It is recommended to de-activate this option as most of the crawlers penalize websites with a lot of new/similar contents.",  $this->pluginID)) ; 
				
				$params->add_title(__('Appearance',  $this->pluginID)) ; 
				$params->add_param('css', __('The CSS to be used:',  $this->pluginID)) ; 
				$params->add_comment(__("The default value is:",  $this->pluginID)) ; 
				$params->add_comment_default_value('css') ; 
				
				$params->flush() ; 
				
			$tabs->add_tab(__('Parameters',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_param.png") ; 	
			
			$frmk = new coreSLframework() ;  
			if (((is_multisite())&&($blog_id == 1))||(!is_multisite())||($frmk->get_param('global_allow_translation_by_blogs'))) {
				ob_start() ; 
					$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
					$trans = new SLFramework_Translation($this->pluginID, $plugin) ; 
					$trans->enable_translation() ; 
				$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_trad.png") ; 	
			}

			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new SLFramework_Feedback($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_mail.png") ; 	
			
			ob_start() ; 
				// A list of plugin slug to be excluded
				$exlude = array('wp-pirate-search') ; 
				// Replace sedLex by your own author name
				$trans = new SLFramework_OtherPlugins("sedLex", $exlude) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other plugins',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_plug.png") ; 	
			
			echo $tabs->flush() ; 
			
			// Before this comment, you may modify whatever you want
			//===============================================================================================
			?>
			<?php echo $this->signature ; ?>
		</div>
		<?php
	}
	
	/** ====================================================================================================================================================
	* To list the available code
	*
	* @return void
	*/
	
 	function list_code() {
		$upload_dir = wp_upload_dir();
 		if (!is_dir($upload_dir['basedir']."/legi/global/code_et_TNC_en_vigueur/code_en_vigueur")) {
 			return false ; 
 		} 	
 		$result = $this->list_code_rec($upload_dir['basedir']."/legi/global/code_et_TNC_en_vigueur/code_en_vigueur") ; 
 		asort($result) ; 
 		return $result ; 
 	}
 	
	

	
	/** ====================================================================================================================================================
	* To list the available code recursive
	*
	* @return void
	*/
	
 	function list_code_rec($path) {
 		if (!is_dir($path)) {
 			return array() ; 
 		}
 		
  		if (is_dir($path.DIRECTORY_SEPARATOR."texte".DIRECTORY_SEPARATOR."version")) {
 			$list_file = scandir($path.DIRECTORY_SEPARATOR."texte".DIRECTORY_SEPARATOR."version") ; 
 			foreach ($list_file as $ld) {
				if (($ld!=".")&&($ld!="..")) {
					if (is_file($path.DIRECTORY_SEPARATOR."texte".DIRECTORY_SEPARATOR."version".DIRECTORY_SEPARATOR.$ld)) {
						if (preg_match("/[A-Z0-9]{20}\.xml/i", $ld)) {
							$vxml = simplexml_load_file ($path.DIRECTORY_SEPARATOR."texte".DIRECTORY_SEPARATOR."version".DIRECTORY_SEPARATOR.$ld) ; 
							$titre = (string)$vxml->META->META_SPEC->META_TEXTE_VERSION->TITREFULL ; 
							$id_code = (string)$vxml->META->META_COMMUN->ID ; 
							return array($id_code=>$titre) ; 
						}
					}
				}
			}
 		} 
 		
 		if (is_dir($path.DIRECTORY_SEPARATOR."article")) {
 			//ERROR car on aurait du sortir avant ...
 			// Cela signifie qu'il n'y avait pas de xml decrivant le code
 			return array() ;
 		}

 		
 		$list_code = array() ;  
 		
 		$list_dir = scandir($path) ; 
 		foreach ($list_dir as $ld) {
 			if (($ld!=".")&&($ld!="..")) {
				$new_path = $path . DIRECTORY_SEPARATOR . $ld ; 
 				if (is_dir($new_path)) {
					$list_code_new = $this->list_code_rec($new_path) ; 
					$list_code = array_merge($list_code, $list_code_new) ; 
 				}  			
 			}
 		}

		return $list_code ; 
 
  	}
	/** ====================================================================================================================================================
	* To import a code from the XML files
	*
	* @return void
	*/
	
 	function import_code($id_code) {
 		$id_code = trim($id_code) ; 
 		$upload_dir = wp_upload_dir();
 		if (!is_dir($upload_dir['basedir']."/legi/global/code_et_TNC_en_vigueur/code_en_vigueur/")) {
 			return array("success"=>false , "msg"=>sprintf(__("No folder %s may be found in %s: Please dowload the XML file on %s and upload the appropriate folder here.", $this->pluginID), "<code>/legi/global/code_et_TNC_en_vigueur/code_en_vigueur/</code>", "<code>".$upload_dir['basedir']."</code>", "<a href='ftp://legi@ftp2.journal-officiel.gouv.fr/'>ftp://legi@ftp2.journal-officiel.gouv.fr/</a>")); 
 		}
 		// We check if the code is in the correct format such as LEGITEXT000006069414
 		if (strlen($id_code)!=20) {
 			return array("success"=>false , "msg"=>sprintf(__("The ID of the code should be 20 characters long (here, we have a %s characters long ID).", $this->pluginID), strlen($id_code))); 
 		}
 		$lev1 = substr($id_code, 0,4) ; 
 		$lev2 = substr($id_code, 4,4) ; 
 		$lev3 = substr($id_code, 8,2) ; 
 		$lev4 = substr($id_code, 10,2) ; 
 		$lev5 = substr($id_code, 12,2) ; 
 		$lev6 = substr($id_code, 14,2) ; 
 		$lev7 = substr($id_code, 16,2) ; 
 		$lev8 = substr($id_code, 18,2) ; 
 		$path = $upload_dir['basedir']."/legi/global/code_et_TNC_en_vigueur/code_en_vigueur/".$lev1."/".$lev2."/".$lev3."/".$lev4."/".$lev5."/".$lev6."/".$lev7."/".$id_code."/" ; 
 		if (!is_dir($path)) {
 			return array("success"=>false , "msg"=>sprintf(__("No folder %s may be found in %s. Please make sure that the ID is correct.", $this->pluginID), "<code>/".$lev1."/".$lev2."/".$lev3."/".$lev4."/".$lev5."/".$lev6."/".$lev7."/".$id_code."/</code>", "<code>".$upload_dir['basedir']."/legi/global/code_et_TNC_en_vigueur/code_en_vigueur/</code>", "<a href='ftp://legi@ftp2.journal-officiel.gouv.fr/'>ftp://legi@ftp2.journal-officiel.gouv.fr/</a>")); 
 		}
		// Recupérer info sur code
		$info_code = serialize(array('status'=>'', 'derniere_maj'=>'' )) ; 
		if (is_file($path."texte/version/".$id_code.".xml")) {
			$oxml = simplexml_load_file ($path."texte/version/".$id_code.".xml") ; 
	 		if ($oxml!==false) {
	 			$status_code = (string)$oxml->META->META_SPEC->META_TEXTE_VERSION->ETAT ; 
	 			$derniere_maj = (string)$oxml->META->META_SPEC->META_TEXTE_CHRONICLE->DERNIERE_MODIFICATION ; 
				$info_code = serialize(array('status'=>$status_code, 'derniere_maj'=>$derniere_maj )) ; 
	 		}
		}
		
 		$nb_entry = $this->import_code_rec($path."article", $info_code) ; 
 	
 		return array("success"=>true , "msg"=>sprintf(__("%s entries have been imported (i.e. %s new entries and %s updated entries).", $this->pluginID), $nb_entry['new']+$nb_entry['update'], $nb_entry['new'], $nb_entry['update'])); 
 	}
 	
	/** ====================================================================================================================================================
	* To import a code from the XML files (reccursive)
	*
	* @return integer nb of entry imported
	*/

 	function import_code_rec($path, $info_code) {
 		global $wpdb ; 
 		
 		if (!is_dir($path)) {
 			return array('new'=>0, 'update'=>0) ; 
 		}
 		
 		$nb_entry = array('new'=>0, 'update'=>0) ;   
 		
 		$list_dir = scandir($path) ; 
 		foreach ($list_dir as $ld) {
 			if (($ld!=".")&&($ld!="..")) {
 				$new_path = $path . DIRECTORY_SEPARATOR . $ld ; 
 				if (is_dir($new_path)) {
					$nb_entry_new = $this->import_code_rec($new_path, $info_code) ; 
					$nb_entry['new'] += $nb_entry_new['new'] ; 
					$nb_entry['update'] += $nb_entry_new['update'] ; 
 				} 
  				if (is_file($new_path)) {
					if (preg_match("/[A-Z0-9]{20}\.xml/i", $ld)) {
						// TODO Import the file
						$parsed_result = $this->parse_article_xml($new_path) ; 
						$already = $wpdb->get_var("SELECT COUNT(*) FROM ".$this->table_name." WHERE id_article='".esc_sql($parsed_result['id_article'])."' AND id_code='".esc_sql($parsed_result['id_code'])."'") ; 
						if ($already!=0) {
							$wpdb->query("UPDATE ".$this->table_name." SET titre_code='".esc_sql($parsed_result['titre_code'])."', info_code='".esc_sql($info_code)."', id_level1='".esc_sql($parsed_result['id_level1'])."',id_level2='".esc_sql($parsed_result['id_level2'])."',id_level3='".esc_sql($parsed_result['id_level3'])."', id_level4='".esc_sql($parsed_result['id_level4'])."',id_level5='".esc_sql($parsed_result['id_level5'])."',id_level6='".esc_sql($parsed_result['id_level6'])."',id_level7='".esc_sql($parsed_result['id_level7'])."', id_level8='".esc_sql($parsed_result['id_level8'])."',id_level9='".esc_sql($parsed_result['id_level9'])."',id_level10='".esc_sql($parsed_result['id_level10'])."', textes_id_level='".esc_sql(serialize($parsed_result['textes_id_level']))."', date_debut='".esc_sql($parsed_result['date_debut'])."', date_fin='".esc_sql($parsed_result['date_fin'])."', num_article='".esc_sql($parsed_result['num_article'])."', state='".esc_sql($parsed_result['state'])."', texte='".esc_sql($parsed_result['texte'])."', version='".esc_sql(serialize($parsed_result['version']))."', lien_autres_articles='".esc_sql(serialize($parsed_result['lien_autres_articles']))."' WHERE id_article='".$parsed_result['id_article']."' AND id_code='".esc_sql($parsed_result['id_code'])."'") ; 
							$nb_entry['update'] ++ ;
						} else {
							$wpdb->query("INSERT INTO ".$this->table_name." (id_article, id_code, titre_code, info_code,id_level1,id_level2,id_level3,id_level4,id_level5,id_level6,id_level7,id_level8,id_level9,id_level10,textes_id_level,date_debut,date_fin,num_article,state,texte,version,lien_autres_articles) VALUES ('".esc_sql($parsed_result['id_article'])."','".esc_sql($parsed_result['id_code'])."','".esc_sql($parsed_result['titre_code'])."', '".esc_sql($info_code)."', '".esc_sql($parsed_result['id_level1'])."','".esc_sql($parsed_result['id_level2'])."','".esc_sql($parsed_result['id_level3'])."','".esc_sql($parsed_result['id_level4'])."','".esc_sql($parsed_result['id_level5'])."','".esc_sql($parsed_result['id_level6'])."','".esc_sql($parsed_result['id_level7'])."','".esc_sql($parsed_result['id_level8'])."','".esc_sql($parsed_result['id_level9'])."','".esc_sql($parsed_result['id_level10'])."','".esc_sql(serialize($parsed_result['textes_id_level']))."','".esc_sql($parsed_result['date_debut'])."','".esc_sql($parsed_result['date_fin'])."','".esc_sql($parsed_result['num_article'])."','".esc_sql($parsed_result['state'])."','".esc_sql($parsed_result['texte'])."','".esc_sql(serialize($parsed_result['version']))."','".esc_sql(serialize($parsed_result['lien_autres_articles']))."')") ; 
							$nb_entry['new'] ++ ;
						}
					}
 				}
 			}
 		}
 		return $nb_entry ; 
 	}
	
	/** ====================================================================================================================================================
	* To parse an article XML file
	*
	* @return integer nb of entry imported
	*/
	
	 function parse_article_xml($path) {
	 	if (!is_file($path)) {
	 		return false ; 
	 	}
	 	$oxml = simplexml_load_file ($path) ; 
	 	if ($oxml===false) {
	 		return false ; 
	 	}
	 	 
	 	$id_article = (string)$oxml->META->META_COMMUN->ID ; 
	 	$num_article = (string)$oxml->META->META_SPEC->META_ARTICLE->NUM ; 
	 	$state = (string)$oxml->META->META_SPEC->META_ARTICLE->ETAT ; 
	 	$date_debut = (string)$oxml->META->META_SPEC->META_ARTICLE->DATE_DEBUT ; 
	 	$date_fin = (string)$oxml->META->META_SPEC->META_ARTICLE->DATE_FIN ; 
	 	
	 	$version = array() ; 
	 	foreach($oxml->VERSIONS->VERSION as $v) {
	 		$attr = $v->LIEN_ART->attributes() ; 
	 		$version[] = array(
	 			"state"=>(string)$attr['etat'], 
	 			"date_debut"=>(string)$attr['debut'], 
	 			"date_fin"=>(string)$attr['fin'], 
	 			"id_article"=>(string)$attr['id'], 
	 			"num_article"=>(string)$attr['num'], 
	 		) ; 
	 	}
	 	
	 	$id_code = (string)$oxml->CONTEXTE->TEXTE->attributes()->cid ; 
	 	$titre_code = (string)$oxml->CONTEXTE->TEXTE->TITRE_TXT ; 
	 	
	 	$id_level1 = "" ; 
	 	$id_level2 = "" ; 
	 	$id_level3 = "" ; 
	 	$id_level4 = "" ; 
	 	$id_level5 = "" ; 
	 	$id_level6 = "" ; 
	 	$id_level7 = "" ; 
	 	$id_level8 = "" ; 
	 	$id_level9 = "" ; 
	 	$id_level10 = "" ; 
	 	$textes_id_level = array() ; 
	 	
	 	// LEVEL 1
	 	if (isset($oxml->CONTEXTE->TEXTE->TM)){
	 		$tm = $oxml->CONTEXTE->TEXTE->TM ; 
	 		$id_level1 = (string)$tm->TITRE_TM->attributes()->id ; 
	 		$textes_id_level[] = (string)$tm->TITRE_TM ; 
	 		
	 		// LEVEL 2
			if (isset($tm->TM)){
				$tm = $tm->TM ; 
				$id_level2 = (string)$tm->TITRE_TM->attributes()->id ;  
				$textes_id_level[] = (string)$tm->TITRE_TM ; 
				
				// LEVEL 3
				if (isset($tm->TM)){
					$tm = $tm->TM ; 
					$id_level3 = (string)$tm->TITRE_TM->attributes()->id ;  
					$textes_id_level[] = (string)$tm->TITRE_TM ; 
			 
					// LEVEL 4
					if (isset($tm->TM)){
						$tm = $tm->TM ; 
						$id_level4 = (string)$tm->TITRE_TM->attributes()->id ;  
						$textes_id_level[] = (string)$tm->TITRE_TM ; 

						// LEVEL 5
						if (isset($tm->TM)){
							$tm = $tm->TM ; 
							$id_level5 = (string)$tm->TITRE_TM->attributes()->id ;  
							$textes_id_level[] = (string)$tm->TITRE_TM ; 
							
							// LEVEL 6
							if (isset($tm->TM)){
								$tm = $tm->TM ; 
								$id_level6 = (string)$tm->TITRE_TM->attributes()->id ;  
								$textes_id_level[] = (string)$tm->TITRE_TM ; 
								
								// LEVEL 7
								if (isset($tm->TM)){
									$tm = $tm->TM ; 
									$id_level7 = (string)$tm->TITRE_TM->attributes()->id ;  
									$textes_id_level[] = (string)$tm->TITRE_TM ; 
									
									// LEVEL 8
									if (isset($tm->TM)){
										$tm = $tm->TM ; 
										$id_level8 = (string)$tm->TITRE_TM->attributes()->id ;  
										$textes_id_level[] = (string)$tm->TITRE_TM ; 
										
										// LEVEL 9
										if (isset($tm->TM)){
											$tm = $tm->TM ; 
											$id_level9 = (string)$tm->TITRE_TM->attributes()->id ;  
											$textes_id_level[] = (string)$tm->TITRE_TM ; 
											
											// LEVEL 10
											if (isset($tm->TM)){
												$tm = $tm->TM ; 
												$id_level10 = (string)$tm->TITRE_TM->attributes()->id ;  
												$textes_id_level[] = (string)$tm->TITRE_TM ; 
			 
											} 
			 
										} 
			 
									} 
			 
								} 
			 
							} 
			 
						} 
			 
					} 
			 	}
			} 
				 
	 	} 
	 	
	 	$texte = $this->SimpleXMLtoHTMLString($oxml->BLOC_TEXTUEL->CONTENU) ; 
	 	
	 	$lien_autres_articles = array() ; 
	 	foreach ($oxml->LIENS->LIEN as $l) {
	 		$lien_autres_articles[] = array('id_texte'=>(string)$l->attributes()->cidtexte, 'id_article'=>(string)$l->attributes()->id, 'sens'=>(string)$l->attributes()->sens,'typelien'=>(string)$l->attributes()->typelien,'date'=>str_replace("2999-01-01","",(string)$l->attributes()->datesignatexte), 'texte'=>(string)$l) ; 
	 	}
	 	
	 	return array("id_code"=>$id_code, "titre_code"=>$titre_code, "id_level1"=>$id_level1, "id_level2"=>$id_level2, "id_level3"=>$id_level3, "id_level4"=>$id_level4, "id_level5"=>$id_level5, "id_level6"=>$id_level6, "id_level7"=>$id_level7, "id_level8"=>$id_level8, "id_level9"=>$id_level9, "id_level10"=>$id_level10, "textes_id_level"=>$textes_id_level, "date_debut"=>$date_debut, "date_fin"=>$date_fin, "id_article"=>$id_article, "num_article"=>$num_article, "state"=>$state, "version"=>$version, "lien_autres_articles"=>$lien_autres_articles, "texte"=>$texte) ; 
	 }
	
	function SimpleXMLtoHTMLString($simplexml){
		$ele=dom_import_simplexml($simplexml);
		$dom = new DOMDocument('1.0', 'utf-8');
		$element=$dom->importNode($ele,true);
		$dom->appendChild($element);
		return $dom->saveHTML();
	} 

	/** ====================================================================================================================================================
	* Make sure that requested uri is a correct uri for the display of the french code
	*
	* @return array result of the check
	*/
	
	function parse_legi_uri() {
		global $wpdb ; 
		
		$url = urldecode($_SERVER['REQUEST_URI']) ; 
		if (strpos($url, "/".$this->get_param("folder_code"))!==false) {
			$url = explode("/".$this->get_param("folder_code"), $url) ; 
			if (count($url)==2) {
				
				$url = trim($url[1]) ; 
				if (substr($url, 0,1)=="/") {
					$url = substr($url,1) ; 
				} 

				if ($url!="") {
				
					//
					// Url de type
					//				/code/recherche/
					//
					//--------------------------------------------------------
					
					// RECHERCHE
					if(preg_match("@^[/]*([\w-]*)/recherche[/]{0,1}$@ui", $url, $matches)) {
						
						$select_query = "SELECT DISTINCT id_code FROM ".$this->table_name." WHERE state='VIGUEUR'" ; 
						$results = $wpdb->get_results($select_query) ; 
						$found_code = false ; 
						// On recherche le code
						foreach ($results as $r) {
							$titre_code = $this->id_code_to_url($r->id_code, false) ; 
							if ($matches[1]==$titre_code) {
								$found_code = $r->id_code ; 
							}
						}
						if ($found_code!=false) {
							// On affiche le résumé du code
							return array($found_code, "recherche") ;
						} else {
							return false ; 
						}
					}
				
					//
					// Url de type
					//				/code/
					//
					//--------------------------------------------------------
					
					if(preg_match("@^[/]*([\w-]*)[/]{0,1}$@ui", $url, $matches)) {
						
						$select_query = "SELECT DISTINCT id_code FROM ".$this->table_name." WHERE state='VIGUEUR'" ; 
						$results = $wpdb->get_results($select_query) ; 
						$found_code = false ; 
						// On recherche le code
						foreach ($results as $r) {
							$titre_code = $this->id_code_to_url($r->id_code, false) ; 
							if ($matches[1]==$titre_code) {
								$found_code = $r->id_code ; 
							}
						}
						if ($found_code!=false) {
							// On affiche le résumé du code
							return array($found_code, false) ;
						} else {
							return false ; 
						}
					}
					
					//
					// Url de type
					//				/code/article/
					//
					//--------------------------------------------------------
					
					if(preg_match("@^[/]*([\w-]*)/([\w-]*)[/]{0,1}$@ui", $url, $matches)) {
					
						$select_query = "SELECT DISTINCT id_code FROM ".$this->table_name." WHERE state='VIGUEUR'" ; 
						$results = $wpdb->get_results($select_query) ; 
						$found_code = false ; 
						// On recherche le code
						foreach ($results as $r) {
							$titre_code = $this->id_code_to_url($r->id_code, false) ; 
							if ($matches[1]==$titre_code) {
								$found_code = $r->id_code ; 
							}
						}
						if ($found_code!=false) {
							// On cherche les articles du code ayant les même trois premières lettres
							$results = $wpdb->get_results("SELECT DISTINCT id_article FROM ".$this->table_name." WHERE id_code='".$found_code."' and LOWER(num_article) LIKE '".esc_sql(substr(str_replace("article_", "", $matches[2]), 0,3))."%' and state='VIGUEUR'") ; 
							$found_article = false ; 
							// On recherche article
							foreach ($results as $r) {
								$num_article = $this->id_article_to_url($r->id_article, false) ; 
								if ($matches[2]==$num_article) {
									$found_article = $r->id_article ; 
								}
							}
							if ($found_article!=false) {
								return array($found_code, $found_article) ;
							} else {
								// On recherche alors dans les articles non-en vigueur
								$results_temp = array() ; 
								$results = $wpdb->get_results("SELECT DISTINCT id_article, date_fin FROM ".$this->table_name." WHERE id_code='".$found_code."' and LOWER(num_article) LIKE '".esc_sql(substr(str_replace("article_", "", $matches[2]), 0,3))."%'") ; 
								// On recherche article
								foreach ($results as $r) {
									$num_article = $this->id_article_to_url($r->id_article, false) ; 
									if ($matches[2]==$num_article) {
										$results_temp[$r->date_fin] = $r->id_article ; 
									}
								}
								if (count($results_temp)==0) {
									return false ; 
								}
								if (count($results_temp)>=1) {
									krsort($results_temp) ; 
									$found_article = array_shift($results_temp) ; 
									return array($found_code, $found_article) ; 
								}
							}
						} else {
							return false ; 
						}
					} else {
						return false ; 
					} 
				} else {
					return false ; 
				}
			} else {
				return false ; 
			}
		} else {
			return false ; 
		}
	}

	
	/** ====================================================================================================================================================
	* Make sure that the template is correct
	*
	* @return string the template path
	*/
	
	 function display_code_change_template($template) {
		global $post;
		global $wpdb;
		global $wp_query ; 
		
		$url = $this->parse_legi_uri() ; 
		if (is_array($url)) {
			if (is_file(get_template_directory(). '/page.php')) {
				$template = get_template_directory() . '/page.php';
			} else {
				if (is_file(get_template_directory(). '/index.php')) {
					$template = get_template_directory() . '/index.php';
				} 
			}
			header("HTTP/1.1 200 OK");
		}
		return $template ; 
	}	
	
	/** ====================================================================================================================================================
	* Create the page with the correct page
	*
	* @return string the template path
	*/

	function display_code_define_post($posts) {
		global $wp, $wp_query, $wpdb;
 
		$url = $this->parse_legi_uri() ; 
		if (is_array($url)) {
			//create a fake post intance
			$post = new stdClass;
			// fill properties of $post with everything a page in the database would have
			$post->ID = -1;                          // use an illegal value for page ID
			$post->post_author = 0;       // post author id
			$post->post_date = date("Y-m-d H:i:s");           // date of post
			$post->post_date_gmt = date("Y-m-d H:i:s");
			$post->post_excerpt = '';
			$post->post_status = 'publish';
			$post->comment_status = 'closed';        // mark as closed for comments, since page doesn't exist
			$post->ping_status = 'closed';           // mark as closed for pings, since page doesn't exist
			$post->post_password = '';               // no password
			$post->post_name = $this->get_param("folder_code") ;
			$post->to_ping = '';
			$post->pinged = '';
			$post->modified = date("Y-m-d H:i:s");
			$post->modified_gmt = date("Y-m-d H:i:s");
			$post->post_content_filtered = '';
			$post->post_parent = 0;
			$post->guid = get_home_url('/' . $this->get_param("folder_code"));
			$post->menu_order = 0;
			$post->post_tyle = "page";
			$post->post_mime_type = '';
			$post->comment_count = 0;

			// set filter results
			$posts = array($post);

			// reset wp_query properties to simulate a found page
			$wp_query->is_page = true;
			$wp_query->is_singular = true;
			$wp_query->is_home = false;
			$wp_query->is_archive = false;
			$wp_query->is_category = false;
			unset($wp_query->query['error']);
			$wp_query->query_vars['error'] = '';
			$wp_query->is_404 = false;
			
			// On modifie le texte pour créer une page d'article
			// Si le deuxieme argument est un string (i.e. id d'article)
			if ((is_string($url[1]))&&($url[1]!="recherche")) {
				$result = $wpdb->get_results("SELECT * FROM ".$this->table_name." WHERE id_article='".$url[1]."'") ;
				$post->post_content = "" ; 
				foreach($result as $r) { 
					$post->post_content .= "#LEGI#".$url[0]."-".$url[1]."#LEGI# " ;
					$post->post_title = "Article ".$r->num_article." | ".$r->titre_code;
				}
			}
			
			// On modifie le texte pour créer une page de recherche
			// Si le deuxieme argument est un string et vaut 'recherche'
			if ((is_string($url[1]))&&($url[1]=="recherche")) {
				$post->post_content .= "#LEGI#".$url[0]."_RECHERCHE#LEGI#" ; 
				$post->post_title = "Recherche | ".$wpdb->get_var("SELECT DISTINCT titre_code FROM ".$this->table_name." WHERE id_code='".$url[0]."' LIMIT 1") ;
			}
			
			// On modifie le texte pour créer une page de code
			// Si le deuxieme argument est false 
			
			if ($url[1]===false) {
				// On ne met pas directement la hierarchie ici car les differents filtres foutent le 
				// bordel sur le javascript du code
				
				$post->post_content .= "#LEGI#".$url[0]."#LEGI#" ; 
				$post->post_title = $wpdb->get_var("SELECT DISTINCT titre_code FROM ".$this->table_name." WHERE id_code='".$url[0]."' LIMIT 1") ;
			}

        }
        return ($posts);
    }
	
	/** ====================================================================================================================================================
	* metadata
	*
	* @return void
	*/
	
	function add_meta_tags() {	
		if (!$this->get_param('allow_crawler')) {
			$url = $this->parse_legi_uri() ; 
			if (is_array($url)) {
				echo  '<meta name="robots" content="nofollow" />'."\r\n" ; 
	        }
		}
		return ; 
	}
	
    
	/** ====================================================================================================================================================
	* Get the section of a code (same as explore but with cache)
	*
	* @return array
	*/

    function get_code_section($code, $article=null) {
    	global $blog_id ; 
		// We create the folder for the backup files
		$blog_fold = "" ; 
		if (is_multisite()) {
			$blog_fold = $blog_id."/" ; 
		}
		
		$array = $this->get_code_section_from_cache($code, $article) ; 
		if (is_array($array)){
			return $array ; 
		}
    	
		$array = $this->explore_code_section($code) ; 
		// we save
		if (!is_dir(WP_CONTENT_DIR."/sedlex/legi-display/".$blog_fold)) {
			@mkdir(WP_CONTENT_DIR."/sedlex/legi-display/".$blog_fold, 0777, true) ; 
		}
		@file_put_contents(WP_CONTENT_DIR."/sedlex/legi-display/".$blog_fold.$code.".array", serialize($array)) ; 
		
		$array = $this->get_code_section_from_cache($code, $article) ; 
		if (is_array($array)){
			return $array ; 
		}
	
    	return $array ;
    }
	
	/** ====================================================================================================================================================
	* Get the section of a code from cache
	*
	* @return array
	*/

	function get_code_section_from_cache($code, $article=null) {
		global $blog_id ; 
		// We create the folder for the backup files
		$blog_fold = "" ; 
		if (is_multisite()) {
			$blog_fold = $blog_id."/" ; 
		}
		
		$array = array() ; 
    	if (is_file(WP_CONTENT_DIR."/sedlex/legi-display/".$blog_fold.$code.".array")) {
			$array_text = @file_get_contents(WP_CONTENT_DIR."/sedlex/legi-display/".$blog_fold.$code.".array") ; 
			if (!is_null($article)) {
				$array_text = str_replace("_in_hiera'", "_in_light'", $array_text) ; 
			}
    		$array = @unserialize($array_text) ; 
    		if (is_array($array)) {
				// Si c'est un article on ferme ce qui n'est pas sur le chemin 
				if (!is_null($article)) {
					$array = $this->close_code_section($array, $article) ; 
				}
    			return $array ; 
    		}
    	} 
		return false ; 		
	}
	
	/** ====================================================================================================================================================
	* Close unneeded the section of a code
	*
	* @return array
	*/

    function close_code_section($array, $article, $level=1) {
    	global $wpdb ; 
    	$new_array = array() ; 
    	$art_seul = false ;
    	$found_at_least = false ; 
    	foreach($array as $a) {
    		if (is_array($a)){
    			if (strpos($a[1], "_art_seul")!==false) {
    				// On le traite à la fin pour savoir si l'article est dans un autre section
    				$art_seul = $a ; 
    			} else {
					$nb = $wpdb->get_var("SELECT COUNT(*) FROM ".$this->table_name." WHERE id_article='".$article."' AND id_level".$level."='".$a[1]."'") ;
					if ($nb==0) {
						$a[3] = false ; 
						// On regarde si ce n'est pas l'article
						if ($article==$a[1]){
							$a[0] = "<b>".$a[0]."</b>" ; 
						}
					} else {
						$a[3] = true ; 
						$found_at_least = true ; 
						$a[0] = str_replace("in_light'", "in_hiera'", $a[0]) ; 
						$a[2] = $this->close_code_section($a[2], $article, $level+1) ; 
					}
					$new_array[] = $a ; 
				}
    		}
    	}
		
    	if (is_array($art_seul)) {
    		if ($found_at_least) {
    			$art_seul[3] = false ;
				$art_seul[0] = str_replace("in_light'", "in_hiera'", $art_seul[0]) ; 
				
    			array_unshift($new_array, $art_seul) ; 
    		} else {
    			// Si on a pas trouve c'est que l'article est dans les articles indépendants
				$art_seul[3] = true ;
				$art_seul[2] = $this->close_code_section($art_seul[2], $article, $level+1) ; 
    			array_unshift($new_array, $art_seul) ; 
    		}
    	} else {
			
		}
    	return $new_array ; 
    }

	/** ====================================================================================================================================================
	* Explore the section of a code
	*
	* @return string the text to replace the shortcode
	*/

    function explore_code_section($code, $level=1, $id_level_prec=false) {
    	global $wpdb ; 
    	if ($level>10) {
    		return array() ; 
    	}
		
		$crawler = "" ; 
		if (!$this->get_param('allow_crawler')){
			$crawler = " rel='nofollow'" ; 
		}
    	
    	$array_out = array() ; 
    	
    	$id_prec = "" ; 
    	if ($level>=2) {
    		$id_prec = " AND id_level".($level-1)."='".$id_level_prec."'" ; 
    	}
    	$result = $wpdb->get_results("SELECT DISTINCT id_level".$level." as id_level FROM ".$this->table_name." WHERE id_code='".$code."' AND state='VIGUEUR'".$id_prec) ;
		// On stock
		$all_sections = array() ; 
		foreach($result as $r) {
			$textes_id_level = @unserialize(stripslashes($wpdb->get_var("SELECT textes_id_level FROM ".$this->table_name." WHERE id_level".$level."='".$r->id_level."' AND state='VIGUEUR' LIMIT 1"))) ;
			
			if ((isset($textes_id_level[$level-1]))&&(trim($textes_id_level[$level-1])!="")) {
				$all_sections[$r->id_level] = trim($textes_id_level[$level-1]) ; 
			}
		}
		// On tri
		$bool = uasort($all_sections, array($this, 'order_sections')); 
		
		// On affiche et on reccurse
		foreach($all_sections as $k=>$as) {
			$string_out = "" ; 
	
			$recu_resu = $this->explore_code_section($code, $level+1, $k) ; 
			
			if (empty($recu_resu)) {
				// C'est le dernier niveau donc on l'indique
				$so = explode(":",strip_tags($as)) ; 
				$so[0] = "<b>".$so[0]."</b>" ; 
				$string_out .= implode(":", $so)." (" ; 
				$list_article = $wpdb->get_results("SELECT id_article, num_article FROM ".$this->table_name." WHERE id_code='".$code."' AND state='VIGUEUR' AND id_level".($level)."='".$k."'") ;
				$list_a = array() ; 
				// On sauve les articles
				foreach ($list_article as $la) {
					$list_a[$la->id_article] = $la->num_article ; 
				}
				
				// On classe les articles
				$bool = uasort($list_a, array($this, 'order_articles')); 
				
				// On affiche
				$j = 0 ; 
				foreach ($list_a as $ka=>$la) {
					$j++ ; 
					if (count($list_a)==1) {
						// article
						$string_out .= "<span class='art_in_hiera'>".strip_tags($la)."</span>" ; 
					} else {
						if ($j==1) {
							// section
							$string_out .= "de <span class='art_in_hiera'>".strip_tags($la) ; 
						}
					}
					if (($j==count($list_a)) && ($j!=1)) {
						$string_out .= " à ".strip_tags($la)."</span>" ; 
						$string_out .= " - ".count($list_a)." articles" ; 
					} 
				}
				if (count($list_a)==0) {
					$string_out .= "Vide" ; 
				}
				
				$string_out .= ")" ; 
				// On cree la list des articles ; 
				$article_full = array() ; 
				foreach ($list_a as $ka=>$la) {
					$article_full[] = array("<a".$crawler." href='".$this->id_article_to_url($ka)."'>".strip_tags($la)."</a>", $ka) ; 
				}
				
				$array_out[] = array("<span class='p_in_hiera'>".$string_out."</span>", $k, $article_full, false) ; 
			} else {
			
			    // On regarde s'il y a des articles seuls  
				if ($level<10) {	
					$list_article = $wpdb->get_results("SELECT id_article, num_article FROM ".$this->table_name." WHERE id_code='".$code."' AND state='VIGUEUR' AND id_level".($level)."='".$k."' AND id_level".($level+1)."=''") ;
					if (count($list_article)>0) {
						$list_a = array() ; 
						// On sauve les articles
						foreach ($list_article as $la) {
							$list_a[$la->id_article] = $la->num_article ; 
						}
						// On classe les articles
						$bool = uasort($list_a, array($this, 'order_articles')); 
			
						// On affiche
						$j = 0 ; 
						$string_out = "<b>Articles seuls</b> (" ; 
						foreach ($list_a as $ka=>$la) {
							$j++ ; 
							if (count($list_a)==1) {
								// article
								$string_out .= "<span class='art_in_hiera'>".strip_tags($la)."</span>" ; 
							} else {
								if ($j==1) {
									// section
									$string_out .= "de <span class='art_in_hiera'>".strip_tags($la) ; 
								}
							}
							if (($j==count($list_a)) && ($j!=1)) {
								$string_out .= " à ".strip_tags($la)."</span>" ; 
								$string_out .= " - ".count($list_a)." articles" ; 
							} 
						}
			
						$string_out .= ")" ; 
						// On cree la list des articles ; 
						$article_full = array() ; 
						foreach ($list_a as $ka=>$la) {
							$article_full[] = array("<a".$crawler." href='".$this->id_article_to_url($ka)."'>".strip_tags($la)."</a>", $ka) ; 
						}
			
						array_unshift($recu_resu, array($string_out, $k."_art_seul", $article_full, false)) ; 
					}
				}
			
			
				$so = explode(":",strip_tags($as)) ; 
				$so[0] = "<b>".$so[0]."</b>" ; 
				$array_out[] = array("<span class='p_in_hiera'>".implode(":", $so)."</span>", $k, $recu_resu, true) ; 
			}
		}
		
		return $array_out ; 	
    }

	
	/** ====================================================================================================================================================
	* Create a list of available code
	*
	* @return string the text to replace the shortcode
	*/

    function legi_shortcode( $_atts, $string ) {
    	global $wpdb ; 
    	
		$atts = shortcode_atts( array(
			'code' => 'all'
		), $_atts );
		
		$crawler = "" ; 
		if (!$this->get_param('allow_crawler')){
			$crawler = " rel='nofollow'" ; 
		}		
		
		$result = "<ul class='list_code_legi'>" ; 
		$select_query = "SELECT DISTINCT id_code, titre_code FROM ".$this->table_name." WHERE state='VIGUEUR'" ; 
		$results = $wpdb->get_results($select_query) ;
		$found = false ;  
		foreach ($results as $r) {
			$url_to_code = $this->id_code_to_url($r->id_code) ; 
			$result .= "<li><p><a".$crawler." href='".$url_to_code."'>".$r->titre_code."</a></p></li>" ; 
			$found = true ; 
		}	
		$result .= "</ul>" ; 		
		if ($found==false) {
			return "<p>Aucun code importé dans votre base de données</p>" ; 
		}

		return $result ; 
	}
	
	
	/** ====================================================================================================================================================
	* Callback to display a new article
	*
	* @return void
	*/

	function updateContenuLegi() {
		global $_POST;
		global $wpdb ; 
		
		$id1 = preg_replace("/[^\w]/ui","",$_POST['id1']) ; 
		if (isset($_POST['id2'])) {
			$id2 = preg_replace("/[^\w]/ui","",$_POST['id2']) ; 
		} else {
			$id2 = "" ; 
		}

		if ((($id1!="")&&($id2==""))||(($id1!="")&&($id2==$id1))) {
			$result = $wpdb->get_results("SELECT * FROM ".$this->table_name." WHERE id_article='".$id1."'") ;
			foreach($result as $r) { 
				echo $this->mise_en_forme_article($r) ; 
			}
		} elseif (($id1!="")&&($id2!="")) {
			$result1 = $wpdb->get_results("SELECT * FROM ".$this->table_name." WHERE id_article='".$id1."'") ;
			$result2 = $wpdb->get_results("SELECT * FROM ".$this->table_name." WHERE id_article='".$id2."'") ;
			foreach($result1 as $r1) { 
				foreach($result2 as $r2) { 
					echo $this->mise_en_forme_article($r1, $r2) ; 
				}
			}
		} else {
			echo __('Error: no id provided', $this->pluginID) ; 
		}
		
		die() ; 
	}
	
	/** ====================================================================================================================================================
	* TYranform the texte to be displayed
	*
	* @return string the text
	*/
 
	function mise_en_forme_article($article, $o_article=null) {
		global $wpdb;
		
		$crawler = "" ; 
		if (!$this->get_param('allow_crawler')){
			$crawler = ' rel="nofollow"' ; 
		}		
		
		$texte = html_entity_decode($article->texte);
		if (!is_null($o_article)){
			$texte2 = html_entity_decode($o_article->texte);
		}
		
		// GESTION DE LA DATE
		if ($article->state=="VIGUEUR") {
			$date = "<p class='date_vigueur'>En vigueur depuis le ".$article->date_debut."</p>";
		} else {
			$date = "<p class='date_vigueur'>".ucfirst(strtolower($article->state))." - Cet article était en vigueur entre le ".$article->date_debut." et ".$article->date_fin."</p>";
		}
		
		// GESTION DU CONTENU 1
		$texte = trim(preg_replace("@<br[\s/]*>@ui", " ### ", $texte)) ; 
		$texte = trim(preg_replace("@<[/]*p[\s]*>@ui", " ### ", $texte)) ;
		$texte = trim(preg_replace("@([^\s])([.,;:])@ui", "$1 $2", $texte)) ;
		$texte = trim(preg_replace("@([.,;:])([^\s])@ui", "$1 $2", $texte)) ;
		
		$texte = preg_replace('/\s\s+/', ' ', $texte);
		
		// GESTION DU CONTENU 2
		if (!is_null($o_article)) {
			$texte2 = trim(preg_replace("@<br[\s/]*>@ui", " ### ", $texte2)) ; 
			$texte2 = trim(preg_replace("@<[/]*p[\s]*>@ui", " ### ", $texte2)) ;
			$texte2 = trim(preg_replace("@([^\s])([.,;:])@ui", "$1 $2", $texte2)) ;
			$texte2 = trim(preg_replace("@([.,;:])([^\s])@ui", "$1 $2", $texte2)) ;
			
			$texte2 = preg_replace('/\s\s+/', ' ', $texte2);
			 
			$diff = new SLFramework_Textdiff() ; 
			$diff->diff(strip_tags($texte2), strip_tags($texte)) ;
			$texte = html_entity_decode($diff->show_simple_difference()) ; 
		}
		
		$texte = str_replace(" .", ".", $texte) ; 
		$texte = str_replace(" ,", ",", $texte) ;  
				
		// Si jamais un paragraphe est doit couper une modification, on ferme et on réouvre la modification
		if (!is_null($o_article)) {
			$texte = preg_replace_callback("@<span class='([^']*)'>([^<]*)</span>@ui", array($this, "checkParagraphAndModif"), $texte) ; 
		}
		
		$texte = explode("###", $texte) ; 
		$newtexte = "" ; 
		// On gère les paragraphes
		foreach ($texte as $t){
			if (trim($t)!=""){
				if (preg_match("@^[0-9]+°.*$@ui", preg_replace("@\s@ui", "", $t))) {
			  		$newtexte .= "<p class='legi_p_puce'>".trim($t)."</p>" ; 
			  	} elseif (preg_match("@^-.*$@ui", preg_replace("@\s@ui", "", $t))) {
			  		$newtexte .= "<p class='legi_p_puce'>".trim($t)."</p>" ; 
			  	} else {
			  		$newtexte .= "<p class='legi_p'>".trim($t)."</p>" ; 
			  	}
			}
		}
		
		// LIENS
		$liens = "" ; 
		$liens_interne = "<h4>Liens vers des articles de ce code</h4>" ; 
		$liens_externe = "<h4>Liens vers d'autres textes</h4>" ; 		
		$already_displayed_links = array() ; 
		$nb_interne = 0 ;  
		$nb_externe = 0 ;  
		
		if ($article->lien_autres_articles!="") {
			$lien_autres_articles = @unserialize($article->lien_autres_articles) ; 
			if ($lien_autres_articles!==false){	
				$liens .= "<div class='lien_contenu_legi'>" ; 

				foreach($lien_autres_articles as $v) {
					// On verifie que l'on ne l'a pas deja traité
					$needle = $v['id_texte'].$v['id_article'] ; 
					if (in_array($needle, $already_displayed_links)){
						continue;
					} else {
						$already_displayed_links[] = $needle ; 
					}
					
					// autres possibilités non-implémentées
					//------------------------------------------------------
					// ADHESION, ADHERE,  
					// ANNULATION, ANNULE,  
					// DENONCIATION, DENONCE,  
					// DIRECTIVE_EUROPEENNE,  
					// DISJONCTION, DISJOINT,  
					// ELARGISSEMENT, ELARGIT,  
					// RENVOIT, RENVOI,  
					// EXTENSION, ETEND,   
					// DEPLACEMENT, DEPLACE,  
					// PEREMPTION, PERIME,  
					// PILOTE_SUIVEUR,  
					// PUBLICATION,  
					// SARDE,  
					// TEXTE_SUITE,  
					// TRANSFERT, TRANSFERE,  
					// RATTACHEMENT, RATTACHE,  
					// RECTIFICATION, RECTIFIE,  
					// SUITE_PROCEDURALE,  
					// JURISPRUDENTIEL,  
					// CITATION_JURI_LEGI ,  
					// PERIME_NVCC_IDCC,  
					// PEREMPTION_NVCC_IDCC,  
					// HISTO,  
					// TXT_SOURCE,  
					// SPEC_APPLI,  
					// TXT_ASSOCIE

					$sens_info = "" ;  
					if (($v['typelien']=="CODIFICATION")||($v['typelien']=="CODIFIE")) {
						$sens_info .= "codifié par " ; 
					} elseif (($v['typelien']=="CREATION")||($v['typelien']=="CREE")) {
						$sens_info .= "créé par " ; 
					} elseif (($v['typelien']=="CONCORDANCE")||($v['typelien']=="CONCORDE")) {
						$sens_info .= "ancien texte " ; 
					} elseif (($v['typelien']=="ABROGATION")||($v['typelien']=="ABROGE")) {
						$sens_info .= "abrogé par " ; 
					} elseif (($v['typelien']=="APPLICATION")||($v['typelien']=="APPLIQUE")) {
						$sens_info .= "mis en application par " ; 
					} elseif ($v['typelien']=="TRANSPOSITION") {
						$sens_info .= "transposant " ; 
					} elseif (($v['typelien']=="CITATION")&&($v['sens']=="source")) {
						$sens_info .= "citant l'article  " ; 
					} elseif (($v['typelien']=="CITATION")&&($v['sens']=="cible")) {
						$sens_info .= "cité par  " ; 
					} elseif (($v['typelien']=="MODIFICATION")||($v['typelien']=="MODIFIE")) {
						$sens_info .= "modifié par  " ; 
					} elseif ($v['typelien']=="SPEC_APPLI") {
						$sens_info .= "conditions d'application données par " ; 
					} else {
						$sens_info .= $v['typelien']." - ".$v['sens']." : " ; 
					}
					
					// Lien interne
					if ($article->id_code==$v['id_texte']) {
						$nom_art = $wpdb->get_var("SELECT num_article FROM ".$this->table_name." WHERE id_article='".$v['id_article']."'") ;
						
						$found_article = false ; 
						// On ne remplace l'article que si on est pas en mode comparaison
						if ((is_null($o_article))&&(!is_null($nom_art))) {						
							$nom_art_temp=preg_replace("@[^A-Z0-9-]@ui", "", $nom_art) ; 
							$nom_art_regex=preg_replace("@([A-Z])([0-9])@ui", "$1(?:.{0,3})$2", $nom_art_temp) ; 
							$newtexte2=preg_replace("@".$nom_art_regex."([^A-Z0-9-/])@ui", '<a'.$crawler.' href="'.$this->id_article_to_url($v['id_article']).'">'.$nom_art_temp.'</a>$1', $newtexte) ; 
							if ($newtexte2!=$newtexte){
								// On a remplacé au moins un article
								$newtexte = $newtexte2 ; 
								$found_article = true ; 
							} 
						} 
						// Si l'article n'existe pas, on ignore en faisant croire qu'on a trouvé
						if (is_null($nom_art)) {
							$found_article = true ; 
						}
						if (!$found_article){
							$liens_interne .= "<p>".$sens_info.'<a'.$crawler.' href="'.$this->id_article_to_url($v['id_article']).'">'.$nom_art."</a></p>";
							$nb_interne ++ ;
						}
						
					// Lien externe
					} else {
						if (($v['id_texte']==$v['id_article'])&&($v['id_texte']!="")||($v['id_texte']!=$v['id_article'])&&($v['id_article']=="")) {
							$liens_externe .= "<p>".$sens_info.'<a'.$crawler.' href="http://legifrance.gouv.fr/affichTexte.do?cidTexte='.$v['id_texte'].'">'.$v['texte']."</a></p>";
							$nb_externe ++ ; 
						} elseif ($v['id_texte']!=$v['id_article']) {
							$liens_externe .= "<p>".$sens_info.'<a'.$crawler.' href="http://legifrance.gouv.fr/affichTexteArticle.do?cidTexte='.$v['id_texte'].'&idArticle='.$v['id_article'].'">'.$v['texte']."</a></p>";
							$nb_externe ++ ; 
						} else {
							$liens_externe .= "<p>(".$v['sens']."-".$v['typelien']."-".preg_replace("/[^0-9]/ui", "", $v['date']).") ".$v['texte']." (".$v['id_texte']." - ".$v['id_article'].")</p>";
							$nb_externe ++ ; 
						}
					}
				}
			}
		}
		
		if ($nb_interne>0) {
			$liens .= $liens_interne ; 
		}
		if ($nb_externe>0) {
			$liens .= $liens_externe ; 
		}
		$liens .= "</div>" ; 
		if ($nb_interne+$nb_externe==0) {
			$liens = "" ; 
		}
		
		// ON RETOURNE LE TOUT
		
		return $date.$newtexte.$liens;
	}
	
	function checkParagraphAndModif($matches){
		$returnString = "" ; 
		$interieur = explode("###", $matches[2]) ; 
		$j = 0 ; 
		foreach ($interieur as $i) {
			if ($j != 0){
				$returnString .= "###" ;
			}
			$j++ ; 
			$returnString .= "<span class='".$matches[1]."'>" ; 
			$returnString .= $i ;
			$returnString .= "</span>" ; 
		}
		return $returnString ; 
	}

	/** ====================================================================================================================================================
	* Find the previous/next article
	*
	* @return array the url
	*/
 
	function find_previous_next_article($array, $article, $level=1, $result) {
    	global $wpdb ; 
    	$art_seul = false ;
    	$found_at_least = false ; 
    	$result = false ; 
    	foreach($array as $a) {
    		if (is_array($a)){
    			if (strpos($a[1], "_art_seul")!==false) {
    				// On le traite à la fin pour savoir si l'article est dans un autre section
					$art_seul[2] = $this->find_previous_next_article($art_seul[2], $article, $level+1) ; 
    			} else {
					$nb = $wpdb->get_var("SELECT COUNT(*) FROM ".$this->table_name." WHERE id_article='".$article."' AND id_level".$level."='".$a[1]."'") ;
					if ($nb!=0) {
						$a[2] = $this->find_previous_next_article($a[2], $article, $level+1) ; 
					}
				}
    		}
    	}
		
    	return $new_array ; 
	}
	

	/** ====================================================================================================================================================
	* Transform an id of a code into an URL
	*
	* @return string the url
	*/
 
	function id_code_to_url($id, $all=true) {
		global $wpdb ; 
		$select_query = "SELECT titre_code FROM ".$this->table_name." WHERE id_code='".$id."'" ; 
		$results = $wpdb->get_results($select_query) ;
		foreach ($results as $r) {
			$titre_code = strtolower($r->titre_code) ;
			$titre_code = str_replace(" du ", " ", $titre_code) ; 
			$titre_code = str_replace(" des ", " ", $titre_code) ; 
			$titre_code = str_replace(" de ", " ", $titre_code) ; 
			$titre_code = str_replace(" le ", " ", $titre_code) ; 
			$titre_code = str_replace(" la ", " ", $titre_code) ; 
			$titre_code = str_replace(" l'", " ", $titre_code) ; 
			$titre_code = str_replace(", ", " ", $titre_code) ; 
			$titre_code = str_replace(".", "", $titre_code) ; 
			$titre_code = preg_replace("/[^\w-]/ui", "_", $titre_code) ;
			if ($all) {
				$url_to_code =  site_url()."/".$this->get_param("folder_code")."/".$titre_code.'/' ; 
			} else {
				$url_to_code = $titre_code ; 
			}
			return $url_to_code ; 
		}		
	}
	
	/** ====================================================================================================================================================
	* Transform an id of a article into an URL
	*
	* @return string the url
	*/

	function id_article_to_url($id, $all=true) {
		global $wpdb ; 
		$select_query = "SELECT id_code, num_article FROM ".$this->table_name." WHERE id_article='".$id."'" ; 
		$results = $wpdb->get_results($select_query) ;
		foreach ($results as $r) {
			$titre_code = $this->id_code_to_url($r->id_code, false) ;
			
			$num_article = strtolower($r->num_article) ;
			$num_article = preg_replace("/[^\w-]/ui", "_", $num_article) ; 
			
			if ($all) {
				$url_to_article =  site_url()."/".$this->get_param("folder_code")."/".$titre_code.'/'."article_".$num_article."/" ; 
			} else {
				$url_to_article = "article_".$num_article ; 
			}
			return $url_to_article ; 
		}		
	}
	
	/** ====================================================================================================================================================
	* Give an order for articles
	*
	* @return integer
	*/
	
	function order_articles($a, $b) {
		$temp = $a ; 
		$temp = preg_replace("/[^0-9-]/ui", "", $temp) ; 
		$temp = explode("-", $temp) ; 
		$a = $temp ; 
	
		$temp = $b ; 
		$temp = preg_replace("/[^0-9-]/ui", "", $temp) ; 
		$temp = explode("-", $temp) ; 
		$b = $temp ; 
	
		for($i=0 ; $i<count($a) ; $i++) {
			if (!isset($b[$i])) {
				return 1 ; 
			} 
			if ($b[$i]<$a[$i]) {
				return 1 ; 
			}
			if ($b[$i]>$a[$i]) {
				return -1 ; 
			}
		}
							
		return -1 ; 
	}
	
	/** ====================================================================================================================================================
	* Give an order for sections
	*
	* @return integer
	*/
	function order_sections($a, $b) {
		
		$test=false ; 
		if ((strpos($a, "Titre préliminaire")!==false)||(strpos($a, "Titre préliminaire")!==false)) {
			$test = true ; 
		}
		
		$romans = array(
			'M' => 1000,
			'CM' => 900,
			'D' => 500,
			'CD' => 400,
			'C' => 100,
			'XC' => 90,
			'L' => 50,
			'XL' => 40,
			'X' => 10,
			'IX' => 9,
			'V' => 5,
			'IV' => 4,
			'I' => 1,
		);

		// On modifie a
		$temp = $a ; 
		$temp = str_replace("Partie", "", $temp) ; 
		$temp = str_replace("Livre", "", $temp) ; 
		$temp = str_replace("Titre", "", $temp) ; 
		$temp = str_replace("Chapitre", "", $temp) ; 
		$temp = str_replace("Section", "", $temp) ; 
		$temp = str_replace("Sous-section", "", $temp) ; 
		$temp = str_replace("Paragraphe", "", $temp) ; 
		
		$temp = str_replace("préliminaire", "0", $temp) ; 
		$temp = str_replace("Ier", "I", $temp) ; 
		$temp = str_replace("Premier", "1", $temp) ; 
		$temp = str_replace("Première", "1", $temp) ; 
		$temp = str_replace("Deuxième", "2", $temp) ; 
		$temp = str_replace("Second", "2", $temp) ; 
		$temp = str_replace("Troisième", "3", $temp) ; 
		$temp = str_replace("Quatrième", "4", $temp) ; 
		$temp = str_replace("Cinquième", "5", $temp) ; 
		$temp = str_replace("Sixième", "6", $temp) ; 
		$temp = str_replace("Septième", "7", $temp) ; 
		$temp = str_replace("Huitième", "8", $temp) ; 
		$temp = str_replace("Neuvième", "9", $temp) ; 
		$temp = explode(" ", $temp) ; 
		$na = array() ; 
		foreach ($temp as $pa) {
			// Roman number
			if (preg_match("/^[MCDXLVI]+$/u", $pa)) {
				$result_a = 0;
				foreach ($romans as $key => $value) {
					while (strpos($pa, $key) === 0) {
						$result_a += $value;
						$pa = substr($pa, strlen($key));
					}
				}
				$na[]= $result_a ;
			} else {
				$na[]= $pa ; 
			}
		}	
		$a = implode(" ", $na) ; 
		
		// On modifie b
		$temp = $b ; 
		$temp = str_replace("Partie", "", $temp) ; 
		$temp = str_replace("Livre", "", $temp) ; 
		$temp = str_replace("Titre", "", $temp) ; 
		$temp = str_replace("Chapitre", "", $temp) ; 
		$temp = str_replace("Section", "", $temp) ; 
		$temp = str_replace("Sous-section", "", $temp) ; 
		$temp = str_replace("Paragraphe", "", $temp) ; 
		
		$temp = str_replace("préliminaire", "0", $temp) ; 
		$temp = str_replace("Ier", "I", $temp) ; 
		$temp = str_replace("Premier", "1", $temp) ; 
		$temp = str_replace("Première", "1", $temp) ; 
		$temp = str_replace("Deuxième", "2", $temp) ; 
		$temp = str_replace("Second", "2", $temp) ; 
		$temp = str_replace("Troisième", "3", $temp) ; 
		$temp = str_replace("Quatrième", "4", $temp) ; 
		$temp = str_replace("Cinquième", "5", $temp) ; 
		$temp = str_replace("Sixième", "6", $temp) ; 
		$temp = str_replace("Septième", "7", $temp) ; 
		$temp = str_replace("Huitième", "8", $temp) ; 
		$temp = str_replace("Neuvième", "9", $temp) ; 
		$temp = explode(" ", $temp) ; 
		$na = array() ; 
		foreach ($temp as $pa) {
			// Roman number
			if (preg_match("/^[MCDXLVI]+$/u", $pa)) {
				$result_a = 0;
				foreach ($romans as $key => $value) {
					while (strpos($pa, $key) === 0) {
						$result_a += $value;
						$pa = substr($pa, strlen($key));
					}
				}
				$na[]= $result_a ;
			} else {
				$na[]= $pa ; 
			}
		}	
		$b = implode(" ", $na) ; 
		
		if ($test) {
			echo "$a - $b <br>" ; 
		}
		
		return strcmp($a,$b) ; 
	}
	
}


$legi_display = legi_display::getInstance();

?>