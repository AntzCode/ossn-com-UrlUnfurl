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

namespace UrlUnfurl\HtmlDocument;

use UrlUnfurl\UrlUnfurl;
use UrlUnfurl\Database;
use UrlUnfurl\Url;
use UrlUnfurl\HtmlDocument\Fetcher\ImageSettings;

class Fetcher
{
	const TYPE_ORIGINAL = 'original';
	const TYPE_LARGE = 'large';
	const TYPE_TEMPORARY = 'tmp';

	public static function getImageStoragePath($filename, $type){
		switch($type){
			case self::TYPE_ORIGINAL:
				return UrlUnfurl::getStorageRootPath().self::TYPE_ORIGINAL.'/'.$filename;
			case self::TYPE_LARGE:
				return UrlUnfurl::getStorageRootPath().self::TYPE_LARGE.'/'.$filename;
			case self::TYPE_TEMPORARY:
				return UrlUnfurl::getStorageRootPath().self::TYPE_TEMPORARY.'/'.$filename;
		}
	}

	public static function deleteImage($imageFilename){
		$imageLargeStoreFilePath = self::getImageStoragePath($imageFilename, self::TYPE_LARGE);
		if(file_exists($imageLargeStoreFilePath)){
			unlink($imageLargeStoreFilePath);
		}
		$imageOriginalStoreFilePath = self::getImageStoragePath($imageFilename, self::TYPE_ORIGINAL);
		if(file_exists($imageOriginalStoreFilePath)){
			unlink($imageOriginalStoreFilePath);
		}
		$imageTempStoreFilePath = self::getImageStoragePath($imageFilename, self::TYPE_TEMPORARY);
		if(file_exists($imageTempStoreFilePath)){
			unlink($imageTempStoreFilePath);
		}
	}

	public static function getFileExtension($filename){
		if(false === strstr($filename, '.')){
			return '';
		}
		$filenameParts = explode('?', $filename);
		return '.'.preg_replace('/^.*\.([a-zA-Z0-9]*)$/', '$1', $filenameParts[0]);
	}

