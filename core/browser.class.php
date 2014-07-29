<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enables the Browser and OS detection
*/
if (!class_exists("SLFramework_BrowsersOsDetection")) {
	class SLFramework_BrowsersOsDetection  {
		
		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @param $agent the browser agent (i.e. should be $_SERVER['HTTP_USER_AGENT'])
		* @return SLFramework_BrowsersOsDetection 
		*/
		function SLFramework_BrowsersOsDetection($agent) {	
			// Default value
			$this->browser_name = 'Other';
			$this->browser_version = "?";
			$this->platform_name = 'Other';
			$this->platform_version = '?';
			$this->useragent = $agent;
			$this->mobile = false;
			$this->robot = false;
			
			/**
			 * Determine if the user is using a BlackBerry
			 */
			if ( stripos($agent,'blackberry') !== false ) {
				$aresult = explode("/",stristr($agent,"BlackBerry"));
				$aversion = explode(' ',$aresult[1]);
				$this->browser_version = $aversion[0];
				$this->browser_name = 'BlackBerry';
				$this->mobile = true;
				$this->robot = false;
			
			/**
			 * Determine if the user is the GoogleBot or not
			 */
			} else if( stripos($agent,'googlebot') !== false ) {
				$aresult = explode('/',stristr($agent,'googlebot'));
				$aversion = explode(' ',$aresult[1]);
				$this->browser_version = str_replace(';','',$aversion[0]);
				$this->browser_name = 'GoogleBot';
				$this->mobile = false;
				$this->robot = true;				
	
			/**
			 * Determine if the browser is the MSNBot or not
			 */
			} else if( stripos($agent,"msnbot") !== false ) {
				$aresult = explode("/",stristr($agent,"msnbot"));
				$aversion = explode(" ",$aresult[1]);
				$this->browser_version = str_replace(";","",$aversion[0]);
				$this->browser_name = 'MSNBot';
				$this->mobile = false;
				$this->robot = true;				
			
			/**
			 * Determine if the browser is the W3C Validator or not
			 */
			} else if( stripos($agent,'W3C-checklink') !== false ) {
				$aresult = explode('/',stristr($agent,'W3C-checklink'));
				$aversion = explode(' ',$aresult[1]);
				$this->browser_version = $aversion[0];
				$this->browser_name = "W3C_CkeckLink";
				$this->mobile = false;
				$this->robot = false;				
			} else if( stripos($agent,'W3C_Validator') !== false ) {
				// Some of the Validator versions do not delineate w/ a slash - add it back in
				$ua = str_replace("W3C_Validator ", "W3C_Validator/", $agent);
				$aresult = explode('/',stristr($ua,'W3C_Validator'));
				$aversion = explode(' ',$aresult[1]);
				$this->browser_version = $aversion[0];
				$this->browser_name = 'W3C_Validator' ;
				$this->mobile = false;
				$this->robot = false;				
	
			/**
			 * Determine if the browser is the Yahoo! Slurp Robot or not
			 */
			} else if( stripos($agent,'slurp') !== false ) {
				$aresult = explode('/',stristr($agent,'Slurp'));
				$aversion = explode(' ',$aresult[1]);
				$this->browser_version = $aversion[0];
				$this->browser_name = "YahooBot";
				$this->robot = true ;
				$this->mobile = false ;
	
			/**
			 * Determine if the browser is Opera or not (last updated 1.7)
			 * To be tested prior to MSIE and Mozilla because its header embedded both string
			 */
			} else if( stripos($agent,'opera mini') !== false ) {
				$resultant = stristr($agent, 'opera mini');
				if( preg_match('/\//',$resultant) ) {
					$aresult = explode('/',$resultant);
					$aversion = explode(' ',$aresult[1]);
					$this->browser_version = ($aversion[0]);
				} else {
					$aversion = explode(' ',stristr($resultant,'opera mini'));
					$this->browser_version = ($aversion[1]);
				}
				$this->browser_name = 'Opera Mini';
				$this->mobile = true ;
			} else if( stripos($agent,'opera') !== false ) {
				$resultant = stristr($agent, 'opera');
				if( preg_match('/Version\/(10.*)$/',$resultant,$matches) ) {
					$this->browser_version = ($matches[1]);
				} else if( preg_match('/\//',$resultant) ) {
					$aresult = explode('/',str_replace("("," ",$resultant));
					$aversion = explode(' ',$aresult[1]);
					$this->browser_version = ($aversion[0]);
				} else {
					$aversion = explode(' ',stristr($resultant,'opera'));
					$this->browser_version = (isset($aversion[1])?$aversion[1]:"");
				}
				$this->browser_name = 'Opera' ;

			/**
			 * Determine if the browser is Internet Explorer or not
			 */	
			// Test for v1 - v1.5 IE
			} else if( stripos($agent,'microsoft internet explorer') !== false ) {
				$this->browser_name = "Internet Explorer";
				$this->browser_version = '1.0';
				$aresult = stristr($agent, '/');
				if( preg_match('/308|425|426|474|0b1/i', $aresult) ) {
					$this->browser_version = '1.5';
				}
			// Test for versions > 1.5
			} else if( stripos($agent,'msie') !== false ) {
				// Test IE8
				if ( ( stripos($agent,'trident/4.0') !== false ) && ( stripos($agent,'MSIE 8') === false ) )  {
					$this->browser_name = ( 'Internet Explorer' );
					$this->browser_version = "8";
				// Test IE9
				} else if ( ( stripos($agent,'trident/5.0') !== false ) && ( stripos($agent,'MSIE 9') === false ) )  {
					$this->browser_name = ( 'Internet Explorer' );
					$this->browser_version = "9";
				// See if the browser is the odd MSN Explorer
				} else if( stripos($agent,'msnb') !== false ) {
					$aresult = explode(' ',stristr(str_replace(';','; ',$agent),'MSN'));
					$this->browser_name = ( "MSN Browser" );
					$this->browser_version = (str_replace(array('(',')',';'),'',$aresult[1]));
				} else {
					$aresult = explode(' ',stristr(str_replace(';','; ',$agent),'msie'));
					$this->browser_name = ( 'Internet Explorer' );
					$this->browser_version = (str_replace(array('(',')',';'),'',$aresult[1]));
				}
			// Test for Pocket IE
			} else if( stripos($agent,'mspie') !== false || stripos($agent,'pocket') !== false ) {
				$aresult = explode(' ',stristr($agent,'mspie'));
				$this->browser_name = ( 'Pocket Internet Explorer' );
				$this->mobile = true ;
				if ( stripos($agent,'mspie') !== false ) {
					$this->browser_version = ($aresult[1]);
				} else {
					$aversion = explode('/',$agent);
					$this->browser_version = ($aversion[1]);
				}
		
			/**
			 * Determine if the browser is Chrome or not
			 * (should be test before safari to avoid false positive)
			 */
			} else if( stripos($agent,'Chrome') !== false ) {
				$aresult = explode('/',stristr($agent,'Chrome'));
				$aversion = explode(' ',$aresult[1]);
				$this->browser_version = ($aversion[0]);
				$this->browser_name = ('Chrome');

			/**
			 * Determine if the browser is Galeon or not
			 */
			} else if( stripos($agent,'galeon') !== false ) {
				$aresult = explode(' ',stristr($agent,'galeon'));
				$aversion = explode('/',$aresult[0]);
				$this->browser_version = ($aversion[1]);
				$this->browser_name = ('Galeon');
		
			/**
			 * Determine if the browser is Konqueror or not 
			 */
			} else if( stripos($agent,'Konqueror') !== false ) {
				$aresult = explode(' ',stristr($agent,'Konqueror'));
				$aversion = explode('/',$aresult[0]);
				$this->browser_version = ($aversion[1]);
				$this->browser_name = ('Konqueror');
				
			/**
			 * Determine if the browser is iCab or not 
			 */
			} else if( stripos($agent,'icab') !== false ) {
				$aversion = explode(' ',stristr(str_replace('/',' ',$agent),'icab'));
				$this->browser_version = ($aversion[1]);
				$this->browser_name = ("iCab");
	
			/**
			 * Determine if the browser is Netscape Navigator 9+ or not
			 */
			} else if( stripos($agent,'Firefox') !== false && preg_match('/Navigator\/([^ ]*)/i',$agent,$matches) ) {
				$this->browser_version = ($matches[1]);
				$this->browser_name = ('Netscape Navigator');
			} else if( stripos($agent,'Firefox') === false && preg_match('/Netscape6?\/([^ ]*)/i',$agent,$matches) ) {
				$this->browser_version = ($matches[1]);
				$this->browser_name = ('Netscape Navigator');
	
			/**
			 * Determine if the browser is Nokia or not 
			 */
			} else if( preg_match("/Nokia([^\/]+)\/([^ SP]+)/i",$agent,$matches) ) {
				$this->browser_version = ($matches[2]);
				if( stripos($agent,'Series60') !== false || strpos($agent,'S60') !== false ) {
					$this->browser_name = ('Nokia S60 OSS Browser');
				} else {
					$this->browser_name = ('Nokia Browser');
				}
				$this->mobile = true ;
	
			/**
			 * Determine if the browser is Android or not
			 */
			} else if( stripos($agent,'Android') !== false ) {
				$aresult = explode('/',stristr($agent,'Version'));
				if( isset($aresult[1]) ) {
					$aversion = explode(' ',$aresult[1]);
					$this->browser_version = ($aversion[0]);
				} else {
					$this->browser_version = "";
				}
				$this->mobile = true ;
				$this->browser_name = ("Android Browser");
			
			/**
			 * Determine if the browser is Firefox or not 
			 */
			} else if ( preg_match("/Firefox[\/ \(]([^ ;\)]+)/i",$agent,$matches) ) {
					$this->browser_version = ($matches[1]);
					$this->browser_name = ('Firefox');
			} else if ( preg_match("/Firefox$/i",$agent,$matches) ) {
					$this->browser_version = ("");
					$this->browser_name = ("Firefox");
				
			/**
			 * Determine if the browser is Safari or not
			 */
			} else if( stripos($agent,'Safari') !== false && stripos($agent,'iPhone') === false && stripos($agent,'iPod') === false ) {
				$aresult = explode('/',stristr($agent,'Version'));
				if( isset($aresult[1]) ) {
					$aversion = explode(' ',$aresult[1]);
					$this->browser_version = ($aversion[0]);
				} else {
					$this->browser_version = ("?");
				}
				$this->browser_name = ('Safari');
	
			/**
			 * Determine if the browser is iPhone or not 
			 */
			} else if( stripos($agent,'iPhone') !== false ) {
				$aresult = explode('/',stristr($agent,'Version'));
				if( isset($aresult[1]) ) {
					$aversion = explode(' ',$aresult[1]);
					$this->browser_version = ($aversion[0]);
				} else {
					$this->browser_version = "";
				}
				$this->mobile = true ;
				$this->browser_name = ('Safari');
	
			/**
			 * Determine if the browser is iPod or not
			 */
			} else if( stripos($agent,'iPad') !== false ) {
				$aresult = explode('/',stristr($agent,'Version'));
				if( isset($aresult[1]) ) {
					$aversion = explode(' ',$aresult[1]);
					$this->browser_version = ($aversion[0]);
				} else {
					$this->browser_version = ("");
				}
				$this->mobile = true ;
				$this->browser_name = ("Safari");
	
			/**
			 * Determine if the browser is iPod or not
			 */
			} else if( stripos($agent,'iPod') !== false ) {
				$aresult = explode('/',stristr($agent,'Version'));
				if( isset($aresult[1]) ) {
					$aversion = explode(' ',$aresult[1]);
					$this->browser_version = ($aversion[0]);
				} else {
					$this->browser_version = "";
				}
				$this->mobile = true ;
				$this->browser_name = ("Safari");
	
			/**
			 * Determine if the browser is Mozilla or not
			 */
			} else if( stripos($agent,'mozilla') !== false  && preg_match('/rv:[0-9].[0-9][a-b]?/i',$agent) && stripos($agent,'netscape') === false) {
				$aversion = explode(' ',stristr($agent,'rv:'));
				preg_match('/rv:[0-9].[0-9][a-b]?/i',$agent,$aversion);
				$this->browser_version = (str_replace('rv:','',$aversion[0]));
				$this->browser_name = ('Mozilla');
			} else if( stripos($agent,'mozilla') !== false && preg_match('/rv:[0-9]\.[0-9]/i',$agent) && stripos($agent,'netscape') === false ) {
				$aversion = explode('',stristr($agent,'rv:'));
				$this->browser_version = (str_replace('rv:','',$aversion[0]));
				$this->browser_name = ('Mozilla');
			} else if( stripos($agent,'mozilla') !== false  && preg_match('/mozilla\/([^ ]*)/i',$agent,$matches) && stripos($agent,'netscape') === false ) {
				$this->browser_version = ($matches[1]);
				$this->browser_name = ('Mozilla');
			}
			
			/**
			 * Determine the user's platform
			 */
			 
			if( stripos($agent, 'ipad') !== false ) {
				$this->platform_name = 'iPad';
				if( preg_match("/iPhone OS ([^; ]*)[; ]/i",$agent,$matches) ) {
					$this->platform_version = str_replace("_", ".", $matches[1]);
				} else if( preg_match("/CPU OS ([^; ]*)[; ]/i",$agent,$matches) ) {
					$this->platform_version = str_replace("_", ".", $matches[1]);
				}
			} else if( stripos($agent, 'ipod') !== false ) {
				$this->platform_name = 'iPod';
				if( preg_match("/iPhone OS ([^; ]*)[; ]/i",$agent,$matches) ) {
					$this->platform_version = str_replace("_", ".", $matches[1]);
				} else if( preg_match("/CPU OS ([^; ]*)[; ]/i",$agent,$matches) ) {
					$this->platform_version = str_replace("_", ".", $matches[1]);
				}
			} else if( stripos($agent, 'iphone') !== false ) {
				$this->platform_name = 'iPhone';
				if( preg_match("/iPhone OS ([^; ]*)[; ]/i",$agent,$matches) ) {
					$this->platform_version = str_replace("_", ".", $matches[1]);
				} else if( preg_match("/CPU OS ([^; ]*)[; ]/i",$agent,$matches) ) {
					$this->platform_version = str_replace("_", ".", $matches[1]);
				}
			} else if( stripos($agent, 'mac') !== false ) {
				$this->platform_name = 'Macintosh';
				if( preg_match("/Mac OS X ([^; \)]*)[; \)]/i",$agent,$matches) ) {
					$this->platform_version = str_replace("_", ".", $matches[1]);
				}
			} else if( stripos($agent, 'android') !== false ) {
				$this->platform_name = 'Android';
				if( preg_match("/Android ([^ ;]*);/i",$agent,$matches) ) {
					$this->platform_version = $matches[1];
				} 
			} else if( stripos($agent, 'nokia') !== false ) {
				$this->platform_name = 'Nokia';
			} else if( stripos($agent, 'blackBerry') !== false ) {
				$this->platform_name = 'Blackberry';
			} else if( stripos($agent,'freebsd') !== false ) {
				$this->platform_name = 'FreeBSD';
			} else if( stripos($agent,'openbsd') !== false ) {
				$this->platform_name = 'OpenBSD';
			} else if( stripos($agent,'netbsd') !== false ) {
				$this->platform_name = 'NetBSD';
			} else if( stripos($agent, 'opensolaris') !== false ) {
				$this->platform_name = 'OpenSolaris';
			} else if( stripos($agent, 'sunos') !== false ) {
				$this->platform_name = 'SunOS';
			} else if( stripos($agent, 'os\/2') !== false ) {
				$this->platform_name = 'OS2' ; 
			} else if( stripos($agent, 'beos') !== false ) {
				$this->platform_name = 'BeOS' ; 
			} else if( stripos($agent,'mspie') !== false || stripos($agent,'pocket') !== false ) {
				$this->platform_name = 'Windows';
				$this->platform_version = 'ME' ; 
			} else if( stripos($agent, 'win') !== false ) {
				$this->platform_name = 'Windows';
				if (stripos($agent, 'win16') !== false ) {
				} else if ( (stripos($agent, 'windows 95') !== false )||(stripos($agent, 'windows_95') !== false )||(stripos($agent, 'win95') !== false ) ) { 
					$this->platform_version = '95';
				} else if ( (stripos($agent, 'windows 98') !== false )||(stripos($agent, 'win98') !== false ) ) { 
					$this->platform_version = '98';
				} else if ( (stripos($agent, 'windows nt 5.0') !== false )||(stripos($agent, 'windows 2000') !== false ) ) { 
					$this->platform_version = '2000';
				} else if ( (stripos($agent, 'windows nt 5.1') !== false )||(stripos($agent, 'windows xp') !== false ) ) { 
					$this->platform_version = 'XP';
				} else if (stripos($agent, 'windows nt 5.2') !== false ) { 
					$this->platform_version = 'Server 2003' ; 
				} else if (stripos($agent, 'windows nt 6.0') !== false ) { 
					$this->platform_version = 'Vista' ; 
				} else if (stripos($agent, 'windows nt 6.1') !== false ) { 
					$this->platform_version = '7' ; 
				} else if (stripos($agent, 'windows nt 6.2') !== false ) { 
					$this->platform_version = '8' ; 
				} else if ( (stripos($agent, 'windows nt 4.0') !== false )||(stripos($agent, 'winnt4.0') !== false )||(stripos($agent, 'winnt') !== false )||(stripos($agent, 'windows nt') !== false ) ) { 
					$this->platform_version = 'NT 4.0' ; 
				} 
			} else if (( stripos($agent, 'linux') !== false )||( stripos($agent, 'X11') !== false )) {
				$this->platform_name = 'Linux';
			} else {
				$this->platform_name = 'Other';
			}
		}
		
		/** ====================================================================================================================================================
		* Get the browser name
		*
		* @return string The browser name
		*/
		function getBrowserName() {		
			return $this->browser_name ;  
		}
		/** ====================================================================================================================================================
		* Get the browser version
		*
		* @return string The browser version
		*/
		function getBrowserVersion() {
			return $this->browser_version ;  
		}
		/** ====================================================================================================================================================
		* Get the platform name
		*
		* @return string The platform name
		*/
		function getPlatformName() {
			return $this->platform_name ;  
		}			
		/** ====================================================================================================================================================
		* Get the platform version
		*
		* @return string The platform version
		*/
		function getPlatformVersion() {
			return $this->platform_version ;  
		}		
	}
}


?>