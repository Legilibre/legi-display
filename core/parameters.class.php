<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 
/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enable the creation of form to manage the parameter of your plugin 
*/
if (!class_exists("SLFramework_Parameters")) {
	class SLFramework_Parameters {
		
		var $output ; 
		var $maj ; 
		var $modified ; 
		var $warning ; 
		var $error ; 
		var $hastobeclosed ;
		var $obj ; 
		
		/** ====================================================================================================================================================
		* Constructor of the object
		* 
		* @see  pluginSedLex::get_param
		* @see  pluginSedLex::set_param
		* @param class $obj a reference to the object containing the parameter (usually, you need to provide "$this"). If it is "new rootSLframework()", it means that it is the framework parameters.
		* @param string $tab if you want to activate a tabulation after the submission of the form
		* @return SLFramework_Parameters the form class to manage parameter/options of your plugin
		*/
		
		function SLFramework_Parameters($obj, $tab="") {
			$this->buffer = array() ; 
			$this->obj = $obj ; 
		}
		
		/** ====================================================================================================================================================
		* Add title in the form
		* 
		* @param string $title the title to add
		* @return void
		*/
		function add_title($title)  {
			$this->buffer[] = array('title', $title) ; 
		}
		
		/** ====================================================================================================================================================
		* Add title macroblock (i.e. that can be duplicate a plurality of time if the user needs it) in the form
		* 
		* @param string $title the title to add
		* @return void
		*/
		function add_title_macroblock($title, $lien="")  {
			if ($lien=="") {
				$lien = __("Add new", "SL_framework") ; 
			}
			$this->buffer[] = array('title_macro', $title, $lien) ; 
		}


		/** ====================================================================================================================================================
		* Add a comment in the form
		* 
		* @param string $comment the comment to add 
		* @return void
		*/
		function add_comment($comment)  {
			$this->buffer[] = array('comment', $comment) ; 
		}
		
		/** ====================================================================================================================================================
		* Add a comment in the form which display the default value of the param
		* 
		* @param string $comment the comment to add 
		* @return void
		*/
		function add_comment_default_value($param)  {
			$comment = $this->obj->get_default_option($param) ; 
			$comment = str_replace("\r", "", $comment) ; 
			$comment = str_replace("<", "&lt;", $comment) ; 
			$comment = str_replace(">", "&gt;", $comment) ; 
			if (strpos($comment, "*")===0)
				$comment = substr($comment, 1) ; 
			$comment = str_replace(" ", "&nbsp;", $comment) ; 
			$comment = str_replace("\n", "<br>", $comment) ; 
			$this->buffer[] = array('comment', "<code>$comment</code>") ; 
		}


		/** ====================================================================================================================================================
		* Add a textarea, input, checkbox, etc. in the form to enable the modification of parameter of the plugin
		* 	
		* Please note that the default value of the parameter (defined in the  <code>get_default_option</code> function) will define the type of input form. If the default  value is a: <br/>&nbsp; &nbsp; &nbsp; - string, the input form will be an input text <br/>&nbsp; &nbsp; &nbsp; - integer, the input form will be an input text accepting only integer <br/>&nbsp; &nbsp; &nbsp; - string beggining with a '*', the input form will be a textarea <br/>&nbsp; &nbsp; &nbsp; - string equals to '[file]$path', the input form will be a file input and the file will be stored at $path (relative to the upload folder)<br/>&nbsp; &nbsp; &nbsp; - string equals to '[password]$password', the input form will be a password input ; <br/>&nbsp; &nbsp; &nbsp; - string equals to '[page]$page', the input form will be a dropdown list with a list of the pages ; <br/>&nbsp; &nbsp; &nbsp; - string equals to '[media]$media', the input form will be a element in the media library ; <br/>&nbsp; &nbsp; &nbsp; - array of string, the input form will be a dropdown list<br/>&nbsp; &nbsp; &nbsp; - boolean, the input form will be a checkbox 
		*
		* @param string $param the name of the parameter/option as defined in your plugin and especially in the <code>get_default_option</code> of your plugin
		* @param string $name the displayed name of the parameter in the form
		* @param string $forbid regexp which will delete some characters in the submitted string (only a warning is raised) : For instance <code>$forbid = "/[^a-zA-Z0-9]/"</code> will remove all the non alphanumeric value
		* @param string $allow regexp which will verify that the submitted string will respect this rexexp, if not, the submitted value is not saved  and an erreor is raised : For instance, <code>$allow = "/^[a-zA-Z]/"</code> require that the submitted string begin with a nalpha character
		* @param array $related a list of the other params that will be actived/deactivated when this parameter is set to true/false (thus, this param should be a boolean)
		* @return void
		*/

		function add_param($param, $name, $forbid="", $allow="", $related=array())  {
			$this->buffer[] = array('param', $param, $name, $forbid, $allow, $related) ; 
		}
		
		/** ====================================================================================================================================================
		* Get the new value of the parameter after its update (with happen upon calling <code>flush</code>)
		*
		* @param string $param the name of the parameter/option as defined in your plugin and especially in the <code>get_default_option</code> of your plugin
		* @return mixed the value of the param (array if there is an error or null if there is no new value)
		*/

		function get_new_value($param)  {
			global $_POST ; 
			global $_FILES ; 
			
			// We reset the value to default
			//---------------------------------------

			if (isset($_POST['resetOptions'])) {
				$value = $this->obj->get_default_option($param) ;
				if ((is_string($value))&&(strpos($value, "*")===0))
					$value = substr($value, 1) ; 
				return  $value ; 
			}

			// We find the correspondance in the array to find the allow and forbid tag
			//---------------------------------------
			
			$name = "" ; 
			$forbid = "" ; 
			$allow = "" ; 
			$related = "" ; 
			for($iii=0; $iii<count($this->buffer); $iii++) {
				$ligne = $this->buffer[$iii] ; 
				if ($ligne[0]=="param") {	
					if ($param == $ligne[1]) { 
						$name = $ligne[2] ; 
						$forbid = $ligne[3] ; 
						$allow = $ligne[4] ; 
						$related = $ligne[5] ; 
						break;
					}
				}
			}
			
			// What is the type of the parameter ?
			//---------------------------------------
			
			$type = "string" ; 
			if (is_bool($this->obj->get_default_option($param))) $type = "boolean" ; 
			if (is_int($this->obj->get_default_option($param))) $type = "int" ; 
			if (is_array($this->obj->get_default_option($param))) $type = "list" ; 
			// C'est un text si dans le texte par defaut, il y a une etoile
			if (is_string($this->obj->get_default_option($param))) {
				if (strpos($this->obj->get_default_option($param), "*") === 0) $type = "text" ; 
			}
			// C'est un file si dans le texte par defaut est egal a [file]
			if (is_string($this->obj->get_default_option($param))) {
				if (str_replace("[file]","",$this->obj->get_default_option($param)) != $this->obj->get_default_option($param)) $type = "file" ; 
			}
			// C'est un password si dans le texte par defaut est egal a [password]
			if (is_string($this->obj->get_default_option($param))) {
				if (str_replace("[password]","",$this->obj->get_default_option($param)) != $this->obj->get_default_option($param)) $type = "password" ; 
			}
			// C'est un media si dans le texte par defaut est egal a [media]
			if (is_string($this->obj->get_default_option($param))) {
				if (str_replace("[media]","",$this->obj->get_default_option($param)) != $this->obj->get_default_option($param)) $type = "media" ; 
			}
			// C'est un media si dans le texte par defaut est egal a [media]
			if (is_string($this->obj->get_default_option($param))) {
				if (str_replace("[page]","",$this->obj->get_default_option($param)) != $this->obj->get_default_option($param)) $type = "page" ; 
			}
			
			// We format the param
			//---------------------------------------

			$problem_e = "" ; 
			$problem_w = "" ; 
						
			if (isset($_POST['submitOptions'])) {
								
				// Is it a boolean ?
				
				if ($type=="boolean") {
					if (isset($_POST[$param])) {
						if ($_POST[$param]) {
							return true ; 
						} else {
							return false ; 
						}
					} else {
						if (isset($_POST[$param."_workaround"])) {
							return false ;
						} else {
							return $this->obj->get_default_option($param) ; 
						}
					}
				}
				
				// Is it an integer ?
				
				if ($type=="int") {
					if (isset($_POST[$param])) {
						if (SLFramework_Utils::is_really_int($_POST[$param])) {
							return (int)$_POST[$param] ; 
						} else {
							if ($_POST[$param]=="") {
								return 0 ; 
							} else {
								return array("error", "<p>".__('Error: the submitted value is not an integer and thus, the parameter has not been updated!', 'SL_framework')."</p>\n") ; 
							}
						}
					} else {
						return $this->obj->get_default_option($param) ; 
					}
				}
				
				// Is it a string ?
				
				if (($type=="string")||($type=="text")||($type=="password")) {
					if (isset($_POST[$param])) {
						$tmp = $_POST[$param] ; 
						if ($forbid!="") {
							$tmp = preg_replace($forbid, '', $_POST[$param]) ; 
						}
						if (($allow!="")&&(!preg_match($allow, $_POST[$param]))) {
							return array("error","<p>".__('Error: the submitted string does not match the constrains', 'SL_framework')." (".$allow.")!</p>\n") ; 
						} else {
							return stripslashes($tmp) ; 
						}
					} else {
						if ($type=="text") {
							if (substr($this->obj->get_default_option($param), 0,1)) {
								return substr($this->obj->get_default_option($param), 1) ;
							}
						}
						return $this->obj->get_default_option($param) ;
					}
				} 
				
				// Is it a list ?
				
				if ($type=="list") {
					if (isset($_POST[$param])) {
						$selected = $_POST[$param] ; 
						$array = $this->obj->get_param($param) ; 
						$mod = false ; 
						for ($i=0 ; $i<count($array) ; $i++) {
							// if the array is a simple array of string
							if (!is_array($array[$i])) {
								if ( (is_string($array[$i])) && (substr($array[$i], 0, 1)=="*") ) {
									$array[$i] = substr($array[$i], 1) ; 
								} 
								// On met une etoile si c'est celui qui est selectionne par defaut
								if ($selected == SLFramework_Utils::create_identifier($array[$i])) {
									$array[$i] = '*'.$array[$i] ; 
								}
							} else {
								if ( (is_string($array[$i][0])) && (substr($array[$i][0], 0, 1)=="*") ) {
									$array[$i][0] = substr($array[$i][0], 1) ; 
								} 
								// On met une etoile si c'est celui qui est selectionne par defaut
								if ($selected == $array[$i][1]) { // The second is the identifier
									$array[$i][0] = '*'.$array[$i][0] ; 
								}
							}
						}
						return $array ; 
					} else {
						return $this->obj->get_default_option($param) ; 
					}
				} 
				
				// is it a media
				if ($type=="media") {
					if (isset($_POST[$param])) {
						return $_POST[$param] ; 
					} else {
						return str_replace("[media]","", $this->obj->get_default_option($param)) ; 
					}				
				}
				
				// is it a page
				if ($type=="page") {
					if (isset($_POST[$param])) {
						return $_POST[$param] ; 
					} else {
						return str_replace("[page]","", $this->obj->get_default_option($param)) ; 
					}				
				}
				
				// Is it a file ?
				if ($type=="file") {
					// deleted ?
					$upload_dir = wp_upload_dir();
					if (isset($_POST["delete_".$param])) {
						$deleted = $_POST["delete_".$param] ; 
						if ($deleted=="1") {
							if (file_exists($upload_dir["basedir"].$this->obj->get_param($param))){
								@unlink($upload_dir["basedir"].$this->obj->get_param($param)) ; 
							}
							return $this->obj->get_default_option($param) ; 
						}
					}
						
					if (isset($_FILES[$param])) {
						$tmp = $_FILES[$param]['tmp_name'] ;
						if ($tmp != "") {
							if ($_FILES[$param]["error"] > 0) {
								return 	array("error", "<p>".__('Error: the submitted file can not be uploaded!', 'SL_framework')."</p>\n") ; 
							} else {
								$upload_dir = wp_upload_dir();
								$path = $upload_dir["basedir"].str_replace("[file]","", $this->obj->get_default_option($param)) ; 
									
								if (is_uploaded_file($_FILES[$param]['tmp_name'])) {
									if (!is_dir($path)) {
										@mkdir($path, 0777, true) ; 
									}
									if (file_exists($path . $_FILES[$param]["name"])) {
										@unlink($path . $_FILES[$param]["name"]) ; 
									} 
									move_uploaded_file($_FILES[$param]["tmp_name"], $path . $_FILES[$param]["name"]);
									return str_replace("[file]","", $this->obj->get_default_option($param).  $_FILES[$param]["name"]) ; 
								} else if (is_file($path . $_FILES[$param]["name"])) {
									return str_replace("[file]","", $this->obj->get_default_option($param).  $_FILES[$param]["name"]) ; 
								} else {
									return 	array("error", "<p>".__('Error: security issue!'.$path . $_FILES[$param]["name"], 'SL_framework')."</p>\n") ; 
								}
							}
						} else {
							return $this->obj->get_param($param) ; 
						}
					} else {
						return $this->obj->get_param($param) ; 
					}
				} 
			}
			return null ; 
		}
		
		/** ====================================================================================================================================================
		* Remove a parameter of the plugin which has been previously added through add_param (then it can be useful if you want to update a parameter without printing the form)
		*
		* It will also remove any comment for the same
		*
		* @param string $param the name of the parameter/option as defined in your plugin and especially in the <code>get_default_option</code> of your plugin
		* @return void
		*/

		function remove_param($param)  {
			$search_comment = false ; 
			foreach($this->buffer as $j=>$i){
				if (!$search_comment) {
					if($i[0] == $param){
						unset($this->buffer[$j]) ; 
						$search_comment = true ; 
					}
				} else {
					if ($i[0] == "comment"){
						unset($this->buffer[$j]) ; 
					} else {
						return ; 
					}
				}		
			}
			$this->buffer = array_values($this->buffer) ; 
		}

		
		/** ====================================================================================================================================================
		* Print the form with parameters
		* 	
		* @return void
		*/
		function flush()  {
			global $_POST ; 
			global $_FILES ; 
			global $wpdb ; 

			$this->buffer[] = array('end', "") ; 

			// We create the beginning of the form
				
			$this->output =  "<h3>".__("Parameters",'SL_framework')."</h3>" ; 
				
			if ($this->obj->getPluginID()!="") {
				$this->output .= "<p>".__("Here are the parameters of the plugin. Modify them at will to fit your needs.","SL_framework")."</p>" ; 
			} else {
				$this->output .= "<p>".__("Here are the parameters of the framework. Modify them at will to fit your needs.","SL_framework")."</p>" ; 			
			}
			$this->output .= "<div class='wrap parameters'><form enctype='multipart/form-data' method='post' action='".$_SERVER["REQUEST_URI"]."'>\n" ; 
			
			// We compute the parameter output
			$hastobeclosed = false ; 
			$maj = false ; 
			$modified = false ; 
			$error = false ; 
			$warning = false ; 
			$toExecuteWhenLoaded = "" ; 
			$macroisdisplayed_count = 0 ;
			$macroisdisplayed = false ; 
			$macroisdisplayed_avoidnext = false ; 
				
			for($iii=0; $iii<count($this->buffer); $iii++) {
				$ligne = $this->buffer[$iii] ; 
								
				// Is it a title
				if (($ligne[0]=="end")||($ligne[0]=="title")||($ligne[0]=="title_macro")) {	
					if ($hastobeclosed) {
						$this->output .= $currentTable->flush()."<br/>" ; 
						$hastobeclosed = false ; 
					} 
					
					// On test si on doit recommencer
					if (($macroisdisplayed) && (!$macroisdisplayed_avoidnext)) {
						$nnn = 1 ; 
						// We search for the next parameter
						$found_param=false ; 
						while (isset($this->buffer[$macro_lasttitle+$nnn])) {
							$first_param_after = $this->buffer[$macro_lasttitle+$nnn] ; 
							if ($first_param_after[0]=='param') {
								$found_param = true ; 
								$first_param_after = $first_param_after[1] ; 
								break ; 
							} else if ($first_param_after[0]=='comment') {
								$nnn ++ ; 
							} else {
								break ; 
							}
						}
						
						// if the param has been found
						if ($found_param) {
							$all_names = $this->obj->get_name_params() ; 
							if (in_array($first_param_after."_macro".($macroisdisplayed_count+1), $all_names)) {
								$iii = $macro_lasttitle-1 ; 
								$macroisdisplayed_count ++ ; 
								$macroisdisplayed_avoidnext = true ; 
								continue ; 
							} else {
								$macroisdisplayed_count=0; 
								$macroisdisplayed = false ; 
								$macroisdisplayed_avoidnext = false ; 
							}
						}
					}
					$macroisdisplayed_avoidnext = false ; 

					// We create a new table 
					$currentTable = new SLFramework_Table() ; 
					$currentTable->removeFooter() ; 
					$hastobeclosed = true ;
					if ($ligne[0]=="title") {
						$currentTable->title(array($ligne[1], "") ) ; 
						$macroisdisplayed = false ;
						$macroisdisplayed_text = "" ; 
					} else if ($ligne[0]=="title_macro"){
						
						// Add delete button
						$params = "[" ; 
						$count_param_temp = 0 ; 
						$nnn=1 ; 
						while (isset($this->buffer[$iii+$nnn])) {
							$first_param_after = $this->buffer[$iii+$nnn] ; 
							if ($first_param_after[0]=='param') {
								if ($count_param_temp!=0) {
									$params .= "," ; 
								}
								$params .= "\"".$first_param_after[1]."_macro".$macroisdisplayed_count."\"" ; 
								$nnn ++ ; 
								$count_param_temp ++ ; 
							} else if ($first_param_after[0]=='comment') {
								$nnn ++ ; 
							} else {
								break ; 
							}
						}
						$params .= "]" ; 
						$md5 = sha1($params) ; 
						$delete = " <a href='#' onclick='del_param(".$params.", \"".$md5."\", \"".$this->obj->pluginID."\");return false ; ' style='font-size:80%'>".__('(Delete)', 'SL_framework')."</a>" ; 
						$x = plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__)) ; 
						$delete .= "<img id='wait_".$md5."' src='".$x."/img/ajax-loader.gif' style='display:none;'>" ; 

						// Add add button
						$add = "" ; 
						$macroisdisplayed_text = $ligne[2] ; 
						if  ($macroisdisplayed_count==0) {	
							$params = "[" ; 
							$count_param_temp = 0 ; 
							$nnn=1 ; 
							while (isset($this->buffer[$iii+$nnn])) {
								$first_param_after = $this->buffer[$iii+$nnn] ; 
								if ($first_param_after[0]=='param') {
									if ($count_param_temp!=0) {
										$params .= "," ; 
									}
									$params .= "\"".$first_param_after[1]."_macro\"" ; 
									$nnn ++ ; 
									$count_param_temp ++ ; 
								} else if ($first_param_after[0]=='comment') {
									$nnn ++ ; 
								} else {
									break ; 
								}
							}
							$params .= "]" ; 
							$md5 = sha1($params) ; 
							$add = " <a href='#' onclick='add_param(".$params.", \"".$md5."\", \"".$this->obj->pluginID."\");return false ; ' style='font-size:80%'>(".$macroisdisplayed_text.")</a>" ; 
							$x = plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__)) ; 
							$add .= "<img id='wait_".$md5."' src='".$x."/img/ajax-loader.gif' style='display:none;'>" ; 
						}

						if (strpos($ligne[1],"%s")!==false) {
							$currentTable->title(array(sprintf($ligne[1], $macroisdisplayed_count+1).$delete.$add, "")) ;
						} else {
							$currentTable->title(array($ligne[1].$delete, "")) ;
						}
						$macro_lasttitle = $iii ; 
						$macroisdisplayed_count_elements = 0 ;
						$macroisdisplayed = true ;
					} 
				}
				// compte le nombre d element dans la macro
				if ($macroisdisplayed) {
					$macroisdisplayed_count_elements ++ ; 
				} else {
					$macroisdisplayed_count_elements = 0 ; 
				}
				
				// Is it a comment
				if ($ligne[0]=="comment") {	
					if (!$hastobeclosed) {
						// We create a default table as no title has been provided
						$currentTable = new SLFramework_Table() ; 
						$currentTable->removeFooter() ; 
						$currentTable->title(array(__("Parameters","SL_framework"), __("Values","SL_framework")) ) ; 
						$hastobeclosed = true ; 
					}
					$cl = "<p class='paramComment' style='color: #a4a4a4;'>".$ligne[1]."</p>" ; 
					// We check if there is a comment just after it
					while (isset($this->buffer[$iii+1])) {
						if ($this->buffer[$iii+1][0]!="comment") break ; 
						$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
						$iii++ ; 
					}
					$cel_label = new adminCell($cl) ; 
					$cel_value = new adminCell("") ; 
					$currentTable->add_line(array($cel_label, $cel_value), '1') ; 
				}
				
				
				// Is it a param
				if ($ligne[0]=="param") {	
					$param = $ligne[1] ; 
					$param_default = $ligne[1] ; 
					//macro
					if ($macroisdisplayed) {
						$param = $param."_macro".$macroisdisplayed_count ; 
					}
					$name = $ligne[2] ; 
					$forbid = $ligne[3] ; 
					$allow = $ligne[4] ; 
					$related = $ligne[5] ; 
					if (!$hastobeclosed) {
						// We create a default table as no title has been provided
						$currentTable = new SLFramework_Table() ; 
						$currentTable->removeFooter() ; 
						$currentTable->title(array(__("Parameters","SL_framework"), __("Values","SL_framework")) ) ; 
						$hastobeclosed = true ; 
					}
					
					// What is the type of the parameter ?
					//---------------------------------------
					$type = "string" ; 
					if (is_bool($this->obj->get_default_option($param_default))) $type = "boolean" ; 
					if (is_int($this->obj->get_default_option($param_default))) $type = "int" ; 
					if (is_array($this->obj->get_default_option($param_default))) $type = "list" ; 
					// C'est un text si dans le texte par defaut, il y a une etoile
					if (is_string($this->obj->get_default_option($param_default))) {
						if (strpos($this->obj->get_default_option($param_default), "*") === 0) $type = "text" ; 
					}
					// C'est un file si dans le texte par defaut est egal a [file]
					if (is_string($this->obj->get_default_option($param_default))) {
						if (str_replace("[file]","",$this->obj->get_default_option($param_default)) != $this->obj->get_default_option($param_default)) $type = "file" ; 
					}
					// C'est un password si dans le texte par defaut est egal a [password]
					if (is_string($this->obj->get_default_option($param_default))) {
						if (str_replace("[password]","",$this->obj->get_default_option($param_default)) != $this->obj->get_default_option($param_default)) $type = "password" ; 
					}
					// C'est un media si dans le texte par defaut est egal a [media]
					if (is_string($this->obj->get_default_option($param_default))) {
						if (str_replace("[media]","",$this->obj->get_default_option($param_default)) != $this->obj->get_default_option($param_default)) $type = "media" ; 
					}
					// C'est un page si dans le texte par defaut est egal a [page]
					if (is_string($this->obj->get_default_option($param_default))) {
						if (str_replace("[page]","",$this->obj->get_default_option($param_default)) != $this->obj->get_default_option($param_default)) $type = "page" ; 
					}
					
					// We reset the param
					//---------------------------------------
					
					$problem_e = "" ; 
					$problem_w = "" ; 
					if (isset($_POST['resetOptions'])) {
						$maj = true ; 
						$new_param = $this->get_new_value($param_default) ; 
						$modified = true ; 
						$this->obj->set_param($param, $new_param) ; 
					}
										
					// We update the param
					//---------------------------------------
					
					$problem_e = "" ; 
					$problem_w = "" ; 
					if (isset($_POST['submitOptions'])) {
						
						$maj = true ; 

						$new_param = $this->get_new_value($param) ; 
						$old_param = $this->obj->get_param($param) ; 
						
						if (is_array($new_param) && (isset($new_param[0])) && ($new_param[0]=='error')) {
							$problem_e .= $new_param[1] ; 
							$error = true ; 
						} else {
						
							// Warning management
							if (($type=="string")||($type=="text")||($type=="password")) {
								if (isset($_POST[$param])) {
									if ($new_param!=stripslashes($_POST[$param])) {
										$problem_w .= "<p>".__('Warning: some characters have been removed because they are not allowed here', 'SL_framework')." (".$forbid.")!</p>\n" ; 
										$warning = true ; 
									}
								}
							} 
							
							// Update of the value
							if ($new_param != $old_param) {
								$modified = true ; 
								$this->obj->set_param($param, $new_param) ; 
								SLFramework_Debug::log(get_class(), "The parameter ".$param." of the plugin ".$this->obj->getPluginID()." have been modified", 4) ; 
							}
						}
					}
					
					// We built a new line for the table
					//---------------------------------------
					if ($type=="boolean") {
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>" ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$checked = "" ; 
						if ($this->obj->get_param($param)) { 
							$checked = "checked" ;  
						}
						if (count($related)>0) { 
							$onClick = "onClick='activateDeactivate_Params(\"".$param."\",new Array(\"".implode("\",\"", $related)."\"))'" ;  
							$toExecuteWhenLoaded .= "activateDeactivate_Params(\"".$param."\",new Array(\"".implode("\",\"", $related)."\"));\n" ; 
						} else {
							$onClick = "" ; 
						}
						$workaround = "<input type='hidden' value='0' name='".$param."_workaround' id='".$param."_workaround'>" ; 
						$cel_value = new adminCell("<p class='paramLine'>".$workaround."<input ".$onClick." name='".$param."' id='".$param."' type='checkbox' ".$checked." ></p>") ; 
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 
					}
					
					if ($type=="int") {
						$ew = "" ; 
						if ($problem_e!="") {	
							$ew .= "<div class='errorSedLex'>".$problem_e."</div>" ; 
						}
						if ($problem_w!="") {	
							$ew .= "<div class='warningSedLex'>".$problem_w."</div>" ; 
						}
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>".$ew ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$cel_value = new adminCell("<p class='paramLine'><input name='".$param."' id='".$param."' type='text' value='".$this->obj->get_param($param)."' size='".min(30,max(6,(strlen($this->obj->get_param($param).'')+1)))."'> ".__('(integer)', 'SL_framework')."</p>") ; 
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 
					}
					
					if ($type=="string") {
						$ew = "" ; 
						if ($problem_e!="") {	
							$ew .= "<div class='errorSedLex'>".$problem_e."</div>" ; 
						}
						if ($problem_w!="") {	
							$ew .= "<div class='warningSedLex'>".$problem_w."</div>" ; 
						}
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>".$ew ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$cel_value = new adminCell("<p class='paramLine'><input name='".$param."' id='".$param."' type='text' value='".htmlentities($this->obj->get_param($param), ENT_QUOTES, "UTF-8")."' size='".min(30,max(6,(strlen($this->obj->get_param($param).'')+1)))."'></p>") ; 
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 			
					}
					
					if ($type=="password") {
						$ew = "" ; 
						if ($problem_e!="") {	
							$ew .= "<div class='errorSedLex'>".$problem_e."</div>" ; 
						}
						if ($problem_w!="") {	
							$ew .= "<div class='warningSedLex'>".$problem_w."</div>" ; 
						}
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>".$ew ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$cel_value = new adminCell("<p class='paramLine'><input name='".$param."' id='".$param."' type='password' value='".htmlentities($this->obj->get_param($param), ENT_QUOTES, "UTF-8")."' size='".min(30,max(6,(strlen($this->obj->get_param($param).'')+1)))."'></p>") ; 
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 			
					}	
									
					if ($type=="text") {
						$num = min(22,count(explode("\n", $this->obj->get_param($param))) + 1) ; 
						$ew = "" ; 
						if ($problem_e!="") {	
							$ew .= "<div class='errorSedLex'>".$problem_e."</div>" ; 
						}
						if ($problem_w!="") {	
							$ew .= "<div class='warningSedLex'>".$problem_w."</div>" ; 
						}
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>".$ew ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$cel_value = new adminCell("<p class='paramLine'><div style='width:100%'><textarea style='width:100%' name='".$param."' id='".$param."' rows='".$num."'>".htmlentities($this->obj->get_param($param), ENT_QUOTES, "UTF-8")."</textarea></div></p>") ; 
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 			
					}
					
					if ($type=="list") {
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>" ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$cc = "" ; 
						ob_start() ; 
						?>
							<p class='paramLine'>
							<select name='<?php echo $param ; ?>' id='<?php echo $param ; ?>'>
							<?php 
							$array = $this->obj->get_param($param);
							
							echo "//" ; print_r($array) ; 

							foreach ($array as $a) {
								if (!is_array($a)) {
									$selected = "" ; 
									if ( (is_string($a)) && (substr($a, 0, 1)=="*") ) {
										$selected = "selected" ; 
										$a = substr($a, 1) ; 
									}
									?>
										<option value="<?php echo SLFramework_Utils::create_identifier($a) ; ?>" <?php echo $selected ; ?>><?php echo $a ; ?></option>
									<?php
								} else {
									$selected = "" ; 
									if ( (is_string($a[0])) && (substr($a[0], 0, 1)=="*") ) {
										$selected = "selected" ; 
										$a[0] = substr($a[0], 1) ; 
									}
									?>
										<option value="<?php echo $a[1] ; ?>" <?php echo $selected ; ?>><?php echo $a[0] ; ?></option>
									<?php
								}
							}
							?>
							</select>
							</p>
						<?php
						$cc = ob_get_clean() ; 
						$cel_value = new adminCell($cc) ; 
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 			
					}
					
					if ($type=="media") {
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>".$ew ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						
						// If this is the URL of an auto-generated thumbnail, get the URL of the original image
						$attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '',  $this->obj->get_param($param) );						
						$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';",$attachment_url)); 
        				if (isset($attachment[0])) {
        					$id_media =  $attachment[0]; 
        					$msg_media = "<p class='paramComment' style='color: #a4a4a4;'>".sprintf(__("The URL is correct and the ID of the media file is %s.",'SL_framework'),'<code>'.$id_media."</code>")."</p>" ; 
        					if (wp_attachment_is_image( $id_media )) {
        						$msg_media .= '<p class="paramComment" style="color: #a4a4a4; text-align:center;"><a href="'.get_attachment_link( $id_media ).'">'.wp_get_attachment_image( $id_media, "thumbnail").'</a></p>' ; 
        					} else {
        						$msg_media .= '<p class="paramComment" style="color: #a4a4a4; text-align:center;>coucou</p>' ; 
        					}
        					
        				} else {
        					$msg_media = "<p class='paramComment' style='color: #a4a4a4;'>".__("The URL is not a media file.",'SL_framework')."</p>" ; 
        				} 
						$cel_value = new adminCell("<p class='paramLine'><div style='width:100%'><input id='".$param."' type='text' size='20' name='".$param."' value='".htmlentities($this->obj->get_param($param), ENT_QUOTES, "UTF-8")."' /><input id='media_".$param."' class='button' type='button' value='".__('Choose a media', 'SL_framework')."' onclick=\"paramMediaReturn = '".$param."'; formfield = jQuery('#".$param."').attr('name'); tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true'); return false;\"/></div></p>".$msg_media) ; 
						
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 	
					}
					
					if ($type=="page") {
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>".$ew ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						
						$selected = 0 ;
						if ($this->obj->get_param($param)!="[page]") {
							$selected = $this->obj->get_param($param) ; 
						} 
						
						$cel_value = new adminCell("<p class='paramLine'>".wp_dropdown_pages(array('echo' => 0,'name' => $param, 'selected' => $selected, "show_option_none" => __('(none)', "SLFramework"), "option_none_value"=>'[page]'))."</p>") ; 
						
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 	
					}
					
					if ($type=="file") {
						$ew = "" ; 
						if ($problem_e!="") {	
							$ew .= "<div class='errorSedLex'>".$problem_e."</div>" ; 
						}
						if ($problem_w!="") {	
							$ew .= "<div class='warningSedLex'>".$problem_w."</div>" ; 
						}
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>".$ew ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$cc = "" ; 
						ob_start() ; 
							$upload_dir = wp_upload_dir();
							if (!file_exists($upload_dir["basedir"].$this->obj->get_param($param))) {
								$this->obj->set_param($param,$this->obj->get_default_option($param_default)) ; 
							}
							if ($this->obj->get_default_option($param_default)==$this->obj->get_param($param)) {
								?>
								<p class='paramLine'><input type='file' name='<?php echo $param ; ?>' id='<?php echo $param ; ?>'/></p>
								<?php 
							} else {
								$path = $upload_dir["baseurl"].$this->obj->get_param($param) ; 
								$pathdir = $upload_dir["basedir"].$this->obj->get_param($param) ; 
								$info = pathinfo($pathdir) ; 
								if ((strtolower($info['extension'])=="png") || (strtolower($info['extension'])=="gif") || (strtolower($info['extension'])=="jpg") ||(strtolower($info['extension'])=="bmp")) {
									list($width, $height) =  getimagesize($pathdir) ; 
									$max_width = 100;
									$max_height = 100; 
									$ratioh = $max_height/$height;
									$ratiow = $max_width/$width;
									$ratio = min($ratioh, $ratiow);
									// New dimensions
									$width = min(intval($ratio*$width), $width);
									$height = min(intval($ratio*$height), $height);  
									?>
									<p class='paramLine'><img src='<?php echo $path; ?>' width="<?php echo $width?>px" height="<?php echo $height?>px" style="vertical-align:middle;"/> <a href="<?php echo $path ; ?>"><?php echo $this->obj->get_param($param) ; ?></a></p>
									<?php 								
								} else {
									?>
									<p class='paramLine'><img src='<?php echo plugin_dir_url("/").'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__))."img/file.png" ; ?>' width="75px" style="vertical-align:middle;"/> <a href="<?php echo $path ; ?>"><?php echo $this->obj->get_param($param) ; ?></a></p>
									<?php 
								}
								?>
								<p class='paramLine'><?php echo sprintf(__("(If you want to delete this file, please check this box %s)", "SL_framework"), "<input type='checkbox'  name='delete_".$param."' value='1' id='delete_".$param."'>") ; ?></p>
								<?php 
							}
						$cc = ob_get_clean() ; 
						$cel_value = new adminCell($cc) ; 						
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 			
					}
				}
				// End is it a param?
			}
			
			// We finish the form output
			ob_start();
			?>
					<div class="submit">
						<input type="submit" name="submitOptions" class='button-primary validButton' value="<?php echo __('Update', 'SL_framework') ?>" />&nbsp;
						<input type="submit" name="resetOptions" class='button validButton' value="<?php echo __('Reset to default values', 'SL_framework') ?>" />
					</div>
				</form>
			</div>
			<script>
				if (window.attachEvent) {window.attachEvent('onload', toExecuteWhenLoadedParameter);}
				else if (window.addEventListener) {window.addEventListener('load', toExecuteWhenLoadedParameter, false);}
				else {document.addEventListener('load', toExecuteWhenLoadedParameter, false);} 
				
				function toExecuteWhenLoadedParameter() {
					<?php echo $toExecuteWhenLoaded ;  ?>
				}
			</script>
			<?php

			// If the parameter have been modified, we say it !
			
			if (($error) && ($maj)) {
				?>
				<div class="error fade">
					<p><?php echo __('Some parameters have not been updated due to errors (see below)!', 'SL_framework') ?></p>
				</div>
				<?php
			} else if (($warning) && ($maj)) {
				?>
				<div class="updated  fade">
					<p><?php echo __('Parameters have been updated (but with some warnings)!', 'SL_framework') ?></p>
				</div>
				<?php
			} else if (($modified) && ($maj)) {
				if (!isset($_POST['resetOptions'])) {
				?>
				<div class="updated  fade">
					<p><?php echo __('Parameters have been updated successfully!', 'SL_framework') ?></p>
				</div>
				<?php
				} else {
				?>
				<div class="updated  fade">
					<p><?php echo __('Parameters have been reset to their default values!', 'SL_framework') ?></p>
				</div>
				<?php
				}
			} 
			
			$this->output .= ob_get_clean();
			echo $this->output ; 
		}
	}
}


?>