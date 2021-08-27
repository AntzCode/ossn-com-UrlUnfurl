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

use UrlUnfurl\Database;
use UrlUnfurl\Result;
use UrlUnfurl\Result\Type;

$postId = null;

foreach($params['menu'] as $menu) {
    foreach($menu as $link) {
        if($link['name']==='comment'){
            $postId = $link['data-guid'];
            break 2;
        }
    };
}

if($postId){

	if($postUrl = Database::getOneBy('_post_url', ['post_id' => $postId])){
		$found = false;
		$resultType = Result::getById($postUrl->urlId);

		if(!$resultType instanceof Type){
		    return;
        }

		foreach($resultType->getImages() as $imageData){
			if($imageData->guid === $postUrl->image){
				$found = true;
				break;
			}
		}

		if(!$found){
			return;
		}

	}else{
	    return;
    }

}

?>
<div class="urlunfurl urlunfurl-post-extra">
    <div class="urlunfurl-title">
        <a href="<?php echo $resultType->getUrl()->url ?>"><?php echo htmlentities($resultType->getTitle()) ?></a>
    </div>
    <div class="urlunfurl-image">
        <a href="<?php echo $resultType->getUrl()->url ?>">
            <img src="<?php echo ossn_site_url('/action/urlunfurl/image?img='.$imageData->filename) ?>" />
        </a>
    </div>
    <div class="urlunfurl-description">
		<?php echo htmlentities($resultType->getDescription()) ?>
    </div>
</div>


?>
<div class="urlunfurl urlunfurl-post-extra">
    <div class="urlunfurl-title">
        <a href="<?php echo $resultType->getUrl()->url ?>"><?php echo htmlentities($resultType->getTitle()) ?></a>
    </div>
    <div class="urlunfurl-image">
        <a href="<?php echo $resultType->getUrl()->url ?>">
            <img src="<?php echo ossn_site_url('/action/urlunfurl/image?img='.$imageData->filename) ?>" />
        </a>
    </div>
    <div class="urlunfurl-description">
		<?php echo htmlentities($resultType->getDescription()) ?>
    </div>
</div>

<?php

$postextra = $params['menu'];
if ($postextra && ossn_isLoggedin()) {
	if (!empty($postextra)) {
		foreach ($postextra as $menu) {
			foreach ($menu as $link) {
				$class = "post-control-" . $link['name'];
				if (isset($link['class'])) {
					$link['class'] = $class . ' ' . $link['class'];
				} else {
					$link['class'] = $class;
				}
				unset($link['name']);
				$link = ossn_plugin_view('output/url', $link);
				echo "<li>" . $link . "</li>";
			}
		}
	}
}
