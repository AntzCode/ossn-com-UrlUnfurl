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

$component = new OssnComponents;

$type  = input('type');

$onOff = array(
    'off',
    'on'
);

$enableImagick = input('enable_imagick');

if(in_array($enableImagick, $onOff)) {
    if($component->setSettings('UrlUnfurl', array('enable_imagick' => $enableImagick))) {
        ossn_trigger_message(ossn_print('ossn:admin:settings:saved'));
        redirect(REF);
    }
}

ossn_trigger_message(ossn_print('ossn:admin:settings:save:error'), 'error');
redirect(REF);
