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

namespace UrlUnfurl;

use PDO;
use PDOException;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;

class Database
{
	protected static $db;
	public static $tablePrefix;

	public static function getDb(){
		if(!self::$db instanceof PDO){
			self::$tablePrefix = UrlUnfurl::$dbTablePrefix;
			self::$db = UrlUnfurl::getPdoHandle();
		}
		return self::$db;
	}

	public static function generateUniqueId($length=32, $rand=''){
		$dateTime = new DateTime('now', new DateTimeZone('UTC'));
		$uniqueId = 'uuUid'.$dateTime->format('YmdHisu').md5(rand(0, 99999).'/'.$rand.'/'.date('Hisd').__FILE__.rand(rand(3,6), rand(12,70)).(rand(8,12)*rand(70,90)));
		return substr($uniqueId, 0, $length);
	}

	public static function getOneBy($tablename, $searchValue, $cols=null){

		if(!is_array($searchValue)){
			// by default, it will get one by id
			$searchValue = ['id' => $searchValue];
		}

		$sorting = [array_keys($searchValue)[0] => 'ASC'];
		$row = self::getBy($tablename, $searchValue, $sorting, 1, $cols);

		if($row && count($row) > 0){
			// returns a row by id
			return $row[0];

		}else{
			return false;
		}

	}

	public static function decodeLimit($limit){

		if(empty($limit)){
			throw new InvalidArgumentException('Limit must not be empty.');
			return;
		}

		if(is_array($limit)){

			if(count($limit) > 2){
				throw new InvalidArgumentException('Limit must define only the limit, or the limit and offset. More than two values were given.');
				return;
			}

			if(count($limit) < 2){

				$offsetInt = 0;
				$limitInt = (int) $limit[0];

			}else{

				$offsetInt = (int) $limit[0];
				$limitInt = (int) $limit[1];

			}

		}else{
			$offsetInt = 0;
			$limitInt = (int) $limit;
		}

		if($limitInt <= 0){
			throw new InvalidArgumentException('Limit must be an integer greater than 0');
			return;
		}

		if($offsetInt < 0){
			throw new InvalidArgumentException('Offset must be an integer not less than 0');
			return;
		}

		return [
			'offset' => $offsetInt,
			'limit' => $limitInt
		];

	}

	public static function getBy($tablename, $searchValue, $sorting, $limit, $cols=null){

		if(is_null($cols)){
			$cols = ['*'];
		}

		$idNames = [];
		foreach($searchValue as $k => $v){
			$idNames[] = '`'.$k.'` = :'.$k;
		}

		$newParams = [];

		foreach($searchValue as $k => $v){
			$newParams[':'.$k] = $v;
		}

		$sortingNames = [];
		foreach($sorting as $k => $v){
			$sortingNames[] = '`'.$k.'` '.$v;
		}

		$sureLimit = self::decodeLimit($limit);

		$db = self::getDb();
		$tablePrefix = self::$tablePrefix;

		$q = "SELECT ".implode(', ', $cols)." FROM {$tablePrefix}{$tablename} WHERE ".implode(' AND ', $idNames);
		$q .= " ORDER BY ".implode(', ', $sortingNames)." LIMIT {$sureLimit['limit']} OFFSET {$sureLimit['offset']} ";

		$sth = $db->prepare($q);
		$sth->execute($newParams);

		$toReturn = $sth->fetchAll(PDO::FETCH_OBJ);

		array_walk($toReturn, ['\UrlUnfurl\Database', 'rowKeysToCamelCase']);

		return $toReturn;

	}

