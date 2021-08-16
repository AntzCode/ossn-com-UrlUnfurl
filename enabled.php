<?php
###################################################################################
##               Open Source Social Network (Component/Extension)                ##
##          ~ Unfurl URL's for a preview when posting links on a wall ~          ##
##                                                                               ##
##    @package   UrlUnfurl Component                                             ##
##    @author    AntzCode Ltd                                                    ##
##    @copyright (C) AntzCode Ltd                                                ##
##    @link      https://github.com/AntzCode/ossn-com-UrlUnfurl                  ##
##    @license   GPLv3 https://raw.githubusercontent.com/AntzCode/               ##
##                       ossn-com-UrlUnfurl/main/LICENSE                         ##
##                                                                               ##
###################################################################################

/**
 * This file is executed after the component is enabled by an Administrator.
 * It ensures that the database is ready to go.
 */

$installDir = dirname(__FILE__).DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR;

$comId = 'UrlUnfurl';

define($comId.'_COM_ENABLED', '1');

$comXml = $this->getCom($comId);
$comSettings = $this->getComSettings($comId);

if(!is_object($comSettings) || !property_exists($comSettings, 'version')){
	// not installed
	require($installDir.'install.php');
}

// run the upgrades
if(version_compare($comSettings->version, $comXml->version) < 0){
	// needs an upgrade
	$upgrades = [];

	$files = scandir($installDir);

	foreach($files as $file){

		if(in_array($file, ['.', '..'])){
			// skip these directories
			continue;
		}

		if(is_dir($installDir.$file) && preg_match('/^[0-9]*\.[0-9]*\.[0-9]*[0-9a-zA-Z\-_]*$/', $file)){
			if(version_compare($comSettings->version, $file) < 0){
				$upgrades[] = $file;
			}
		}

	}

	if(count($upgrades) > 0){
		sort($upgrades);
		foreach($upgrades as $upgrade){
			require($installDir.$upgrade.DIRECTORY_SEPARATOR.'upgrade.php');
		}
	}

}