	public static function downloadHtml(Url $url){

		if(in_array('curl', get_loaded_extensions())){
			$ch = curl_init($url->url);
			$ua = UrlUnfurl::getSettings('httpUserAgentHtml');
			curl_setopt($ch, CURLOPT_USERAGENT, $ua);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
			curl_setopt($ch, CURLOPT_TIMEOUT, 45); //timeout in seconds
			curl_setopt($ch, CURLOPT_HEADER  , 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$response = curl_exec($ch);
			$httpCode = (string) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$headers = substr($response, 0, $headerSize);
			$html = substr($response, $headerSize);
			curl_close($ch);

		}else{

			if($html = file_get_contents($url->url)){
				$httpCode = (empty($html) ? '500' : '200');
			}else{
				$httpCode = '500';
			}
			$headers = '';

		}

		return (object) [
			'httpCode' => $httpCode,
			'headers' => $headers,
			'html' => $html
		];

	}

	public static function downloadImage($imageUrl, Url $url){

		// normalise the url
		if(strtolower(substr($imageUrl, 0, 4)) !== 'http'){

			if(substr($imageUrl, 0, 2) == '//'){

				$imageUrl = $url->urlDecoded->scheme.':'.$imageUrl;

			}else{

				if(substr($imageUrl, 0, 1) === '/'){
					// from the site root
					$imageUrl = $url->urlDecoded->scheme.'://'.$url->urlDecoded->host.$imageUrl;

				}else{
					// relative to the path

					$originFileExt = self::getFileExtension(basename($url->urlDecoded->path));

					if(empty($originFileExt)){
						// treat the origin as a url to a directory
						$imageUrl = $url->urlDecoded->scheme.'://'.$url->urlDecoded->host.'/'.rtrim($url->urlDecoded->path, '/').'/'.$imageUrl;
					}else{
						// treat the origin as a url to a file
						$imageUrl = dirname($url->urlDecoded->scheme.'://'.$url->urlDecoded->host.'/'.$url->urlDecoded->path).'/'.$imageUrl;

					}
				}
			}
		}

		$imageExt = self::getFileExtension($imageUrl);
		$imageGuid = Database::generateUniqueId();
		$imageTempFilePath = self::getImageStoragePath($imageGuid.$imageExt, self::TYPE_TEMPORARY);

		if(!is_dir(dirname($imageTempFilePath))){
			mkdir(dirname($imageTempFilePath), 0755, true);
		}

		if(in_array('curl', get_loaded_extensions())){
			$ch = curl_init();
			$ua = UrlUnfurl::getSettings('httpUserAgentImages');
			curl_setopt($ch, CURLOPT_USERAGENT, $ua);
			curl_setopt($ch, CURLOPT_URL, $imageUrl);
			curl_setopt($ch, CURLOPT_VERBOSE, 0);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
			curl_setopt($ch, CURLOPT_TIMEOUT, 45); //timeout in seconds
			curl_setopt($ch, CURLOPT_HEADER  , 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_AUTOREFERER, 0);
			curl_setopt($ch, CURLOPT_REFERER, $url->urlDecoded->scheme.'://'.$url->urlDecoded->host);
			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			$fc = curl_exec($ch);
			curl_close($ch);

			$fp = fopen($imageTempFilePath, 'wb');
			fwrite($fp, $fc);
			fclose($fp);

		}else{
			file_put_contents($imageTempFilePath, file_get_contents($imageUrl));
		}

		if(!file_exists($imageTempFilePath)){
			return false;
		}

		return (object) [
			'guid' => $imageGuid,
			'filePath' => $imageTempFilePath
		];

	}

	public static function resizeAndMoveImage($originalImageFilepath, $newImageGuid, ImageSettings $settings){
		$imageExt = self::getFileExtension($originalImageFilepath);

		$imageOriginalStoreFilePath = self::getImageStoragePath($newImageGuid.$imageExt, self::TYPE_ORIGINAL);
		$imageLargeStoreFilePath = self::getImageStoragePath($newImageGuid.$imageExt, self::TYPE_LARGE);

		if(!is_dir(dirname($imageOriginalStoreFilePath))){
			mkdir(dirname($imageOriginalStoreFilePath), 0755, true);
		}

		if(!is_dir(dirname($imageLargeStoreFilePath))){
			mkdir(dirname($imageLargeStoreFilePath), 0755, true);
		}

		$originalFilesize = filesize($originalImageFilepath);
		$originalImageSize = getimagesize($originalImageFilepath);

		if($originalImageSize[0] < $settings->imageMinWidth || $originalImageSize[1] < $settings->imageMinHeight){
			return false;
		}

		if(!in_array($originalImageSize['mime'], $settings->validMimes)){
			return false;
		}

		if($originalImageSize[0] > $settings->imageWidth || $originalImageSize[1] > $settings->imageHeight){
			// resize and move uploaded file
			copy($originalImageFilepath, $imageOriginalStoreFilePath);
			$resized = self::resizeImage($originalImageFilepath, $settings->imageWidth, $settings->imageHeight);
			file_put_contents($imageLargeStoreFilePath, $resized);
			unlink($originalImageFilepath);
		}else{
			rename($originalImageFilepath, $imageLargeStoreFilePath);
			$originalFilesize = null;
			$originalImageSize = null;
		}

		$imageSize = getimagesize($imageLargeStoreFilePath);
		$filesize = filesize($imageLargeStoreFilePath);

		$imageInfo = new \stdClass();
		$imageInfo->guid = $newImageGuid;
		$imageInfo->filename = $newImageGuid.$imageExt;
		$imageInfo->imageSize = $imageSize;
		$imageInfo->filesize = $filesize;
		$imageInfo->originalImageSize = $originalImageSize;
		$imageInfo->originalFilesize = $originalFilesize;

		return $imageInfo;

	}

	protected static function resizeImage($filepath, $width, $height){
		return \ossn_resize_image($filepath, $width, $height);
	}

}