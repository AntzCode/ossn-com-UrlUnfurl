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

use UrlUnfurl;
use UrlUnfurl\Result\UrlDecoded;

class Url
{
	public $url;
	public $rawUrl;
	public $urlDecoded;

	public function __construct(string $url){

		$this->rawUrl = $url;

		$url = trim($url);

		switch($url){

			case (substr(strtolower($url), 0, 8) === 'https://'):
			case (substr(strtolower($url), 0, 7) === 'http://'):
				// the protocol exists
				$this->url = $url;
				break;

			case (substr($url, 0, 2) === '//'):
				// need to add a protocol - http for compatibility
				$this->url = 'http://'.substr($url, 2);
				break;

			case (substr($url, 0, 1) === '/'):
				// treat it as a local url
				$this->url = UrlUnfurl::getBaseUrl().$url;
				break;

			default:
				// just starts with a domain name, prefix it with a protocol
				$this->url = 'http://'.$url;

		}

		$this->urlDecoded = new UrlDecoded($this->url);

	}
}
