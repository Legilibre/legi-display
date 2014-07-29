<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 
/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enables the creation of a progress bar
*/
if (!class_exists("SLFramework_Progressbar")) {
	class SLFramework_Progressbar {
		
		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @param integer $length the width in pixel of the progress bar
		* @param integer $height the height in pixel of the progress bar
		* @param integer $start the % of the start (progression)
		* @param string $insideText the text to put in the progress bar
		* @param string $id the identifieur if there is a pluralitu of progress bar (the image which moves is named $id."_image", the text is named $id."_txt")
		* @return boxAdmin the box object
		*/
		
		function SLFramework_Progressbar($length=300, $height=20, $start=0, $insideText="", $id="progressbar") {
			$this->length = $length ; 
			$this->insideText = $insideText ; 
			$this->height = $height ; 
			$this->start = $start ; 
			$this->id = $id ; 
		}
		
		
		/** ====================================================================================================================================================
		* Print the progress bar code
		* Once the progress bar is displayed, you may modify the progression by calling in javascript <code>progressBar_modifyProgression(25,"id")</code> which modify the progression to 25% for the ID "id" (the ID is not mandatory, by default the ID will be "progressbar")
		* Once the progress bar is displayed, you may modify the text by calling in javascript <code>progressBar_modifyText("new text","id")</code> which modify the text to "new text" for the ID "id" (the ID is not mandatory, by default the ID will be "progressbar")
		* 
		* @return void
		*/
		function flush()  {
			ob_start();
			?>
			 <div class="progressbar" style="position:relative;overflow:hidden; height: <?php echo $this->height ; ?>px;width:<?php echo $this->length ; ?>px;">
				<img src="<?php echo plugin_dir_url("/")."/".str_replace(basename(__FILE__),"",plugin_basename( __FILE__)); ?>/img/progressbar.png" style='position:absolute;left:0;top:-<?php echo floor(2*$this->height) ; ?>px;height:<?php echo floor(3*$this->height) ; ?>px;width:<?php echo $this->length ; ?>px;'/>
				<img  id="<?php echo $this->id."_image"; ?>" src="<?php echo plugin_dir_url("/")."/".str_replace(basename(__FILE__),"",plugin_basename( __FILE__)); ?>/img/progressbar.png" style='position:absolute;left:0;top:0px;height:<?php echo floor(3*$this->height) ; ?>px;width:<?php echo floor($this->length*$this->start/100) ; ?>px;'/>
				<div  id="<?php echo $this->id."_text"; ?>" style='position:absolute;left:0;top:0px;height:<?php echo $this->height; ?>px;text-align:center;line-height:<?php echo $this->height; ?>px;width:<?php echo $this->length ; ?>px;'><?php echo $this->insideText?>&nbsp;</div>
			</div>
			
			<?php
			echo ob_get_clean();
		}
	}
}

?>