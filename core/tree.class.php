<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class allows to display hierachical list in a smart way
*/
if (!class_exists("SLFramework_Treelist")) {

	class SLFramework_Treelist {
				
		/** ====================================================================================================================================================
		* Constructor
		* 
		* @access private
		* @return void
		*/
		function SLFramework_Treelist() {
		}
		
		/** ====================================================================================================================================================
		* Display the tree list according to the array given
		* 
		* For instance : 
		* <code>$array = array(<br/>      array('first element', null),<br/>      array('second element', array(<br/>            array('sub1', null),<br/>            array('sub2', null)<br/>      )),<br/>      array('third element', null)<br/>) ;</code>
		* <code>SLFramework_Treelist::render($array) ; </code>
		* if the array have 1 elements i.e. array('title element') the first element is the title (no children)
		* if the array have 2 elements i.e. array('title element', "E45AF"), the second element will be considered as the id of the array
		* if the array have 3 elements i.e. array('title element', "E45AF", null) the third element is the children (array or null)
		* if the array have 4 elements i.e. array('title element', "E45AF", null, false), the fourth element will be considered as an indication whether the node is to be expanded or not (by default it is true)
		* 
		* @param array $array list to display, each item of the list is an array : array('title', null) if the node is the last one in the tree or array(title, array(...)) if there is other son nodes. 
		* @param boolean $reduce_develop to enable the reduction and the expansion of the tree with javascript
		* @param string $reorder_callback if you want to make the tree (nested list) sortable, please indicate what is the ajax callback function to be called upon sort. This function will receive the array in the $_POST['result'] variable. This function should return "OK". Otherwise, the return message will be 'alerted'
		* @param string $classPerso a CSS custom class to customize the apparence of the tree
		* @return void
		*/
		
		static function render($array, $reduce_develop=false, $reorder_callback=null, $classPerso=""){
			$rand = rand(1, 10000000) ; 
			?>
			<script type="text/javascript">
				function stopPropag<?php echo $rand ?>(event) {
					event.stopPropagation();
					return false ; 
				}

				function folderToggle<?php echo $rand ?>(event, element) {
					element.parent().toggleClass("minus_folder plus_folder")
					event.stopPropagation();
					jQuery.fn.fadeThenSlideToggle = function(speed, easing, callback) {
						if (this.is(":hidden")) {
							return this.slideDown(speed, easing).fadeTo(speed, 1, easing, callback);
						} else {
							return this.fadeTo(speed, 0, easing).slideUp(speed, easing, callback);
						}
					};
					
					element.fadeThenSlideToggle(500);
					
					return false ; 
				}
			</script>
			<?php
			echo "<div id='sortableTreeView".$rand."' class='".$classPerso."'>" ; 
				SLFramework_Treelist::render_sub($array,$reduce_develop, false, $rand) ; 
			echo "</div>" ; 
			
			if ($reorder_callback!=null) {
				?>
				<script>

				jQuery(document).ready(function() {
					// Initialize the sortable
					jQuery('#sortableTreeView<?php echo $rand ?> ul').sortable({
						handle: 'div', 
						items: 'li',
						opacity: .6,
						cursorAt: { left: 0 },
						cursor: 'crosshair',
						connectWith: "#sortableTreeView<?php echo $rand ?> ul",
						placeholder: "highlight_placeholder", 
						sort: function(event, ui) {
							parentPlaceholder = jQuery(ui.placeholder).parent().parent();
							previousPlaceholderChildren = jQuery(ui.placeholder).prev().children("ul:first");
							
							// If the item is moved to the left, send it to its parent level
							if (jQuery(ui.placeholder).offset().left>jQuery(ui.helper).offset().left) {
								// We add the new placeholder
								parentPlaceholder.after(jQuery(ui.placeholder));
							}
							// If the item is moved to the right, send it to its previous level children
							if ((previousPlaceholderChildren.offset()!=null) && (jQuery(ui.helper).offset().left>previousPlaceholderChildren.offset().left)) {
								// We add the new placeholder
								previousPlaceholderChildren.append(jQuery(ui.placeholder));
							}						
						},
						start: function(event, ui) {
							jQuery(ui.placeholder).css({height: jQuery(ui.helper).height()});
							jQuery(ui.placeholder).css({width: jQuery(ui.helper).width()});
							// Remove every children
							jQuery(ui.placeholder).empty() ; 
							// Clone 
							cloneChild = jQuery(ui.helper).clone() ; 
							cloneChildWithoutFirstLi = jQuery("<span></span>") ; 
							cloneChild.children().each(function() {    
   								cloneChildWithoutFirstLi.append(jQuery(this));
							});

							jQuery(ui.placeholder).append(cloneChildWithoutFirstLi) ; 
            			},
						update: function(event, ui) {
							if (ui.sender == null){
								arrayResult = toHierarchy(jQuery('#sortableTreeView<?php echo $rand ?> ul')) ; 
								
								jQuery(ui.item).children('div').addClass('loading');
								var arguments = {
									action: '<?php echo $reorder_callback ?>', 
									result : arrayResult
								} 
								
								//POST the data and append the results to the results div
								jQuery.post(ajaxurl, arguments, function(response) {
									jQuery(ui.item).children('div').removeClass('loading');
									if (response!='OK') {
										alert(response) ; 
									}
								});

							}
						}
					});
				}) ;
				
				function toHierarchy(element) {
					var ret = Array() ;
					jQuery(element).children('li').not('li li').each(function () {
						var level = _recursiveItems(jQuery(this));
						ret.push(level);
					});
		
					return ret;
		
					function _recursiveItems(li) {
						var id = jQuery(li).attr('id') ; 
						var child = null ;
						if (jQuery(li).children('ul').children('li').length > 0) {
							child = Array();
							jQuery(li).children('ul').children('li').each(function() {
								var level = _recursiveItems(jQuery(this));
								child.push(level);
							});
						}
						item = Array(id, child) ; 
						return item;
					}
				}
				</script>
				<?php
			}
	
		}
		
		/** ====================================================================================================================================================
		* Same as the render function but avoid printing the javascript
		* 
		* @access private
		* @param array $array list to display, each item of the list is an array : array('title', null) if the node is the last one in the tree or array(title, array(...)) if there is other son nodes. 
		* @param boolean $reduce_develop to enable the reduction and the expansion of the tree with javascript
		* @return void
		*/
		
		static function render_sub($array, $reduce_develop=false, $hide=false, $rand=""){
			$hidden = "" ; 
			if ($hide) {
				$hidden = " style='display: none;' " ; 
			}
			echo "<ul class='tree'".$hidden.">" ; 
			foreach ($array as $item) {
				$id = "" ; 
				$next_hide = false ; 
				$children = null ; 
				$plus_minus = "class='minus_folder'" ; 
				
				if (isset($item[1])) {
					$id = " id='".$item[1]."' " ; 
				}
				if (isset($item[2])) {
					$children = $item[2] ; 
				}
				if (isset($item[3])) {
					if (!$item[3]) {
						$next_hide=true ; 
						$plus_minus = "class='plus_folder'" ; 
					}
				}
				$toggle="" ; 
				if ($reduce_develop && ($children!=null)) {
					$toggle = " onclick='folderToggle".$rand."(event, jQuery(this).find(\"ul:first\"));' ".$plus_minus." " ; 
				} else if ($reduce_develop) {
					$toggle = " onclick='stopPropag".$rand."(event);' " ; 
				}
				// We replace the link in the text by a stopPropag to avoid closing the hierarchy when clicking on links
				
				if ($children!=null) {
					$item[0] = str_replace("<a ", "<a onclick='stopPropag".$rand."(event);' ", $item[0]) ; 
				}
				
				echo "<li".$id."".$toggle.">"."<div>".$item[0]."</div>" ; 
				if ($children!=null) {
					SLFramework_Treelist::render_sub($children, $reduce_develop, $next_hide, $rand) ; 
				} else {
					echo "<ul></ul>" ; 
				}
				echo "</li>" ; 
			}
			echo "</ul>" ; 
		}
	}
}

?>