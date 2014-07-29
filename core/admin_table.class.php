<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enables the creation of tables in the admin backend
*/


if (!class_exists("SLFramework_Table")) {
	$SLframework_id_table = 0 ; 
	
	class SLFramework_Table  {
		var $nbCol ; 
		var $nbLigneTotal ; 
		var $nbLignePerPage ; 
		var $title ; 
		var $order ; 
		var $hasFooter ; 
		var $content ; 
		var $id ; 
		var $searchWords ; 
		
		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @param integer $nb_all_Items the number of all items. If the number of submitted lines are less than this number, a navigation bar will be added at the top of the table
		* @param integer $nb_max_per_page the number of item per page. This parameter is useful if you have submitted the previous parameter.
		* @param boolean $order allow the ordering of the columns with small arrows 
		* @return SLFramework_Table the table
		*/
		
		function SLFramework_Table($nb_all_Items=0, $nb_max_per_page=0, $order=false, $search=false) {	
			global $SLframework_id_table ; 
			
			$SLframework_id_table ++ ; 
			
			$this->id = $SLframework_id_table ; 
			$this->title = array() ; 
			$this->order = $order ; 
			$this->nbLigneTotal = $nb_all_Items ; 
			$this->nbLignePerPage = $nb_max_per_page ; 
			$this->hasFooter = true ; 
			$this->search = $search ; 
			$this->searchWords = "" ; 
			$this->content = array() ; 
		}
		
		/** ====================================================================================================================================================
		* Set the titles of the columns
		* 
		* @param array $array it is an array of string which is of the size of the number of columns. Each string is the title for a different column
		* @return void
		*/

		function title($array) {
			$this->title = $array ; 
		}
		
		/** ====================================================================================================================================================
		* Get the current page of the table.
		* This is relevant if the number of your items is greater than the number of lines
		* 
		* @return integer the page number
		*/
		function current_page() {
			if (isset($_GET['paged_'.$this->id])) {
				$page_cur = preg_replace("/[^0-9]/", "", $_GET['paged_'.$this->id]) ; 
			} else {
				$page_cur = 1 ; 
			}
			return $page_cur ; 
		}
		
		/** ====================================================================================================================================================
		* Set the number of items (all).
		* 
		* @return void
		*/
		function set_nb_all_Items($nb) {
			$this->nbLigneTotal = $nb ; 
		}
		
		/** ====================================================================================================================================================
		* Get the currentfilter of the table.
		* 
		* @return string the filter
		*/
		function current_filter() {
			if (isset($_GET['filter_'.$this->id])) {
				$page_filter = trim(preg_replace("#(\xBB|\xAB|!|\xA1|%|,|:|;|\(|\)|\&|\"|\'|\.|-|\/|\?|\\\)#", " ", $_GET['filter_'.$this->id])) ; 
				while ($page_filter != str_replace("  ", " ", $page_filter)) {
					$page_filter = str_replace("  ", " ", $page_filter) ; 
				}
			} else {
				$page_filter = "" ; 
			}
			return $page_filter ; 
		}
		/** ====================================================================================================================================================
		* Get the current column order of the table.
		* 
		* @return integer the column number
		*/
		function current_ordercolumn() {
			if (isset($_GET['ordercol_'.$this->id])) {
				$col_cur = preg_replace("/[^0-9]/", "", $_GET['ordercol_'.$this->id]) ; 
			} else {
				$col_cur = 1 ; 
			}
			return $col_cur ; 
		}
		
		/** ====================================================================================================================================================
		* Get the current column direction of the table.
		* 
		* @return string the column direction "ASC" or "DESC"
		*/
		function current_orderdir() {
			if (isset($_GET['orderdir_'.$this->id])) {
				$dir_cur = $_GET['orderdir_'.$this->id] ; 
				if ($dir_cur != "DESC") {
					$dir_cur = "ASC" ; 
				}
			} else {
				$dir_cur = "ASC" ; 
			}
			return $dir_cur ; 
		}
		
		/** ====================================================================================================================================================
		* Remove the showed title at the footer of the table
		* By default, titles of the columns are displayed at the top of the table and at its footer.
		* 
		* @return void
		*/
		function removeFooter() {
			$this->hasFooter = false ; 
		}
		
		/** ====================================================================================================================================================
		* Add a line in your table
		* For instance
		* <code>$table = new SLFramework_Table() ; <br/> $table->title(array("Col1", "Col2", "Col3") ) ; <br/> $cel1 = new adminCell("Cel1-1") ; <br/> $cel2 = new adminCell("Cel1-2") ; <br/> $cel3 = new adminCell("Cel1-3") ; <br/> $table->add_line(array($cel1, $cel2, $cel3), '1') ; <br/> echo $table->flush() ; </code>
		* This code will display a table with a unique line
		* 
		* @param array $array it is an array of adminCell object. The length of this array is the same size of the number of your columns
		* @param id $id it is the id of this line. It is useful when you add an action on a cell
		* @see adminCell::adminCell
		* @see adminCell:add_action
		* @return void
		*/
		function add_line($array, $id) {
			$n = 1 ; 
			foreach ($array as $a) {
				$a->idLigne= $id ;
				$a->idCol = $n ; 
				$n++ ; 
			}
			$this->content[] = $array ; 
		}
		
		/** ====================================================================================================================================================
		* Return the table HTML code. You just have to echo it
		* 
		* @return string the HTML code of the table
		*/
		function flush() {
			ob_start() ; 
			$get = $_GET;
			
			//
			// Est-ce que on affiche la zone de recherche
			//
			if ($this->search) {
				
					
					$filter = $this->current_filter() ; 
					
?>					<form id="posts-filterwords" action="<?php echo $_SERVER['PHP_SELF'] ;?>" method="get">
						<div class="tablenav top">
							<div class="tablenav-pages">
<?php
								// Variable cachee pour reconstruire completement l'URL de la page courante
								foreach ($get as $k => $v) {
									if (($k!="filter_".$this->id)&&($k!="paged_".$this->id)) {
?>										<input name="<?php echo $k;?>" value="<?php echo $v;?>" type="hidden"/>
<?php    							}
								}
?>								<input name="paged_<?php echo $this->id ; ?>" value="1" type="hidden"/>
								<span class="paging-input"><?php echo sprintf(__("Filter: %s", "SL_framework"), "<input type='text' name='filter_".$this->id."' value=\"".$filter."\" size='30'>") ?></span>
								<br class="clear">
							</div>
						</div>
					</form>
<?php			
			}

			//
			// Est-ce que on affiche le raccourci pour se deplacer dans les entrees du tableau
			//
			if ($this->nbLigneTotal>count($this->content)) {
				$page_cur = $this->current_page() ; 
				
				$page_tot = ceil($this->nbLigneTotal/$this->nbLignePerPage) ; 
			
				$page_inf = max(1,$page_cur-1) ; 
				$page_sup= min($page_tot,$page_cur+1) ; 
				
?>					<form id="posts-filter" action="<?php echo $_SERVER['PHP_SELF'] ;?>" method="get">
						<div class="tablenav top">
							<div class="tablenav-pages">
<?php
								// Variable cachee pour reconstruire completement l'URL de la page courante
								foreach ($get as $k => $v) {
									if ($k!="paged_".$this->id) {
?>										<input name="<?php echo $k;?>" value="<?php echo $v;?>" type="hidden"/>
<?php    								}
								}
?>								<span class="displaying-num"><?php echo $this->nbLigneTotal ; ?> items</span>
								<a class="first-page<?php if ($page_cur == 1) {echo  ' disabled' ; } ?>" <?php if ($page_cur == 1) {echo  'onclick="javascript:return false;" ' ; } ?>title="Go to the first page" href="<?php echo add_query_arg('table_id', $this->id, add_query_arg( 'paged_'.$this->id, '1' ));?>">&laquo;</a>
								<a class="prev-page<?php if ($page_cur == 1) {echo  ' disabled' ; } ?>" <?php if ($page_cur == 1) {echo  'onclick="javascript:return false;" ' ; } ?>title="Go to the previous page" href="<?php echo add_query_arg('table_id', $this->id, add_query_arg( 'paged_'.$this->id, $page_inf ));?>">&lsaquo;</a>
								<span class="paging-input"><?php echo sprintf(__("%s of %s", "SL_framework"), "<input class='current-page' title='".__('Current Page', 'SL_framework')."' name='paged_".$this->id."' value='".$page_cur."' size='1' type='text'>", "<span class='total-pages'>".$page_tot."</span>") ?></span>
								<a class="next-page<?php if ($page_cur == $page_tot) {echo  ' disabled' ; } ?>" <?php if ($page_cur == $page_tot) {echo  'onclick="javascript:return false;" ' ; } ?>title="Go to the next page" href="<?php echo add_query_arg('table_id', $this->id, add_query_arg( 'paged_'.$this->id, $page_sup ));?>">&rsaquo;</a>
								<a class="last-page<?php if ($page_cur == $page_tot) {echo  ' disabled' ; } ?>" <?php if ($page_cur == $page_tot) {echo  'onclick="javascript:return false;" ' ; } ?>title="Go to the last page" href="<?php echo add_query_arg('table_id', $this->id, add_query_arg( 'paged_'.$this->id, $page_tot ));?>">&raquo;</a>			
								<br class="clear">
							</div>
						</div>
					</form>
<?php
			}
			//
			// Affichage du debut du tableau
			//
?>					<table class="widefat fixed" cellspacing="0">
						<thead>
							<tr>
								<tr>
<?php
			$i_col = 1 ; 
			foreach ($this->title as $name) {
				if ($this->order) {
					if ($this->current_ordercolumn()==$i_col) {
						$name .= "&nbsp;" ; 
						if  ($this->current_orderdir()=="DESC") {
							$name .= "<a href='".add_query_arg( array ( 'orderdir_'.$this->id => 'ASC', 'ordercol_'.$this->id  => $i_col ) )."'><img src='".plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."img/arrow_up.png' style='border:0px; vertical-align:middle;'></a>" ; 
							$name .= "<img src='".plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."img/arrow_down_s.png' style='border:0px; vertical-align:middle;'>" ; 							
						} else {
							$name .= "<img src='".plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."img/arrow_up_s.png' style='border:0px; vertical-align:middle;'>" ; 
							$name .= "<a href='".add_query_arg( array ( 'orderdir_'.$this->id => 'DESC', 'ordercol_'.$this->id  => $i_col ) )."'><img src='".plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."img/arrow_down.png' style='border:0px; vertical-align:middle;'></a>" ; 						
						}
					} else {
						$name .= "&nbsp;" ; 
						$name .= "<a href='".add_query_arg( array ( 'orderdir_'.$this->id => 'ASC', 'ordercol_'.$this->id  => $i_col ) )."'><img src='".plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."img/arrow_up.png' style='border:0px; vertical-align:middle;'></a>" ; 
						$name .= "<a href='".add_query_arg( array ( 'orderdir_'.$this->id => 'DESC', 'ordercol_'.$this->id  => $i_col ) )."'><img src='".plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."img/arrow_down.png' style='border:0px; vertical-align:middle;'></a>" ; 
					}
				}
				$i_col++ ; 
?>									<th class="manage-column column-columnname" scope="col"><?php echo $name ; ?></th>
<?php
			}
?>								</tr>
							</tr>
						</thead>
<?php
			//
			// Affichage de la fin du tableau
			//
			if ($this->hasFooter) {			
?>						<tfoot>
							<tr>
								<tr>
<?php
				foreach ($this->title as $name) {
?>									<th class="manage-column column-columnname" scope="col"><?php echo $name ; ?></th>
<?php
				}
?>								</tr>
							</tr>
						</tfoot>
<?php			
			}
			//
			// Affichage des lignes
			//
?>						<tbody>
<?php
			$ligne = 0 ; 
			foreach ($this->content as $line) {
				$ligne++ ; 
				// on recupere le premier id de la ligne et on considere que c'est le meme partout
				$id = $line[0]->idLigne ; 
?>							<tr class="<?php if ($ligne%2==1) {echo  'alternate' ; } ?>" valign="top" id="ligne<?php echo $id ; ?>"> 
<?php
				foreach ($line as $cellule) {
					$cellule->flush() ; 
				}
?>							</tr> 
<?php

			}
?>						</tbody>
					</table>
<?php
			$return = ob_get_clean() ; 
			return $return ; 
		}
	} 
}

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class create cells to be used with the SLFramework_Table::add_line method
*/
if (!class_exists("adminCell")) {
	class adminCell  {
		var $content ; 
		var $action ; 
		var $idLigne ;
		var $idCol ;
		
		/** ====================================================================================================================================================
		* Create the cells object
		* 
		* @param string $content the HTML code to be displayed in the cell
		* @return adminCell the object
		*/
		function adminCell($content) {
			$this->content = $content ; 
			$this->action = array() ;
		}
		
		/** ====================================================================================================================================================
		* To add a javascript action on this cell.
		* An action a small link at the bottom of the cell which call a javascript action when it is clicked
		* For instance :  
		* <code>$cel1 = new adminCell("content cell") ; <br/ > $cel1->add_action("Delete", "deleteFunction") ; </code>
		*with the following javascript code in the js/js_admin.js file to call a PHP function (deletePHP) in AJAX
		*<code>function deleteFunction (element) { <br/>&nbsp; &nbsp; &nbsp;// Get the id of the line <br/>&nbsp; &nbsp; &nbsp;var idLine = element.getAttribute("id"); <br/>&nbsp; &nbsp; &nbsp;// Prepare the argument for the AJAX call <br/>&nbsp; &nbsp; &nbsp;var arguments = { <br/>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;action: 'deletePHP',  <br/>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;id : idLine <br/>&nbsp; &nbsp; &nbsp;}  <br/>&nbsp; &nbsp; &nbsp;//POST the data  <br/>&nbsp; &nbsp; &nbsp;jQuery.post(ajaxurl, arguments, function(response) { <br/>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;// The call is finished <br/>&nbsp; &nbsp; &nbsp;});  <br/>}</code>
		* and do not forget to add a <code>add_action('wp_ajax_deletePHP', array($this,'deletePHP'));</code> in the <code>_init</code> function of your plugin
		* If the function is only a string with no parantehsis (i.e. <code>the_function</code>), thus the id of the line will be passed in argument
		* If the function is a function name with arguments (i.e. <code>the_function(arg1, arg2)</code>), thus this function will be called directly
		*
		* @param string $name the text of the link to be displayed
		* @param string $javascript_function the name of the function to be called when the link is clicked
		* @return adminCell the cell object
		*/

		function add_action($name, $javascript_function) {
			$this->action[] = array($name, $javascript_function) ;
		}
		
		/** ====================================================================================================================================================
		* Print the cell HTML code. 
		* This function is not to be called from the plugin. It is called in the table class
		* 
		* @access private
		* @return void
		*/
		function flush() {
		
?>								<td class="column-columnname">
									<span id="cell_<?php echo $this->idLigne ?>_<?php echo $this->idCol ?>" ><?php  echo $this->content ?></span>
<?php
			if (! empty($this->action)) {
?>									<div class="row-actions">
<?php		
				$num = 0 ; 
				foreach ($this->action as $l) {
					$num ++ ;
					if (strpos($l[1],"(")>0) {
						$l[1] = str_replace('"', '\'', $l[1]) ; 
?>										<span><a href="#" onclick="javascript: jQuery('#wait_<?php echo SLFramework_Utils::create_identifier($l[1]) ;?>_<?php echo $this->idLigne ;?>').show() ; jQuery('body').bind('DOMSubtreeModified',function() {jQuery('#wait_<?php echo SLFramework_Utils::create_identifier($l[1]) ;?>_<?php echo $this->idLigne ;?>').hide() ; })  ; <?php echo $l[1] ;?> ; return false ; " id="<?php echo SLFramework_Utils::create_identifier($l[1]) ;?>_<?php echo $this->idLigne ;?>"><?php echo $l[0] ;?></a><img id='wait_<?php echo SLFramework_Utils::create_identifier($l[1]) ;?>_<?php echo $this->idLigne ;?>' src='<?php echo plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__)); ?>img/ajax-loader.gif' style='display:none;'><?php if ($num!=count($this->action)) { echo " |" ; }?></span>
<?php					
					} else {
					
?>										<span><a href="#" onclick="javascript: jQuery('#wait_<?php echo SLFramework_Utils::create_identifier($l[1]) ;?>_<?php echo $this->idLigne ;?>').show() ; jQuery('body').bind('DOMSubtreeModified',function() {jQuery('#wait_<?php echo SLFramework_Utils::create_identifier($l[1]) ;?>_<?php echo $this->idLigne ;?>').hide() ; }) ; <?php echo $l[1] ;?>(<?php echo $this->idLigne ; ?>) ; return false ; " id="<?php echo $l[1] ;?>_<?php echo $this->idLigne ;?>"><?php echo $l[0] ;?></a><img id='wait_<?php echo SLFramework_Utils::create_identifier($l[1]) ;?>_<?php echo $this->idLigne ;?>' src='<?php echo plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__)); ?>img/ajax-loader.gif' style='display:none;'><?php if ($num!=count($this->action)) { echo " |" ; }?></span>
<?php				}
				}
?>									</div>
<?php
			}
?>								</td>
<?php
		}
	}
}

?>