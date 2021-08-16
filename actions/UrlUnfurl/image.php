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

use UrlUnfurl\UrlUnfurl;

$imageName = input('img');

$filepath = UrlUnfurl::getImagePath($imageName);

$imagesize = getimagesize($filepath);
$filesize = filesize($filepath);
$etag = md5($imageName.$filesize);

header("Content-type: {$imagesize['mime']}");
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', strtotime("+6 months")), true);
header("Pragma: public");
header("Cache-Control: public");
header("Content-Length: {$filesize}");
header("ETag: \"$etag\"");
readfile($filepath);
return;
