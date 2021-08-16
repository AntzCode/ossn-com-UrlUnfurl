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

namespace UrlUnfurl\Result;

class UrlDecoded
{
	public $scheme = null;
	public $host = null;
	public $port = null;
	public $user = null;
	public $pass = null;
	public $path = null;
	public $query = null;
	public $fragment = null;

	public function __construct($url){

		$decoded = parse_url($url);

		$this->scheme = ($decoded['scheme'] ?? 'http');
		$this->host = $decoded['host'];
		$this->port = ($decoded['port'] ?? '');
		$this->user = ($decoded['user'] ?? '');
		$this->pass = ($decoded['pass'] ?? '');
		$this->path = ($decoded['path'] ?? '/');
		$this->query = ($decoded['query'] ?? '');
		$this->fragment = ($decoded['fragment'] ?? '');

	}

}
