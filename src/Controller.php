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

use UrlUnfurl\Url;
use UrlUnfurl\HtmlDocument;
use UrlUnfurl\Queue\Item;
use UrlUnfurl\Result\Type;
use UrlUnfurl\Result;
use UrlUnfurl\Result\Type\Error;

class Controller
{
	public static function getUrlData($url, $properties=null){

		if($result = self::doGetUrlData($url)){

			$responses = array();

			if($result instanceof Result){
				// we have valid info about the url from the database cache

				$responseData = new \stdClass();
				$responseData->url = $url;
				$responseData->queued = false;
				$responseData->decoded = $result->url->urlDecoded;
				$responseData->twitter = json_decode($result->twitter->toJson());
				$responseData->facebook = json_decode($result->facebook->toJson());
				$responseData->derived = json_decode($result->derived->toJson());
				$responseData->oembed = json_decode($result->oembed->toJson());

				if(is_null($result->twitter->getTitle())){
					$responseData->twitter = null;
				}else{
					$responseData->twitter->images = $result->twitter->getImages();
				}
				if(is_null($result->facebook->getTitle())){
					$responseData->facebook = null;
				}else{
					$responseData->facebook->images = $result->facebook->getImages();
				}
				if(is_null($result->derived->getTitle())){
					$responseData->derived = null;
				}else{
					$responseData->derived->images = $result->derived->getImages();
				}
				if(is_null($result->oembed->getTitle())){
					$responseData->oembed = null;
				}else{
					$responseData->oembed->images = $result->oembed->getImages();
				}
				unset(
					$responseData->twitter->data,
					$responseData->facebook->data,
					$responseData->derived->data,
					$responseData->oembed->data
				);
				if(is_null($responseData->twitter) && is_null($responseData->facebook) && is_null($responseData->oembed) && is_null($responseData->derived)){
					$responseData->valid = false;
				}else{
					$responseData->valid = true;
				}

				$responses[] = $responseData;

			}else if($result instanceof Item){
				// the url does not exist, so it was queued as a job and this is the process id
				$responseData = new \stdClass();
				$responseData->url = $url;
				$responseData->queued = true;
				$responseData->processId = $result->getProcessId();
				$responseData->status = $result->getStatus();
				$responses[] = $responseData;

			}else{
				// there was an error - could not process the url

			}

		}else{
			// there was an error - could not process the url

		}

		if(empty($responses)){
			$responseData = (object) [
				'url' => $url,
				'valid' => false,
				'queued' => false
			];
		}

		return $responseData;

	}

	protected static function doGetUrlData($url, $properties=null){

		$urlUnfurlUrl = new Url($url);

		if($urlUnfurlResult = Result::getByUrl($urlUnfurlUrl)){
			return $urlUnfurlResult;
		}else{
			// the record doesn't exist, need to create it
			return self::addUrlToQueue($url);
		}

	}

	public static function addUrlToQueue(string $url){

		$urlUnfurlUrl = new Url($url);

		$queueItem = Item::getOneByUrl($urlUnfurlUrl);

		if($queueItem instanceof Item) {
			return $queueItem;
		}else{
			// only inserts if url is not already in the queue
			return Item::createFromUrl($urlUnfurlUrl);
		}

	}

	public static function processQueue($processId=null){

		$responseData = self::doProcessQueue($processId);

		if($responseData instanceof Item){
			$displayResult = (object) [
				'url' => $responseData->getUrl(),
				'processId' => $responseData->getProcessId(),
				'queued' => $responseData->getIsQueued(),
				'status' => $responseData->getStatus()
			];
		}

		if($responseData instanceof Type){
			if(in_array($responseData->getType(), [
				Type::TYPE_FACEBOOK,
				Type::TYPE_TWITTER,
				Type::TYPE_DERIVED
			])){
				$status = 'success';
			}else{
				$status = 'fail';
			}

			$displayResult = (object) [
				'url' => $responseData->getUrl()->url,
				'processId' => $processId,
				'queued' => false,
				'status' => $status,
				'title' => $responseData->getTitle(),
				'description' => $responseData->getDescription(),
				'type' => $responseData->getType()
			];

		}

		return $displayResult;

	}

	protected static function doProcessQueue($processId=null){

		$queueItem = Item::getOneByProcessId($processId);

		if($queueItem instanceof Item){
			// we have something to process

			switch($queueItem->getStatus()){

				case Item::STATUS_NEW:
					// needs to be scraped

					$htmlDocument = HtmlDocument::createFromUrl($queueItem->getUrl());

					$queueItem->setHtmlDocument($htmlDocument);
					$queueItem->persist();

					return $queueItem;

				case Item::STATUS_SCRAPED:
					// has been scraped, interpret the results

					if(in_array($queueItem->getHtmlDocument()->getHttpCode(), ['200', '302'])){
						// ok response from the web server

						// extract the data from the page
						$pageInfo = Result::createFromHtml($queueItem->getHtmlDocument(), $queueItem->getUrl());

						$resultType = null;

						if($pageInfo->isValidType(Type::TYPE_FACEBOOK)){
							// use the facebook tags
							$resultType = $pageInfo->getType(Type::TYPE_FACEBOOK);

						}else if($pageInfo->isValidType(Type::TYPE_TWITTER)){
							// use the twitter one
							$resultType = $pageInfo->getType(Type::TYPE_TWITTER);

						}else if($pageInfo->isValidType(Type::TYPE_DERIVED)){
							// use the derived
							$resultType = $pageInfo->getType(Type::TYPE_DERIVED);

						}else{
							// can't make any use of this page
							$pageInfo->resetType(Type::TYPE_FACEBOOK);
							$pageInfo->resetType(Type::TYPE_TWITTER);
							$pageInfo->resetType(Type::TYPE_DERIVED);
							$resultType = $pageInfo->getType(Type::TYPE_INCOMPLETE);
							$resultType->setTitle('');

							$resultType->setDomain($queueItem->getUrl()->urlDecoded->host);
							$resultType->setPath($queueItem->getUrl()->urlDecoded->path);

						}

						if(false === $resultType instanceof Type) {
							$resultType = $pageInfo->getType(Type::TYPE_ERROR);
						}

						$resultType->setUrl($queueItem->getUrl());
						$resultType->setHtmlDocument($queueItem->getHtmlDocument());
						$resultType->setDomain($queueItem->getUrl()->urlDecoded->host);
						$resultType->setPath($queueItem->getUrl()->urlDecoded->path);
						$resultType->persistUnique([ 'path' => $resultType->getPath(), 'domain' => $resultType->getDomain() ]);

						// finished processing this scraped page, now we shall delete it from the queue
						$queueItem->drop();

						return $resultType;

					}else{
						// bad request, somehow has failed

						$resultType = new Error();
						$resultType->setUrl($queueItem->getUrl());
						$resultType->setDomain($queueItem->getUrl()->urlDecoded->host);
						$resultType->setPath($queueItem->getUrl()->urlDecoded->path);

						$resultType->persistUnique([ 'path' => $resultType->getPath(), 'domain' => $resultType->getDomain() ]);

						// finished processing this scraped page, now we shall delete it from the queue
						$queueItem->drop();

						return $resultType;

					}

			}
		}
	}
}

