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

$en = array(
	'urlunfurl' => 'Url Unfurl',
	'ossn:urlunfurl:admin:settings:enable_imagick:title' => 'Enable Imagick',
	'ossn:urlunfurl:admin:settings:enable_imagick:note' => '<i class="fa fa-info-circle"></i>
	If you do not have Imagick installed on your server, you can disable this setting',
	'ossn:urlunfurl:wall:button:preview' => 'Preview URL',
	'ossn:urlunfurl:wall:button:preview' => 'Preview',
);
ossn_register_languages('en', $en);