	public static function insert($tablename, $params)
	{
		$db = self::getDb();
		$tablePrefix = self::$tablePrefix;

		array_walk($params, ['\UrlUnfurl\Database', 'rowKeysFromCamelCase']);

		$keys = array_keys($params);
		array_walk($keys, function (&$v) {
			$v = '`' . $v . '`';
		});

		$values = array_keys($params);
		array_walk($values, function (&$v) {
			$v = ':' . $v;
		});

		$newParams = $params;
		array_walk($newParams, function ($v, &$k) {
			$k = ':' . $k;
		});

		try{
			$q = "INSERT INTO {$tablePrefix}{$tablename} (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ")";

			$sth = $db->prepare($q);

			try {
				$sth->execute($newParams);
				return $db->lastInsertId();
			} catch (PDOException $e) {
				throw $e;
				return false;
			}

		}catch(PDOException $e){
			throw $e;
			return false;
		}

	}


	public static function delete($tablename, $wheres, $sorting, $limit){

		$wherePairs = [];
		foreach($wheres as $k => $v){
			$wherePairs[] = '`'.$k.'` = :_'.$k;
			$values[':_'.$k] = $v;
		}

		$sortingNames = [];
		foreach($sorting as $k => $v){
			$sortingNames[] = '`'.$k.'` '.$v;
		}

		$sureLimit = self::decodeLimit($limit);

		if($sureLimit['offset'] > 0){
			throw new InvalidArgumentException('Cannot specify an offset value in the limit for delete operation');
		}

		try{

			$db = self::getDb();
			$tablePrefix = self::$tablePrefix;

			$q = "DELETE FROM {$tablePrefix}{$tablename} WHERE ".implode(' AND ', $wherePairs);
			$q .= " ORDER BY ".implode(', ', $sortingNames)." LIMIT {$sureLimit['limit']} ";

			$sth = $db->prepare($q);

			try {
				$sth->execute($values);
				return $db->lastInsertId();

			} catch (PDOException $e) {
				throw $e;
				return false;
			}

		}catch(PDOException $e){
			throw $e;
			return false;
		}

	}

	public static function update($tablename, $whats, $wheres){

		$db = self::getDb();
		$tablePrefix = self::$tablePrefix;

		array_walk($whats, ['\UrlUnfurl\Database', 'rowKeysFromCamelCase']);
		array_walk($wheres, ['\UrlUnfurl\Database', 'rowKeysFromCamelCase']);

		$whatPairs = [];
		$values = [];
		foreach($whats as $k => $v){
			$whatPairs[] = '`'.$k.'` = :'.$k;
			$values[':'.$k] = $v;
		}

		$wherePairs = [];
		foreach($wheres as $k => $v){
			$wherePairs[] = '`'.$k.'` = :_'.$k;
			$values[':_'.$k] = $v;
		}

		try{

			$q = "UPDATE {$tablePrefix}{$tablename} SET " . implode(', ', $whatPairs) . " WHERE ".implode(' AND ', $wherePairs);

			$sth = $db->prepare($q);

			try {
				$sth->execute($values);
				return $db->lastInsertId();
			} catch (PDOException $e) {
				throw $e;
				return false;
			}

		}catch(PDOException $e){

			throw $e;
			return false;

		}

	}

	public static function rowKeysFromCamelCase($obj, $capitalizeFirstCharacter=false){

		if(is_array($obj)){

			foreach($obj as $k => $v){
				$obj[self::stringFromCamelCase($k)] = $v;
				unset($obj[$k]);
			}

		}else if(is_object($obj)){

			foreach(get_object_vars($obj) as $k => $v){
				$k2 = self::stringFromCamelCase($k);
				$obj->{$k2} = $v;
				unset($obj->{$k});
			}

		}else if(is_scalar($obj)){
			$obj = self::stringFromCamelCase($obj, $capitalizeFirstCharacter);
		}

		return $obj;

	}

	// 20210810 from https://www.codegrepper.com/code-examples/php/php+change+camelcase+to+underscore
	public static function stringFromCamelCase($input) {
		$pattern = '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!';
		preg_match_all($pattern, $input, $matches);
		$ret = $matches[0];
		foreach ($ret as &$match) {
			$match = $match == strtoupper($match) ?
				strtolower($match) :
				lcfirst($match);
		}
		return implode('_', $ret);
	}

	public static function rowKeysToCamelCase($obj, $capitalizeFirstCharacter=false){

		if(is_array($obj)){

			foreach($obj as $k => $v){
				unset($obj[$k]);
				$obj[self::stringToCamelCase($k)] = $v;
			}

		}else if(is_object($obj)){

			foreach(get_object_vars($obj) as $k => $v){
				$k2 = self::stringToCamelCase($k);
				unset($obj->{$k});
				$obj->{$k2} = $v;
			}

		}else if(is_scalar($obj)){

			$obj = self::stringToCamelCase($obj);

		}

		return $obj;

	}

	// 20210810 from https://stackoverflow.com/questions/2791998/convert-dashes-to-camelcase-in-php
	public static function stringToCamelCase($string, $capitalizeFirstCharacter = false)
	{
		$str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
		if (!$capitalizeFirstCharacter) {
			$str[0] = strtolower($str[0]);
		}
		return $str;
	}

}
