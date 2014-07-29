<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class allows to determine which part of a text has been removed and added
*/
if (!class_exists("SLFramework_Textdiff")) {
	class SLFramework_Textdiff {
	
		var $text1 ;
		var $text2 ;
		
		var $text_diff ; 
		
		/** ====================================================================================================================================================
		* Constructor
		* 
		* @access private
		* @return void
		*/
		function SLFramework_Textdiff() {
			require_once( ABSPATH . WPINC . '/wp-diff.php' );
		}

		/** ====================================================================================================================================================
		* Identify the added part
		* 
		* @param string $text1 the reference text
		* @param string $text2 the text to compare with
		* @return void
		*/
		
		public function diff($text1, $text2) {
			$this->text1 = $text1 ;
			$this->text2 = $text2 ;
		
			$left_lines  = explode("\n", $this->text1);
			$right_lines = explode("\n", $this->text2);
			
			$this->text_diff = new Text_Diff($left_lines, $right_lines);
		}
		
		/**====================================================================================================================================================
		 * Displays a human readable HTML representation of ALL the text marked with  differences .
		 *
		 * @return string  HTML with differences.
		 */
		 
		public function show_all_with_difference() {
			$renderer = new Text_Diff_Renderer_inline() ; 
			$result = $renderer->render($this->text_diff) ; 
			
			$lignes = explode("\n", $result) ; 
			$del_continue = false ; 
			$ins_continue = false ; 
			
			$return = "<div class='diff_result'><ol class='numbering'>\n" ; 
			
			foreach ($lignes as $l) {
				$return .= "<li><pre> " ; 
				
				if ($del_continue)
					$return .= "<span class='diff_del'>" ; 
				if ($ins_continue)
					$return .= "<span class='diff_ins'>" ; 
				
				if (substr_count($l, '<del>')-substr_count($l, '</del>') > 0)  
					$del_continue = true ; 
				if (substr_count($l, '<del>')-substr_count($l, '</del>') < 0)  
					$del_continue = false ; 
				if (substr_count($l, '<ins>')-substr_count($l, '</ins>') > 0)  
					$ins_continue = true ; 
				if (substr_count($l, '<ins>')-substr_count($l, '</ins>') < 0)  
					$ins_continue = false ; 
					
				$l = str_replace("<del>", "<span class='diff_del'>", $l) ; 
				$l = str_replace("<ins>", "<span class='diff_ins'>", $l) ; 
				$l = str_replace("</ins>", "</span>", $l) ; 
				$l = str_replace("</del>", "</span>", $l) ; 
				
				$return .= $l ; 
				
				if ($del_continue)
					$return .= "</span>" ; 
				if ($ins_continue)
					$return .= "</span>" ; 
				
				$return .= "</pre></li>\n" ; 
			}
			
			$return .= "</ol></div>\n"  ;
			
			return $return ; 
		}
		
		/**====================================================================================================================================================
		 * Displays a simple human readable HTML representation of ALL the text marked with  differences .
		 *
		 * @return string  HTML with differences.
		 */
		 
		public function show_simple_difference() {
			$renderer = new Text_Diff_Renderer_inline() ; 
			$result = $renderer->render($this->text_diff) ; 
			
			$result = str_replace("<del>", "<span class='diff_del'>", $result) ; 
			$result = str_replace("<ins>", "<span class='diff_ins'>", $result) ; 
			$result = str_replace("</ins>", "</span>", $result) ; 
			$result = str_replace("</del>", "</span>", $result) ; 
			
			return $result ; 
		}

		/**====================================================================================================================================================
		 * Displays a human readable HTML representation of ONLY the difference between two texts.
		 *
		 * @return string  HTML with differences.
		 */
		 
		public function show_only_difference() {
			$renderer = new Text_Diff_Renderer_inline() ; 
			$result = $renderer->render($this->text_diff) ; 
			
			$lignes = explode("\n", $result) ; 
			$del_continue = false ; 
			$ins_continue = false ; 
			
			$first_trois_points = true ; 
			$next_points = "" ; 
			
			$buffer = array() ; 
			
			foreach ($lignes as $l) {
				$l_inti = $l ;
				$return = "" ; 
				if ($del_continue) 
					$return .= "<span class='diff_del'>" ; 
				if ($ins_continue)
					$return .= "<span class='diff_ins'>" ; 
				
				if (substr_count($l, '<del>')-substr_count($l, '</del>') > 0)  
					$del_continue = true ; 
				if (substr_count($l, '<del>')-substr_count($l, '</del>') < 0)  
					$del_continue = false ; 
				if (substr_count($l, '<ins>')-substr_count($l, '</ins>') > 0)  
					$ins_continue = true ; 
				if (substr_count($l, '<ins>')-substr_count($l, '</ins>') < 0)  
					$ins_continue = false ; 
					
				$l = str_replace("<del>", "<span class='diff_del'>", $l) ; 
				$l = str_replace("<ins>", "<span class='diff_ins'>", $l) ; 
				$l = str_replace("</ins>", "</span>", $l) ; 
				$l = str_replace("</del>", "</span>", $l) ; 
				
				$return .= $l ; 
				
				if ($del_continue)
					$return .= "</span>" ; 
				if ($ins_continue)
					$return .= "</span>" ; 
				
				if ($l_inti != $return) {
					$buffer[] = array(true, $return) ; 
				} else {
					$buffer[] = array(false, $return) ; 
				}
			}
			
			$return = "<div class='diff_result'><ol class='numbering'>\n" ; 
			$troispoint = false ; 
			for ($i=0; $i<count($buffer) ; $i++) {
				// If there is a modified line 3 before or 3 after this lines we print it ... other wise we print one sigle "..."
				$n1 = max(0, $i-1) ; 
				$n2 = max(0, $i-2) ; 
				$n3 = max(0, $i-3) ; 
				$n4 = $i ; 
				$n5 = min(count($buffer)-1, $i+1) ; 
				$n6 = min(count($buffer)-1, $i+2) ; 
				$n7 = min(count($buffer)-1, $i+3) ; 
				
				if (($buffer[$n1][0])||($buffer[$n2][0])||($buffer[$n3][0])||($buffer[$n4][0])||($buffer[$n5][0])||($buffer[$n6][0])||($buffer[$n7][0])) {
					$return .= $next_points ; 
					$next_points = "" ; 
					$return .= "<li class='numbering_li' value='".($i+1)."'><pre> ".$buffer[$i][1]."</pre></li>\n" ; 
					$troispoint = false ; 
				} else {
					if (!$troispoint) {
						if ($first_trois_points) {
							$return .= "</ol><pre> ...</pre>\n" ; 
							$next_points .="<ol class='numbering'>\n" ; 
							$troispoint = true ; 
							$first_trois_points = false ; 
						} else {
							$return .= "</ol><pre> ...</pre>\n" ; 
							
							$next_points .= "<hr class='diff_hr'/>\n" ; 
							$next_points .= "<pre> ...</pre>" ;
							$next_points .="<ol class='numbering'>\n" ; 
							$troispoint = true ; 
						}
					} 
				}
			}
			if ($next_points=="")
				$return .= "</ol></div>\n"  ;
			else 
				$return .="</div>\n" ; 
			
			return $return ; 
		}	
		
	}
}


?>