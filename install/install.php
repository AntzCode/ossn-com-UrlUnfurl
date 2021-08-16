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

if(!defined($comId.'_COM_ENABLED')){
	exit('Needs to run from within context of /Components/'.$comId.'/enabled.php');
}

$defaultSettings = (object) [
	'enable_imagick' => 1,
	'image_width' => 700,
	'image_height' => 350,
	'image_min_width' => 395,
	'image_min_height' => 235,
	'max_url_num_images' => 8,
	'derived_description_min_length' => 192,
	'derived_description_max_length' => 384,
	'http_user_agent_html' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.16 (KHTML, like Gecko) Chrome/24.0.1304.0 Safari/537.16',
	'http_user_agent_images' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.16 (KHTML, like Gecko) Chrome/24.0.1304.0 Safari/537.16',
];

if(!isset($comSettings) || !is_object($comSettings)){
	$comSettings = new stdClass();
}

foreach($defaultSettings as $k => $v){
	if(!property_exists($comSettings, $k)){
		// we will not overwrite existing settings, if any
		$comSettings->$k = $v;
	}
}

$this->setSettings($comId, (array) $comSettings);

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
CREATE TABLE `{$tablePrefix}_url` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `time_created` DATETIME NOT NULL DEFAULT NOW(),
    `title` VARCHAR(120) NULL,
    `type` VARCHAR(12) NOT NULL,
    `http_status` VARCHAR(3) NULL,
    `domain` VARCHAR(80) NOT NULL,
    `path` VARCHAR(255) NOT NULL,
    `image` VARCHAR(80) NULL,
    `description` TEXT NULL,
    `language` VARCHAR(5) NOT NULL DEFAULT 'en_US',
    `data` MEDIUMTEXT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `url_index` (`path` (8), `domain` (8))
) ENGINE = INNODB;
SQL;

$this->statement($query);
$this->execute();

$query = <<<SQL
DROP TABLE IF EXISTS `{$tablePrefix}_url_object`; 
SQL;

$this->statement($query);
$this->execute();

$query = <<<SQL
CREATE TABLE `{$tablePrefix}_url_object` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `url_id` INT UNSIGNED NOT NULL,
    `time_created` DATETIME NOT NULL DEFAULT NOW(),
    `type` VARCHAR(12) NOT NULL,
    `sorting` INT UNSIGNED NOT NULL,
    `status` TINYINT(1),
    `data`  MEDIUMTEXT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `url_id_index` (`url_id`)
) ENGINE = MYISAM;
SQL;

$res = $this->statement($query);
$res2 = $this->execute();

$query = <<<SQL
ALTER TABLE `{$tablePrefix}_url_object`
	ADD CONSTRAINT `fk_url_object_url_id` FOREIGN KEY
	    `url_id_index` (`url_id`)
		REFERENCES `{$tablePrefix}_url` (`id`)
        ON DELETE CASCADE
SQL;

$this->statement($query);
$this->execute();

$comSettings->version = '1.0.0';
$this->setSettings($comId, (array) $comSettings);


$query = <<<SQL
DROP TABLE IF EXISTS `{$tablePrefix}_url_queue`; 
SQL;

$this->statement($query);
$this->execute();

$query = <<<SQL
CREATE TABLE `{$tablePrefix}_url_queue` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `url` VARCHAR(255) NOT NULL,
    `status` VARCHAR(8) NOT NULL,
    `process_id` VARCHAR(60) NOT NULL,
    `time_created` DATETIME NOT NULL DEFAULT NOW(),
    `data`  MEDIUMTEXT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = MYISAM;
SQL;

$res = $this->statement($query);
$res2 = $this->execute();

$query = <<<SQL
DROP TABLE IF EXISTS `{$tablePrefix}_post_url`; 
SQL;

$this->statement($query);
$this->execute();

$query = <<<SQL
CREATE TABLE `{$tablePrefix}_post_url` (
    `post_id` INT NOT NULL,
	`url_id` VARCHAR(255) NOT NULL,
	`image` VARCHAR(60) NULL,
	`time_created` DATETIME NOT NULL DEFAULT NOW(),
	PRIMARY KEY (`post_id`)
) ENGINE = MYISAM;
SQL;

$res = $this->statement($query);
$res2 = $this->execute();
