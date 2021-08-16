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
$settings = $component->getComSettings('UrlUnfurl');

?>

<label><?php echo ossn_print('ossn:urlunfurl:admin:settings:enable_imagick:title');?></label>
<?php echo ossn_print('ossn:urlunfurl:admin:settings:enable_imagick:note');?>

<select name="enable_imagick">
 	<?php
        $enableImagickOff = '';
        $enableImagickOn = '';
        if($settings && $settings->enable_imagick == 'on'){
            $enableImagickOn = 'selected';
        } else {
            $enableImagickOff = 'selected';
        }
	?>
	<option value="off" <?php echo $enableImagickOff;?>><?php echo ossn_print('ossn:admin:settings:off');?></option>
	<option value="on" <?php echo $enableImagickOn;?>><?php echo ossn_print('ossn:admin:settings:on');?></option>

</select>

<br />

<input type="submit" value="<?php echo ossn_print("save");?>" class="btn btn-success btn-sm"/>
