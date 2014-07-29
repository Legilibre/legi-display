<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class create a page with the other plugins of the author listed
*/

if (!class_exists("SLFramework_Popup")) {
	class SLFramework_Popup {
	   
		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @param string $title the title which will be displayed in the top of the popup
		* @param string $css the css of the popup if needed.
		* @param string $content the content of the popup
		* @return void 
		*/
		
		public function SLFramework_Popup($title, $content, $css="", $callback="") {
			$this->callback = $callback ; 
			$this->css = $css ; 
			$this->title = $title ; 
			$this->content = $content ; 
			$this->javascriptdisplayed = false ; 
		}
		
		/** ====================================================================================================================================================
		* Display the popup
		* 
		* @return void 
		*/
		
		function render() {
		ob_start() ;
   			?>
   			<script id='popupScript'>
   			var popupStatus = 0;  
   			
   			//loading popup with jQuery magic!  
			function loadPopup(){  
				//loads popup only if it is disabled  
				if(popupStatus==0){  
					jQuery("#backgroundPopup").css({  
						"opacity": "0.7"  
					});  
					jQuery("#backgroundPopup").fadeIn("slow");  
					jQuery("#popupForm").fadeIn("slow");  
					popupStatus = 1;  
				}  
			} 
			
			//disabling popup with jQuery magic!  
			function disablePopup(){  
				//disables popup only if it is enabled  
				if(popupStatus==1){  
					jQuery("#backgroundPopup").fadeOut("slow", function() { jQuery("#backgroundPopup").remove(); });  
					jQuery("#popupForm").fadeOut("slow", function() { jQuery("#popupForm").remove(); jQuery("#popupScript2").remove(); jQuery("#popupScript").remove(); } );  
					<?php echo $this->callback ; ?>
					popupStatus = 0;  
				}  
			} 
			
    		</script>
   			<?php
   			echo ob_get_clean() ; 
		
			echo "<div id='backgroundPopup' style='".$this->css."'>" ; 
			echo "</div>" ; 
   			echo "<div id='popupForm'>" ; 
			echo "	<a href='#' id='popupFormClose' onClick='disablePopup() ; return false ; '><img src='".plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."img/close_popup.png' alt='".__("Close popup", 'SL_framework')."'/></a>" ;  
			echo "	<div id='titlePopupForm'>" ; 
			echo "		<p>".$this->title."</p>" ; 
			echo "	</div>" ; 
			echo "	<div id='innerPopupForm'>" ; 
			echo $this->content ;  
   			echo "	</div>" ; 
   			echo "</div>" ; 
			
   			
   			ob_start() ;
   			?>
   			<script id='popupScript2'>
    		
			//load popup  
			loadPopup(); 
			
    		</script>
   			<?php
   			echo ob_get_clean() ; 
		}
	} 
}


?>