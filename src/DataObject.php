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

abstract class DataObject
{

	public function __set($k, $v){
		if(property_exists($this, $k)){
			$this->$k = $v;
		}
	}

	public function __get($k){
		if(property_exists($this, $k)){
			return $this->$k;
		}
		return null;
	}

	public function preJsonEncode($data){
		return $data;
	}

	public function toJson(){
		$data = new \stdClass();
		foreach(get_object_vars($this) as $k){
			$data->$k = $this->$k;
		}
		$data = $this->preJsonEncode($data);
		return json_encode($data);
	}

}
