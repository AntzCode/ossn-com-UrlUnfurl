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

$url = html_entity_decode(input('url'));

if($result = Controller::getUrlData($url)) {

	if ($result->queued) {
		if ($result->status === 'new') {
			$result = Controller::processQueue($result->processId);
		}
		if ($result->queued) {
			if ($result->status === 'scraped') {
				$result = Controller::processQueue($result->processId);
			}
		}
		if (!$result->queued) {
			if ($result->status === 'success') {

				$result = Controller::getUrlData($url);

				header('Content-Type: application/json');
				echo json_encode($result);
				exit;
			}
		}

		header('Content-Type: application/json');
		echo json_encode((object) ['queued' => false, 'status' => 'fail']);
		exit;

	}else{
		header('Content-Type: application/json');
		echo json_encode($result);
		exit;
	}


}else{
	header('Content-Type: application/json');
	echo json_encode((object) ['queued' => false, 'status' => 'fail']);
	exit;
}
