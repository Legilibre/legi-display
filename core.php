<?php
/**
* Core SedLex Plugin Bootstrap
* VersionInclude : 4.0
*/ 

/* Prevent direct access to this file */
if (!defined('ABSPATH')) {
	exit("Sorry, you are not allowed to access this file directly.");
}

$url = "" ; 

if (!class_exists('pluginSedLex')) {
	$folders = scandir(WP_PLUGIN_DIR) ; 
	$date = 0 ; 
	foreach ($folders as $f) {
		if ($f != "." && $f != "..") {
			if (is_dir(WP_PLUGIN_DIR."/".$f)) {
				if (is_file(WP_PLUGIN_DIR."/".$f."/core.nfo")) {
					if (is_file(WP_PLUGIN_DIR."/".$f."/core.class.php")) {
						$info = explode("#",file_get_contents(WP_PLUGIN_DIR."/".$f."/core.nfo")) ; 
						// We get the max (< 0 if str1 is less than str2)
						if (strcmp($date, $info[1])<0) {
							$date = $info[1] ; 
							$url = WP_PLUGIN_DIR."/".$f."/" ; 
						}
					}
				}
			}
		}
	}
	
	$files = array() ; 
	
	$files[] = $url.'core.php' ; 
	
	$files[] = $url.'core.class.php' ; 
	$files[] = $url.'core/admin_table.class.php'; 
	$files[] = $url.'core/tabs.class.php' ; 
	$files[] = $url.'core/box.class.php' ; 
	$files[] = $url.'core/browser.class.php' ; 
	$files[] = $url.'core/debug.class.php' ; 
	$files[] = $url.'core/feedback.class.php' ; 
	$files[] = $url.'core/otherplugins.class.php' ; 
	$files[] = $url.'core/parameters.class.php' ; 
	$files[] = $url.'core/popup.class.php' ; 
	$files[] = $url.'core/progress_bar.class.php'; 
	$files[] = $url.'core/translation.class.php'; 
	$files[] = $url.'core/text_diff.class.php' ; 
	$files[] = $url.'core/tree.class.php'; 
	$files[] = $url.'core/utils.class.php' ; 
	
	foreach ($files as $f) {
		if (file_exists($f)) {
			require_once($f) ; 
		}
	}
}

?>