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

define('__URLUNFURL__', ossn_route()->com . 'UrlUnfurl/');

require_once(__URLUNFURL__ . 'vendor/autoload.php');
require_once(__URLUNFURL__ . 'classes/OssnCallbacks.php');

use UrlUnfurl\UrlUnfurl;

function urlunfurl_init() {

	$UrlUnfurl = UrlUnfurl::getInstance();
	$comSettings = UrlUnfurl::getOssnComponentSettings();

	if(ossn_isAdminLoggedin()) {
        ossn_register_action('urlunfurl/admin/settings', __URLUNFURL__ . 'actions/UrlUnfurl/admin/settings.php');
        ossn_register_com_panel('UrlUnfurl', 'settings');
    }

	/**
	 * 		FILES
	 */

	ossn_extend_view('css/ossn.default', 'css/urlunfurl');
	ossn_extend_view('js/ossn_wall', 'js/urlunfurl');
	ossn_new_js('anchorme.min', 'js/anchorme.min');
	ossn_load_js('anchorme.min');

	/**
	 * 		ACTIONS
	 */

	ossn_register_action('urlunfurl/fetch', __URLUNFURL__ . 'actions/UrlUnfurl/fetch.php');
	ossn_register_action('urlunfurl/process', __URLUNFURL__ . 'actions/UrlUnfurl/process.php');
	ossn_register_action('urlunfurl/image', __URLUNFURL__ . 'actions/UrlUnfurl/image.php');

	// bypass validation on some actions
	ossn_add_hook('action', 'validate:bypass', 'urlunfurl_hook_action_validate_bypass');

	/**
	 * 		MENUS
	 */
	$urlUnfurlMenuButton = [
		'name' => 'urlunfurl',
		'class' => 'urlunfurl-menu-button',
		'text' => '<i class="fa fa-eye" title="'.ossn_print('ossn:urlunfurl:wall:button:preview').'"></i>',
	];

	ossn_register_menu_item('wall/container/controls/home', $urlUnfurlMenuButton);
	ossn_register_menu_item('wall/container/controls/user', $urlUnfurlMenuButton);
	ossn_register_menu_item('wall/container/controls/group', $urlUnfurlMenuButton);

	/**
	 * 		HOOKS
	 */
	ossn_add_hook('wall:template', 'user', ['UrlUnfurl_Callbacks', 'hookWallTemplateUser'], 100);
	ossn_add_hook('urlunfurl', 'worker:process:url_queue', array('UrlUnfurl_Callbacks', 'hookWorkerProcessUrlQueue'));
	ossn_register_callback('wall', 'post:created', array('UrlUnfurl_Callbacks', 'eventWallPostCreated'));
	ossn_register_callback('wall', 'post:edited', array('UrlUnfurl_Callbacks', 'eventWallPostEdited'));
	ossn_register_callback('post', 'deleted', array('UrlUnfurl_Callbacks', 'eventWallPostDeleted'));

}

/**
 * Bypasses validation on these actions (makes them accessible without a user token)
 * @param $hook
 * @param $type
 * @param $return
 * @param $params
 * @return mixed
 */
function urlunfurl_hook_action_validate_bypass($hook, $type, $return, $params){

	if(!in_array('urlunfurl/process', $params)){
		// @TODO: for development only, disable in production
		$params[] = 'urlunfurl/process';
	}

	if(!in_array('urlunfurl/image', $params)){
		$params[] = 'urlunfurl/image';
	}

	return $params;
}

// execute component init
ossn_register_callback('ossn', 'init', 'urlunfurl_init');
