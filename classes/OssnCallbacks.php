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

use UrlUnfurl\Database;
use UrlUnfurl\Result;
use UrlUnfurl\Controller;

class UrlUnfurl_Callbacks
{

	public static function hookWallTemplateUser($hook, $type, $return, &$params){
		return $return.ossn_plugin_view("UrlUnfurl/wall/post-item", $params);
	}

	public static function eventWallPostCreated($event, $action, $params){

		$postId = $params['guid'];

		$urlUnfurlId = input('urlunfurl_url_id');
		$urlUnfurlImageGuid = input('urlunfurl_url_image');

		if(empty($urlUnfurlId)){
			return;
		}

		if($urlObject = Result::getById($urlUnfurlId)){
			// valid url

			$found = false;
			foreach($urlObject->getImages() as $image){
				if($image->guid === $urlUnfurlImageGuid){
					$found = true;
					break;
				};
			}

			if(!$found){
				// image not found
				return;
			}

		}

		$id = Database::insert('_post_url', [
			'post_id' => $postId,
			'url_id' => $urlUnfurlId,
			'image' => $urlUnfurlImageGuid
		]);

	}

	public function eventWallPostEdited($event, $action, $params){

		$postId = $params['guid'];

		$urlUnfurlId = input('urlunfurl_url_id');
		$urlUnfurlImageId = input('urlunfurl_image_id');

		if(empty($urlUnfurlId)){
			return;
		}

	}

	public function eventPostDeleted($event, $action, $params){
		$postId = $params;
	}

}


