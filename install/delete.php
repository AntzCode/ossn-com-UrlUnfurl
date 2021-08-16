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

$comId = 'UrlUnfurl';
$tablePrefix = 'ossn_'.strtolower($comId);

if(!defined($comId.'_COM_DELETE')){
	exit('Needs to run from within context of /Components/'.$comId.'/delete.php');
}

$query = <<<SQL
IF EXISTS(
	  SELECT *
	  FROM INFORMATION_SCHEMA.STATISTICS
	  WHERE INDEX_SCHEMA = DATABASE()
			AND TABLE_NAME='{$tablePrefix}_url_object'
			AND INDEX_NAME = 'url_id_index')
THEN
	ALTER TABLE `{$tablePrefix}_url_object` DROP FOREIGN KEY `fk_url_object_url_id`;
	ALTER TABLE `{$tablePrefix}_url_object` DROP INDEX `url_id_index` ;
END IF;
SQL;

try{
	$this->statement($query);
	$this->execute();
}catch(Exception $e){

}

$query = <<<SQL
DROP TABLE IF EXISTS `{$tablePrefix}_url`; 
SQL;

$this->statement($query);
$this->execute();

$query = <<<SQL
DROP TABLE IF EXISTS `{$tablePrefix}_url_object`; 
SQL;

$this->statement($query);
$this->execute();

$query = <<<SQL
DROP TABLE IF EXISTS `{$tablePrefix}_url_queue`; 
SQL;

$this->statement($query);
$this->execute();

$query = <<<SQL
DROP TABLE IF EXISTS `{$tablePrefix}_post_url`; 
SQL;

$this->statement($query);
$this->execute();
