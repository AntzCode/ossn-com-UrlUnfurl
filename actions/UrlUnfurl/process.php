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

use UrlUnfurl\Controller;

$processId = input('process_id', '', '');

$responseData = Controller::processQueue($processId);

if($responseData === false){
	header('Content-Type: application/json');
	echo json_encode((object) ['queued' => false, 'status' => 'fail']);
	exit;
}else{
	header('Content-Type: application/json');
	echo json_encode($responseData);
	exit;
}
