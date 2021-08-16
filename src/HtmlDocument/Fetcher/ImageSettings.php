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

namespace UrlUnfurl\HtmlDocument\Fetcher;

class ImageSettings
{
	public $imageMinWidth = 395;
	public $imageMinHeight = 235;
	public $imageWidth = 700;
	public $imageHeight = 350;
	public $validMimes = [
		'image/jpeg',
		'image/gif',
		'image/png'
	];
}
