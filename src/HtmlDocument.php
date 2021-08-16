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

use UrlUnfurl\UrlUnfurl;
use UrlUnfurl\Url;
use UrlUnfurl\Result\Type;
use UrlUnfurl\HtmlDocument\Fetcher;
use UrlUnfurl\HtmlDocument\Fetcher\ImageSettings;
use DOMDocument;

class HtmlDocument
{
	protected $httpCode;
	protected $headers;
	protected $html;

	public function getHttpCode(){
		return $this->httpCode;
	}
	public function getHtml(){
		return $this->HtmlDocument;
	}
	public function getHeaders(){
		return $this->headers;
	}

	// 20210816 from https://www.php.net/manual/en/function.html-entity-decode.php#111859
	public static function decodeHtmlEntities($str) {
		$ret = html_entity_decode($str, ENT_COMPAT, 'UTF-8');
		$p2 = -1;
		for(;;) {
			$p = strpos($ret, '&#', $p2+1);
			if ($p === FALSE)
				break;
			$p2 = strpos($ret, ';', $p);
			if ($p2 === FALSE)
				break;

			if (substr($ret, $p+2, 1) == 'x')
				$char = hexdec(substr($ret, $p+3, $p2-$p-3));
			else
				$char = intval(substr($ret, $p+2, $p2-$p-2));

			//echo "$char\n";
			$newchar = iconv(
				'UCS-4', 'UTF-8',
				chr(($char>>24)&0xFF).chr(($char>>16)&0xFF).chr(($char>>8)&0xFF).chr($char&0xFF)
			);
			//echo "$newchar<$p<$p2<<\n";
			$ret = substr_replace($ret, $newchar, $p, 1+$p2-$p);
			$p2 = $p + strlen($newchar);
		}
		return $ret;
	}

	public function getTypeData($type, Url $url){

		$returnData = (object) [
			'title' => null,
			'description' => null,
			'image' => null,
			'data' => null
		];

		$imageSettings = new ImageSettings();
		$imageSettings->imageMinWidth = UrlUnfurl::getSettings('imageMinWidth');
		$imageSettings->imageMinHeight = UrlUnfurl::getSettings('imageMinHeight');
		$imageSettings->imageWidth = UrlUnfurl::getSettings('imageWidth');
		$imageSettings->imageHeight = UrlUnfurl::getSettings('imageHeight');

		switch($type){

			case Type::TYPE_FACEBOOK:

				$facebookMatches = [];
				$facebookTags = [];
				preg_match_all('/<meta.*("og:[^"]*").*content\s*=\s*("[^"]*")[^>]*\>/Um', $this->html, $facebookMatches);

				foreach($facebookMatches[1] as $k => $name){
					$facebookTags[trim($name, '"')] = self::decodeHtmlEntities(trim($facebookMatches[2][$k], '"'));
				}

				$returnData->title = ($facebookTags['og:title'] ?? null);
				$returnData->description = ($facebookTags['og:description'] ?? null);
				$returnData->image = ($facebookTags['og:image'] ?? null);
				$returnData->data = (object) ['tags' => $facebookTags];

				if(!is_null($returnData->image)){

					$imageUrl = $returnData->image;

					if(!$downloadInfo = Fetcher::downloadImage($imageUrl, $url)){

						$returnData->image = null;

					}else{

						$imageGuid = $downloadInfo->guid;
						$imageTempFilePath = $downloadInfo->filePath;

						if(!$imageInfo = Fetcher::resizeAndMoveImage($imageTempFilePath, $imageGuid, $imageSettings)){

							$returnData->image = null;
							unlink($imageTempFilePath);

						}else{

							$returnData->image = $imageInfo->filename;
							$returnData->data->images = [$imageInfo];

						}
					}
				}

				break;

			case Type::TYPE_TWITTER:

				$twitterMatches = [];
				$twitterTags = [];
				preg_match_all('/<meta.*("twitter:[^"]*").*content\s*=\s*("[^"]*")[^>]*>/Um', $this->html, $twitterMatches);

				foreach($twitterMatches[1] as $k => $name){
					$twitterTags[trim($name, '"')] = html_entity_decode(trim($twitterMatches[2][$k], '"'));
				}

				$returnData->title = ($facebookTags['twitter:title'] ?? null);
				$returnData->description = ($facebookTags['twitter:description'] ?? null);
				$returnData->image = ($facebookTags['twitter:image'] ?? null);
				$returnData->data = (object) ['tags' => $twitterTags];

				if(!is_null($returnData->image)){

					$imageUrl = $returnData->image;

					if(!$downloadInfo = Fetcher::downloadImage($imageUrl, $url)){

						$returnData->image = null;

					}else{

						$imageGuid = $downloadInfo->guid;
						$imageTempFilePath = $downloadInfo->filePath;

						if(!$imageInfo = Fetcher::resizeAndMoveImage($imageTempFilePath, $imageGuid, $imageSettings)){

							$returnData->image = null;
							unlink($imageTempFilePath);

						}else{

							$returnData->image = $imageInfo->filename;
							$returnData->data->images = [$imageInfo];

						}
					}
				}

				break;

			case Type::TYPE_DERIVED:

				$derivedContent = $this->deriveFromHtml($url);
				$returnData->image = (count($derivedContent->images) > 0
					? $derivedContent->images[0]->filename
					: null
				);
				$returnData->title = (empty($derivedContent->title) ? $url->urlDecoded->domain : $derivedContent->title);
				$returnData->description = (empty($derivedContent->description) ? null : $derivedContent->description);
				$returnData->data = $derivedContent;

				break;


		}

		return $returnData;
	}

