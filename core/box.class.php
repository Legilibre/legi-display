<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 
/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enables the creation of a box in the admin backend
*/
if (!class_exists("SLFramework_Box")) {
	class SLFramework_Box {
		
		var $title ; 
		var $content ; 
		
		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @param string $title the title of the box
		* @param string $content the HTML code of the content of the box
		* @return SLFramework_Box the box object
		*/
		
		function SLFramework_Box($title, $content) {
			$this->title = $title ; 
			$this->content = $content ; 
		}
		
		
		/** ====================================================================================================================================================
		* Print the box HTML code. 
		* 
		* @return void
		*/
		function flush()  {
			ob_start();
			?>
			<div class="metabox-holder" style="width: 100%">
				<div class="meta-box-sortables">
					<div class="postbox">
						<h3 class="hndle"><span><?php echo $this->title ; ?></span></h3>
						<div class="inside" style="padding: 5px 10px 5px 20px;">
							<?php 
								echo $this->content ; 
							?>
						</div>
					</div>
					
				</div>
			</div>
			<?php
			return ob_get_clean();
		}
	}
}

?>