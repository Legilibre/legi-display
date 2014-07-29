<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 


/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class regroups a few useful method to manage directory, string, ... 
*/
if (!class_exists("SLFramework_Utils")) {
	class SLFramework_Utils {
	
		/** ====================================================================================================================================================
		* To convert into UTF8
		* 
		* @param string $content the string to convert into UTF8
		* @return string the converted string
		*/

		static function convertUTF8($content) {
		    if(!mb_check_encoding($content, 'UTF-8')
			OR !($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) {

			$content = mb_convert_encoding($content, 'UTF-8');

			if (mb_check_encoding($content, 'UTF-8')) {
			    // log('Converted to UTF-8');
			} else {
			    // log('Could not converted to UTF-8');
			}
		    }
		    return $content;
		} 

		/** ====================================================================================================================================================
		* Compute the size of a directory (reccursively or not)
		* 
		* @param string $path the path of the directory to scan 
		* @param boolean $recursive set to FALSE if you do NOT want to reccurse in the folder of the directory
		* @return integer the size of the directory
		*/
		
		static function dirSize($path , $recursive=TRUE){
			$result = 0 ;
			if(!is_dir($path) || !is_readable($path)) {
				return 0;
			}
			$fd = dir($path);
			while($file = $fd->read()){
				if(($file != ".") && ($file != "..")){
					if(@is_dir($path.'/'.$file)) {
						$result += $recursive?SLFramework_Utils::dirSize($path.'/'.$file):0;
					} else {
						$result += filesize($path.'/'.$file);
					}
				}
			}
			$fd->close();
			return $result;
		}
		
		/** ====================================================================================================================================================
		* Test if the argument is really an integer (even if string)
		* For instance : 
		* <code>is_really_int(5)</code> will return TRUE.
		* <code>is_really_int("5")</code> will return TRUE.
		* <code>is_really_int(5.2)</code> will return FALSE.
		* <code>is_really_int(array(5))</code> will return FALSE.
		*
		* @param mixed $int the integer, float, string, ... to check 
		* @return boolean TRUE if it is an integer, FALSE otherwise
		*/
		
		static function is_really_int($int){
			if(is_numeric($int) === TRUE){
				// It's a number, but it has to be an integer
				if((int)$int == $int){
					return TRUE;
				// It's a number, but not an integer, so we fail
				}else{
					return FALSE;
				}
			// Not a number
			}else{
				return FALSE;
			}
		}
		
		/** ====================================================================================================================================================
		* Randomize a string
		* For instance, <code>rand_str(5, "0123456789")</code> will return a string of length 5 characters comprising only numbers 
		* 
		* @param integer $length the length of the randomized result string
		* @param string $chars the available characters for the randomized result string
		* @return string the randomized result string
		*/
		static function rand_str($length, $chars) {
			// Length of character list
			$chars_length = (strlen($chars) - 1);
			// Start our string
			$string = $chars{rand(0, $chars_length)};
			// Generate random string
			for ($i = 1; $i < $length; $i = strlen($string)) {
				// Grab a random character from our list
				$r = $chars{rand(0, $chars_length)};
				$string .=  $r;
			}
			// Return the string
			return $string;
		}
		
		/** ====================================================================================================================================================
		* Create an simple identifier from a given string. It removes all non alphanumeric characters and strip spaces
		* For instance : 
		* <code>create_identifier("Hello World 007")</code> will return "Hello_World_007".
		* <code>create_identifier("It's time !")</code> will return "Its_time_".
		* <code>create_identifier("4L car")</code> will return "L_car".
		* 
		* @param string $text the text to be sanitized
		* @return string the sanitized string (identifier)
		*/
		
		static public function create_identifier($text) {		
			// Pas d'espace
			$n = str_replace(" ", "_", strip_tags($text));
			// L'identifiant ne doit contenir que des caracteres alpha-numérique et des underscores...
			$n = preg_replace("#[^A-Za-z0-9_]#", "", $n);
			// l'identifiant doit commencer par un caractere "alpha"
			$n = preg_replace("#^[^A-Za-z]*?([A-Za-z])#", "$1", $n);
			return $n;
		}
		
		/** ====================================================================================================================================================
		* Convert an integer into a string which represent a  size in a computer format (ie. MB, KB, GB, etc.)
		* 
		* @param integer $bytes the number to convert into a byte-format (ie. MB, KB, GB, etc.)
		* @return string the size with a byte-format at the end (ie. MB, KB, GB, etc.)
		*/
		
		static function byteSize($bytes)  {
			$size = $bytes / 1024;
			if($size < 1024) {
				$size = number_format($size, 2);
				$size .= ' '.__('KB', 'SL_framework');
			} else {
				if($size / 1024 < 1024)  {
					$size = number_format($size / 1024, 2);
					$size .= ' '.__('MB', 'SL_framework');
				} else if ($size / 1024 / 1024 < 1024)  {
					$size = number_format($size / 1024 / 1024, 2);
					$size .= ' '.__('GB', 'SL_framework');
				} 
			}
			return $size;
		} 	
 
		/** ====================================================================================================================================================
		* Sort a table against the n-th column
		* 
		* @param array $data the table (i.e. array of array) to be sorted
		* @param integer $num the n-th column to be considered in order to sort the table 
		* @param boolean $asc the order will be ascendant if true and descendant otherwise
		* @return array the sorted table
		*/

		static function multicolumn_sort($data,$num,$asc=true){
 			$col_uniq = array() ; 
 			
 			foreach ($data as $val) {
    			$col_uniq[] = $val[$num];
  			}
  			
 			// We sort
			if ($asc) {
			 	array_multisort($col_uniq, SORT_ASC, $data);
			} else {
				array_multisort($col_uniq, SORT_DESC, $data);
			}
			
			return $data;
		} 
		
		/** ====================================================================================================================================================
		* Copy a file or a directory (recursively)
		* 
		* @param string $source the source directory
		* @param string $destination the destination directory
		* @return void
		*/

		static function copy_rec( $source, $destination ) {
			if ( is_dir( $source ) ) {
				@chmod($source, 0755) ; 
				@mkdir( $destination );
				@chmod($destination, 0755) ; 
				$directory = dir( $source );
				while ( FALSE !== ( $readdirectory = $directory->read() ) ) {
					if ( $readdirectory == '.' || $readdirectory == '..' ) {
						continue;
					}
					$PathDir = $source . '/' . $readdirectory; 
					if ( is_dir( $PathDir ) ) {
						SLFramework_Utils::copy_rec( $PathDir, $destination . '/' . $readdirectory );
						continue;
					}
					copy( $PathDir, $destination . '/' . $readdirectory );
				}
		 
				$directory->close();
			} else {
				copy( $source, $destination );
			}
		}
		
		/** ====================================================================================================================================================
		* Delete a file or a directory (recursively)
		* 
		* @param string $path the path to delete
		* @return boolean true if the dir or file does not exists at the end of the rm process
		*/

		static function rm_rec($path) {
			if (is_dir($path)) {
				@chmod($path, 0755) ; 
				$objects = scandir($path);
				foreach ($objects as $object) {
					if ($object != "." && $object != "..") {
						if (filetype($path."/".$object) == "dir") 
							SLFramework_Utils::rm_rec($path."/".$object); 
						else 
							@unlink($path."/".$object);
					}
				}
				reset($objects);
				@rmdir($path);
				if (is_dir($path)) {
					return false ; 
				}
			} else {
				if (is_file($path)) {
					@unlink($path) ; 
				}
				if (is_file($path)) {
					return false ; 
				}
			}
			return true ; 
		}
		
		/** ====================================================================================================================================================
		*Compute the md5 of a file or a directory (recursively)
		* 
		* @param string $path the path to compute hash
		* @param array $exclu a list of filename/folder to exclude from the hash
		* @return string md5 hash
		*/

		static function md5_rec($path, $exclu=array()) {
			$md5 = "" ;  
			$text = "" ; 
			if (is_dir($path)) {
				@chmod($path, 0755) ; 
				$objects = scandir($path);
				foreach ($objects as $object) {
					if ($object != "." && $object != "..") {
						if (filetype($path."/".$object) == "dir") {
							$toexclu = false ; 
							foreach($exclu as $e) {
								if ($e==$object) {
									$toexclu = true ; 
								}
							}
							if (!$toexclu) {
								$text .= SLFramework_Utils::md5_rec($path."/".$object, $exclu); 
							}
						} else {
							$toexclu = false ; 
							foreach($exclu as $e) {
								if ($e==$object) {
									$toexclu = true ;
								}
							}
							if (!$toexclu) 
								$text .= $object.file_get_contents($path."/".$object);
						}
					}
				}
				$md5 = sha1($text) ; 
			} else {
				$md5 = sha1_file($path) ; 
			}
			return $md5 ; 
		}	
		
		
		/** ====================================================================================================================================================
		* Check if a folder or a file is writable
		* 
		* @param string $path the path to the folder or the file
		* @return boolean true if the folder or the file is writable
		*/
		
		static function is_writable($path) {
			if ($path{strlen($path)-1}=='/') // recursively return a temporary file path
				return SLFramework_Utils::is_writable($path.uniqid(mt_rand()).'.tmp');
			else if (is_dir($path))
				return SLFramework_Utils::is_writable($path.'/'.uniqid(mt_rand()).'.tmp');
			
			// check tmp file for read/write capabilities
			$rm = file_exists($path);
			$f = @fopen($path, 'a');
			if ($f===false)
				return false;
			@fclose($f);
			if (!$rm)
				@unlink($path);
			return true;
		}
		
		/** ====================================================================================================================================================
		* Check if a folder or a file is readable
		* 
		* @param string $path the path to the folder or the file
		* @return boolean true if the folder or the file is writable
		*/
		
		static function is_readable($path) {
			if (is_dir($path))  {
				if (@scandir($path) === FALSE) {
					return false ; 
				}
				return true ; 
			}
			if (is_file($path))  {
				if (@fopen($path, 'r')=== FALSE) {
					return false ; 
				}
				return true ; 
			}
			return false ; 
		}
	} 
}

?>