	public function deriveFromHtml(Url $url){

		$pageTitle = '';

		$matches = [];
		preg_match_all('/<title>(.*?)<\/title>/', $this->html, $matches);

		if(array_key_exists(1, $matches) && array_key_exists(0, $matches) && !empty($matches[1][0])){

			$pageTitle = html_entity_decode($matches[1][0]);

		}else{

			preg_match_all('/<h1[^>]*?>(.*?)<\/h1>/', $this->html, $matches);

			if(array_key_exists(1, $matches) && array_key_exists(0, $matches)){
				$pageTitle = html_entity_decode($matches[1][0]);
			}

		}

		$pageDescription = '';

		$matches = [];
		preg_match_all('/<metas.*?name="description".*?content="([^"]*?)".*?>/', $this->html, $matches);

		if(array_key_exists(1, $matches) && array_key_exists(0, $matches) && !empty($matches[1][0])){

			$pageDescription = html_entity_decode($matches[1][0]);

		}

		if(empty($pageDescription)){
			// try for paragraphs
			try{
				$domDocument = new DOMDocument();
				@$domDocument->loadHtml($this->html);
				$paragraphs = $domDocument->getElementsByTagName('p');

				foreach($paragraphs as $paragraph){

					if($pText = $paragraph->nodeValue){

						if(strlen($pText) >= UrlUnfurl::getSettings('derivedDescriptionMinLength') && strlen($pText) <= UrlUnfurl::getSettings('derivedDescriptionMaxLength')){
							$pageDescription = html_entity_decode($pText);
							break;
						}
					}
				}

			}catch(Exception $e){

			}
		}

		$matches = [];

		preg_match_all('/(<img\s){1,1}.*?src\s*?=\s*?("[^"]*?")[^>]*?>/', $this->html, $matches);

		$images = [];
		foreach($matches[2] as $img){
			$images[] = trim($img, '"');
		}

		$counts = array_count_values($images);

		foreach($images as $k => $img){
			// remove any images that appear more than once on the page - they are probably icons or ads

			if($counts[$img] > 1){
				unset($images[$k]);
				continue;
			}

			// remove any images that don't have valid file extensions
			if(!in_array(strtolower(Fetcher::getFileExtension($img)), [
				'.jpg', '.jpeg', 'jfif', '.gif', '.png',// '.svg'
			])){
				unset($images[$k]);
			}

		}

		$images = array_values($images);
		$imagesDone = 0;

		// download the images so we can analyse them
		foreach($images as $k => $imageUrl){

			// limit the number of images, for speed and storage
			if($imagesDone >= UrlUnfurl::getSettings('maxUrlNumImages')){
				unset($images[$k]);
				continue;
			}

			if(!$downloadInfo = Fetcher::downloadImage($imageUrl, $url)){
				unset($images[$k]);
				continue;
			}

			$imageGuid = $downloadInfo->guid;
			$imageTempFilePath = $downloadInfo->filePath;

			$imageSettings = new ImageSettings();
			$imageSettings->imageMinWidth = UrlUnfurl::getSettings('imageMinWidth');
			$imageSettings->imageMinHeight = UrlUnfurl::getSettings('imageMinHeight');
			$imageSettings->imageWidth = UrlUnfurl::getSettings('imageWidth');
			$imageSettings->imageHeight = UrlUnfurl::getSettings('imageHeight');

			if(!$imageInfo = Fetcher::resizeAndMoveImage($imageTempFilePath, $imageGuid, $imageSettings)){
				unset($images[$k]);
				unlink($imageTempFilePath);
				continue;
			}

			$images[$k] = $imageInfo;

			$imagesDone++;

		}

		$images = array_values($images);

		return (object) [
			'title' => $pageTitle,
			'description' => $pageDescription,
			'images' => $images
		];

	}

	public static function createFromUrl(Url $url){

		ini_set('display_errors', 1);
		error_reporting(E_ALL);

		if(in_array('curl', get_loaded_extensions())){

			$ch = curl_init($url->url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
			curl_setopt($ch, CURLOPT_TIMEOUT, 45); //timeout in seconds
			curl_setopt($ch, CURLOPT_HEADER  , 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$response = curl_exec($ch);
			$httpCode = (string) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$headers = substr($response, 0, $headerSize);
			$html = substr($response, $headerSize);
			curl_close($ch);

		}else{

			$header = '';
			if($html = file_get_contents($url->url)){
				$httpCode = (empty($html) ? '500' : '200');
			}else{
				$httpCode = '500';
			}
			$headers = '';

		}

		$htmlDocument = new self();
		$htmlDocument->httpCode = $httpCode;
		$htmlDocument->headers = $headers;
		$htmlDocument->html = $html;

		return $htmlDocument;
	}

}
