<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enables the translation of the plugin using the framework
*/
if (!class_exists("SLFramework_Feedback")) {
	class SLFramework_Feedback {

		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @param string $plugin the name of the plugin (probably <code>str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__)))</code>)
		* @param string $pluginID the pluginID of the plugin (probably <code>$this->pluginID</code>)
		* @return SLFramework_Feedback the SLFramework_Feedback object
		*/
		function SLFramework_Feedback($plugin, $pluginID) {
			$this->plugin = $plugin ; 
			$this->pluginID = $pluginID ; 
		}
		
		/** ====================================================================================================================================================
		* Display the feedback form
		* Please note that the users will send you their comments/feedback at the email used is in the header of the main file of your plugin <code>Author Email : name@domain.tld</code>
		* 
		* @return void
		*/

		public function enable_feedback() {
			
			$_POST['plugin'] = $this->plugin ; 
			
			$info_file = pluginSedLex::get_plugins_data(WP_PLUGIN_DIR."/".$this->plugin."/".$this->plugin.".php") ; 

			if (preg_match("#^[a-z0-9-_.]+@[a-z0-9-_.]{2,}\.[a-z]{2,4}$#",$info_file['Email'])) {
				?><h3><?php echo __("Donate", "SL_framework") ?></h3>
				<p><?php echo __('If you like the plugin, do not hesitate to donate. Please note that this plugin is developed in my spare time for free.', 'SL_framework')?></p>
				<p><?php echo __('This is not mandatory! but it may be a sign that this plugin fits you needs: it makes me happy...', 'SL_framework')?></p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
					<input type="hidden" name="cmd" value="_donations">
					<input type="hidden" name="business" value="<?php echo $info_file['Email'] ;?>">
					<input type="hidden" name="item_name" value="Wordpress plugin (<?php echo $this->plugin ;?>)">
					<input type="hidden" name="currency_code" value="EUR">
					<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">
					<input type="image" src="https://www.paypalobjects.com/en_US/i/bnr/vertical_solution_PP.gif" border="0" name="submit" alt="PayPal">
					<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
				</form>
			<?php
			}

			echo  "<h3>".__("Feedback form",'SL_framework')."</h3>" ; 
			echo "<p>".__('This form is an easy way to contact the author and to discuss issues/incompatibilities/etc. with him',  "SL_framework")."</p>" ; 
			echo "<a name='top_feedback'></a><div id='form_feedback_info'></div><div id='form_feedback'>" ; 

			if (preg_match("#^[a-z0-9-_.]+@[a-z0-9-_.]{2,}\.[a-z]{2,4}$#",$info_file['Email'])) {
				$table = new SLFramework_Table() ; 
				$table->title(array(__("Contact the author", "SL_framework"), "") ) ;
				// Name
				$cel1 = new adminCell("<p>".__('Your name:', 'SL_framework')."*</p>") ;
				$cel2 = new adminCell("<p><input onChange='modifyFormContact()' id='feedback_name' type='text' name='feedback_name' value='' /></p>") ;
				$table->add_line(array($cel1, $cel2), '1') ;	
				// Email
				$cel1 = new adminCell("<p>".__('Your email:', 'SL_framework')."*</p><p class='paramComment' style='color: rgb(164, 164, 164);'>".__('Useful... so that the author will be able to anwser you.', 'SL_framework')."</p>") ;
				$cel2 = new adminCell("<p><input onChange='modifyFormContact()' id='feedback_mail' type='text' name='feedback_mail' value='' /></p>") ;
				$table->add_line(array($cel1, $cel2), '1') ;	
				// Comment
				$cel1 = new adminCell("<p>".__('Your comments:', 'SL_framework')."*</p><p class='paramComment' style='color: rgb(164, 164, 164);'>".__('Please note that additional information on your wordpress installation will be sent to the author in order to help the debugging if needed (such as : the wordpress version, the installed plugins, etc.)', 'SL_framework')."</p>") ;
				$cel2 = new adminCell("<p><textarea id='feedback_comment' style='width:500px;height:200px;'></textarea></p>") ;
				$table->add_line(array($cel1, $cel2), '1') ;	
				
				echo $table->flush() ; 
				
				echo "<p id='feedback_submit'><input id='feedback_submit_button' disabled type='submit' name='add' class='button-primary validButton' onclick='send_feedback(\"".$this->plugin."\", \"".$this->pluginID."\");return false;' value='".__('Send feedback','SL_framework')."' /></p>" ; 
				
				$x = plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__)) ; 
				echo "<img id='wait_feedback' src='".$x."/img/ajax-loader.gif' style='display:none;'>" ; 
			} else {
				echo "<p>".__('No email have been provided for the author of this plugin. Therefore, the feedback is impossible', 'SL_framework')."</p>" ; 
			}
			echo "</div>" ;
		}
		
		/** ====================================================================================================================================================
		* Send the feedback form
		* 
		* @access private
		* @return void
		*/
		public function send_feedback() {
			// We sanitize the entries
			$plugin = preg_replace("/[^a-zA-Z0-9_-]/","",$_POST['plugin']) ; 
			$pluginID = preg_replace("/[^a-zA-Z0-9_]/","",$_POST['pluginID']) ; 
			$name = strip_tags($_POST['name']) ; 
			$mail = preg_replace("/[^:\/a-z0-9@A-Z_.-]/","",$_POST['mail']) ; 
			$comment = strip_tags($_POST['comment']) ; 
			
			// If applicable, we select the log file
			$logfile = SLFramework_Debug::get_log_path() ; 
			
			$info_file = pluginSedLex::get_plugins_data(WP_PLUGIN_DIR."/".$plugin."/".$plugin.".php") ; 
			
			$to = $info_file['Email'] ; 
			
			$subject = "[".ucfirst($plugin)."] Feedback of ".$name ; 
			
			$message = "" ; 
			$message .= "From $name (".$mail.")\n\n\n" ; 
			$message .= $comment."\n\n\n" ; 
			
			$message .= "* Accounts \n" ; 
			$message .= "**************************************** \n" ; 
			$admin = get_userdata(1);
			$message .= "Admin User Name: " . $admin->display_name ."\n" ;
			$message .= "Admin User Login: " . $admin->user_login."\n" ;
			$message .= "Admin User Mail: " . $admin->user_email."\n" ;
			$current_user = wp_get_current_user();
			$message .= "Logged User Name: " . $current_user->display_name ."\n" ;
			$message .= "Logged User Login: " . $current_user->user_login."\n" ;
			$message .= "Logged User Mail: " . $current_user->user_email."\n" ;
			$message .= "\n\n\n" ; 
			
			$message .= "* Information \n" ; 
			$message .= "**************************************** \n" ; 
			$message .= "Plugin: ".$plugin."\n" ;
			$message .= "Plugin Version: ".$info_file['Version']."\n" ; 
			$message .= "Wordpress Version: ".get_bloginfo('version')."\n" ; 
			$message .= "URL (home): ".home_url('/')."\n" ; 
			$message .= "URL (site): ".site_url('/')."\n" ; 
			$message .= "Language: ".get_bloginfo('language')."\n" ; 
			$message .= "Charset: ".get_bloginfo('charset')."\n" ; 
			$message .= "\n\n\n" ; 
			
			$message .= "* Configuration of the plugin \n" ; 
			$message .= "**************************************** \n" ; 
			$options = get_option($pluginID.'_options'); 
			// mask the password
			$new_option = array() ; 
			$new_plugin_copy = call_user_func(array($pluginID, 'getInstance'));

			foreach ($options as $o=>$v) {
				if ($new_plugin_copy->get_default_option($o)!=="[password]") {
					$new_option[$o] = $v ; 
				} else {
					$new_option[$o] = "********** (masked)" ; 
				}
			}
			ob_start() ; 
				print_r($new_option) ; 
			$message .= ob_get_clean() ; 
			$message .= "\n\n\n" ; 
			
			$message .= "* Activated plugins \n" ; 
			$message .= "**************************************** \n" ; 
			$plugins = get_plugins() ; 
			$active = get_option('active_plugins') ; 
			foreach($plugins as $file=>$p){
				if (array_search($file, $active)!==false) {
					$message .= $p['Name']."(".$p['Version'].") => ".$p['PluginURI']."\n" ; 
				}
			}
			
			
			$headers = "" ; 
			if (preg_match("#^[a-z0-9-_.]+@[a-z0-9-_.]{2,}\.[a-z]{2,4}$#",$mail)) {
				$headers = "Reply-To: $mail\n".
						"Return-Path: $mail" ; 
			}
			
			$attachments = array($logfile);
			
			// send the email
			if (wp_mail( $to, $subject, $message, $headers, $attachments )) {
				echo "<div class='updated  fade'>" ; 
				echo "<p>".__("The feedback has been sent", 'SL_framework')."</p>" ; 
				echo "</div>" ; 
				SLFramework_Debug::log(get_class(), "A feedback mail has been sent.", 4) ; 
			} else {
				echo "<div class='error  fade'>" ; 
				echo "<p>".__("An error occured sending the email.", 'SL_framework')."</p><p>".__("Make sure that your wordpress is able to send email.", 'SL_framework')."</p>" ; 
				echo "</div>" ; 	
				SLFramework_Debug::log(get_class(), "A feedback mail has failed to be sent.", 2) ; 
			}

			//Die in order to avoid the 0 character to be printed at the end
			die() ;
		}
	}
}


?>