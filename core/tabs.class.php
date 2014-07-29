<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enables the creation of tabulation in the admin backend
*/
if (!class_exists("SLFramework_Tabs")) {
	class SLFramework_Tabs  {
		var $title ; 
		var $content ; 
		var $activated ; 
		
		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @return SLFramework_Tabs the tabs
		*/
		
		function SLFramework_Tabs() {	
			$this->title = array() ; 
			$this->content = array() ; 
			$this->image = array() ; 
			$this->activated = 0 ; 
		}
		
		/** ====================================================================================================================================================
		* Add a tabulation
		* For instance, 
		* <code>$tabs = new SLFramework_Tabs() ; <br/> ob_start() ;  <br/> echo "Content 1" ;  <br/> $tabs->add_tab("Tab1", ob_get_clean() ) ; 	 <br/> ob_start() ;  <br/> echo "Content 2" ;  <br/> $tabs->add_tab("Tab2", ob_get_clean() ) ;  <br/> echo $tabs->flush() ; </code>
		* will create to basic tabulation.
		* @param string $title the title of the tabulation
		* @param string $content the HTML content of the tab
		* @param string $image the path of an image that will be display before the title. Please indicate a 20x20px image.
		* @return void
		*/
		
		function add_tab($title, $content, $image="") {
			$this->title[] = $title ; 
			$this->content[] = $content ; 
			$this->image[] = $image ; 
		}
		
		/** ====================================================================================================================================================
		* Change the tabs activated by default (normally it is the first tab i.e. 1)
		* 1 is the first, 2 is the second, etc.
		* 
		* @param integer $nb the tabultaion index to activate
		* @return void
		*/
		function activate($nb) {
			$this->activated = $nb-1 ; 
		}
		
		/** ====================================================================================================================================================
		* Print the tabulation HTML code. 
		* 
		* @return void
		*/
		function flush() {
			global $_SERVER ; 
			ob_start() ; 
			$rnd = rand(1, 100000) ; 
?>
			<script>
				function setCookie(name,value,days) {
					if (days) {
						var date = new Date();
						date.setTime(date.getTime()+(days*24*60*60*1000));
						var expires = "; expires="+date.toGMTString();
					}
					else var expires = "";
					document.cookie = name+"="+value+expires+"; path=/";
				}
				
				function getCookie(name) {
					var nameEQ = name + "=";
					var ca = document.cookie.split<?php $a="to avoid false detection of the deprecated function" ; ?>(';');
					for(var i=0;i < ca.length;i++) {
						var c = ca[i];
						while (c.charAt(0)==' ') c = c.substring(1,c.length);
						if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
					}
					return null;
				}
				
				function deleteCookie(name) {
					setCookie(name,"",-1);
				}

				jQuery(document).ready(function($){ 
					<?php 
					if ($this->activated != 0) {
					?>
					jQuery('#tabs<?php echo $rnd ; ?>').tabs({active:<?php echo ($this->activated) ?>}) ; 
					<?php
					} else {
					?>
					if (getCookie("tabSL")!=null) {
						var indexToGo = jQuery('#tabs<?php echo $rnd ; ?> a[href="#'+getCookie("tabSL")+'"]').parent().index();
						if (indexToGo==-1) {
							indexToGo = 0 ; 
						}
						jQuery("#tabs<?php echo $rnd ; ?>").tabs({active:indexToGo});
					} 
					<?php
					}
					?>
					jQuery('#tabs<?php echo $rnd ; ?>').tabs({ activate: function(event, ui) { 
						idToGo = ui.newTab.find("a").attr('href').split<?php $a="to avoid false detection of the deprecated function"; ?>("#") ; 
						setCookie("tabSL", idToGo[1], 1 ) ; 
					} });
				}) ; 
			</script>		
			
			<div class="tabsSL" id="tabs<?php echo $rnd ; ?>">
				<ul class="hide-if-no-js">
<?php
			$all = implode("", $this->title) ; 
			
			for ($i=0 ; $i<count($this->title) ; $i++) {
				if ($this->image[$i]=="") {
					$this->image[$i] = plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."img/tab_empty.png" ; 
				}
?>					<li><a href="#tab-<?php echo sha1($all.$this->title[$i]) ?>"><img style='vertical-align:middle;' src='<?php echo $this->image[$i] ?>'> <?php echo $this->title[$i] ?></a></li>		
<?php
			}
?>				</ul>
<?php
			for ($i=0 ; $i<count($this->title) ; $i++) {
?>				<div id="tab-<?php echo sha1($all.$this->title[$i]) ?>" class="blc-section">
					<?php echo $this->content[$i] ; ?>
				</div>
<?php
			}
?>
			</div>
<?php		return ob_get_clean() ; 
		}
	}
}


?>