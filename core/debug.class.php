<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class allow to log debug information regarding the execution of plugins
* For instance : 
* <code>SLFramework_Debug::log(get_class(), "An error occurred!", $error_level);</code>
* the error_level may be 1=critical ; 2=error ; 3=warning ; 4=information ; 5=verbose.
*/
if (!class_exists("SLFramework_Debug")) {
	class SLFramework_Debug {
	

		/** ====================================================================================================================================================
		* Get log file
		*
		* @return string the path of the log file
		*/

		static function get_log_path() {
			// Test if the directory of the log file exists
			if (!is_dir(WP_CONTENT_DIR."/sedlex/log/")) {
				@mkdir(WP_CONTENT_DIR."/sedlex/log/", 0755, true) ; 
			}
			// We scan the folder to find the log file
			$namelogfile = "" ; 
			$files = @scandir(WP_CONTENT_DIR."/sedlex/log/") ; 
			if (!empty($files)) {
				foreach ($files as $f) {
					if (preg_match("/log(.*)log/i", $f)) {
						$namelogfile = $f ; 
					}
				}
			}
			// If we didn't find anything 
			if ($namelogfile == "") {
				$namelogfile = "log_".rand(1,10000000).".log" ; 
				@touch(WP_CONTENT_DIR."/sedlex/log/".$namelogfile) ; 
			}
			
			return WP_CONTENT_DIR."/sedlex/log/".$namelogfile ; 
		}

		/** ====================================================================================================================================================
		* Log function
		*
		* @param string $where the name of the plugin which call the log function
		* @param string $text the text to be logged
		* @param integer $error_level 1=critical ; 2=error ; 3=warning ; 4=information ; 5=verbose
		* @return void
		*/

		static function log($where, $text, $error_level=5) {
			// Test if this message has to be loged
			$frmk = new coreSLframework() ; 
			$level = $frmk->get_param('debug_level') ; 
			
			if (!is_numeric($error_level))
				return ; 
			$error_level = floor($error_level) ; 
			if ($level<$error_level)
				return ; 
			
			$namelogfile = SLFramework_Debug::get_log_path() ; 
			
			// We get the old content
			$old_content = @array_slice(@file($namelogfile), 0, 1999) ; 
			if (!is_array($old_content )) {
				$old_content = array("") ; 
			}
			
			// Once the file is identified, we stored the new logfile
			$error = "VERBOSE" ; 
			if ($error_level==1)
				$error = "CRITICAL" ; 
			if ($error_level==2)
				$error = "ERROR" ; 
			if ($error_level==3)
				$error = "WARNING" ; 
			if ($error_level==4)
				$error = "INFO" ; 
			if ($error_level==5)
				$error = "VERBOSE" ; 
			$new_content = "[".date("Ymd His")."] [".@getmypid()."] [".$where."] [".$error."] - ".$text."\r\n".implode("", $old_content) ; 
			
			// We store the content
			@file_put_contents($namelogfile, $new_content) ; 
		}
	} 
}




?>