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

$installDir = dirname(__FILE__).DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR;

$comId = 'UrlUnfurl';

define($comId.'_COM_DELETE', '1');

$comXml = $this->getCom($comId);
$comSettings = $this->getComSettings($comId);

require($installDir.'delete.php');

OssnFile::DeleteDir(ossn_get_userdata('tmp/urlunfurl/'));
OssnFile::DeleteDir(ossn_get_userdata('components/urlunfurl/'));

