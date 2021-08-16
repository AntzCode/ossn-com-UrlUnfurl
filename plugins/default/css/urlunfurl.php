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
?>

/**
    ########################################
    ###
    ###   Formatting of the preview in the form
    ###
    ########################################
*/

.urlunfurl-preview-container{
}
.urlunfurl-preview-container{
    margin: 1%;
    width: 98%;
    border: solid 1px #3b5998 !important;
    background: #9ccada1f;
    border-radius: 10px;
}
.urlunfurl-preview-title{
    border: none;
    text-align: justify;
    font-size: 1.9rem;
    font-weight: bold;
    margin: 0;
    padding: 2%;
    line-height: 1.5;
    background: #3b5998;
    color: #FFFFFF;
    border-top-right-radius: 5px;
    border-top-left-radius: 5px;
    /*    background: rgb(102,160,180);
    background: linear-gradient(0deg, rgba(102,160,180,0.5046393557422969) 0%, rgba(106,163,183,0.7875525210084033) 3%, rgba(156,202,218,0.4598214285714286) 54%, rgba(156,202,218,0.19931722689075626) 100%);
    -webkit-box-shadow: 0px 12px 32px -2px rgba(109,109,109,0.64);*/
    box-shadow: 0px 12px 32px -2px rgba(109,109,109,0.64);
}
.urlunfurl-preview-image{
    width: 96%;
    margin-left: 2%;
    margin-top: 2%;
    overflow: hidden;
    -webkit-box-shadow: 0px 12px 32px -2px rgba(109,109,109,0.19);
    box-shadow: 0px 12px 32px -2px rgba(109,109,109,0.19);
}
.urlunfurl-preview-image img{
    width: 100%;
    height: 100%
}
.urlunfurl-preview-description{
    font-size: 1.6rem;
    line-height: 1.4;
    text-align: justify;
    margin: 2%;
}

/**
    ########################################
    ###
    ###   Formatting of the menus in the form
    ###
    ########################################
*/
.urlunfurl-menu-button{
    position: relative;
}
.urlunfurl-menu{
    min-width: 320px;
    min-height: 20px;
    border: solid 1px #337ab7;
    background: white;
    border-radius: 5px;
    z-index: 1200 !important;
    display: none;
    text-align: left;
    margin: 0 8px;
}
.urlunfurl-menu.open{
    display: block !important;
}
.urlunfurl-menu .disable{
    font-weight: bold;
    text-align: right;
    line-height: 2.5;
    border-bottom: solid 1px gray;
    padding: 4px 8px;
}
.urlunfurl-menu .disable:hover{
    color: white;
    background-color: red;
}
.urlunfurl-menu .disable .disable-icon
{
    text-align: center;
    color: red;
    width: 8%;
}
.urlunfurl-menu .disable:hover .disable-icon
{
    color: white;
}
.urlunfurl-menu .urls .url{
    float: left;
    width: 100%;
    padding: 4px 8px;
    border-bottom: solid 1px #EEEEEE;
}
.urlunfurl-menu .urls .url:hover{
    background-color: #EEEEEE;
}
.urlunfurl-menu .urls .url .is-active{
    float: left;
    width: 7%;
    color: green;
    min-height: 1px;
    visibility: hidden;
}
.urlunfurl-menu .urls .url .is-active.active{
    visibility: visible;
}
.urlunfurl-menu .urls .url .title{
    float: left;
    width: 86%;
}
.urlunfurl-menu .urls .url .url-actions{
    float: right;
    width: 7%;
}
.urlunfurl-menu .urls .url .url-actions .active-icon{

}
.urlunfurl-menu .urls .url .image-picker-container
{
    min-height: 180px;
}
.urlunfurl-menu .urls .url .image-picker-container > div{
    border: solid 3px #EEEEEE;
    background: white;
}
.urlunfurl-menu .urls .url .image-picker-container .image{
    height: 152px;
    width: auto;
    border-radius: 16px;
    margin: 10px;
    -webkit-box-shadow: 4px 4px 14px -1px rgba(135,135,135,0.45);
    box-shadow: 4px 4px 14px -1px rgba(135,135,135,0.45);
}
.urlunfurl-menu .urls .url .image-picker-container .close-button{
    position: absolute;
    top: 14px;
    right: 14px;
    font-size: 26px;
}

/**
    ########################################
    ###
    ###   Formatting of the post on the wall
    ###
    ########################################
*/
.urlunfurl-post-extra{
    border: solid 1px #3b5998;
    background: #9ccada1f;
    border-radius: 10px;
}
.urlunfurl-post-extra .urlunfurl-title{
    font-size: 1.9rem;
    font-weight: bold;
    margin: 0;
    padding: 2%;
    line-height: 1.5;
    background: #3b5998;
    color: #FFFFFF;
    border-top-right-radius: 5px;
    border-top-left-radius: 5px;
    /*    background: rgb(102,160,180);
    background: linear-gradient(0deg, rgba(102,160,180,0.5046393557422969) 0%, rgba(106,163,183,0.7875525210084033) 3%, rgba(156,202,218,0.4598214285714286) 54%, rgba(156,202,218,0.19931722689075626) 100%);
    -webkit-box-shadow: 0px 12px 32px -2px rgba(109,109,109,0.64);*/
    box-shadow: 0px 12px 32px -2px rgba(109,109,109,0.64);
}
.urlunfurl-post-extra .urlunfurl-title a{
    color: #FFFFFF;
}
.urlunfurl-post-extra .urlunfurl-title:hover a{
    position: relative;
    top: -1px;
    left: -1px;
    text-decoration: none;
}
.urlunfurl-post-extra .urlunfurl-image{
    width: 96%;
    margin-left: 2%;
    margin-top: 2%;
    overflow: hidden;
    -webkit-box-shadow: 0px 12px 32px -2px rgba(109,109,109,0.19);
    box-shadow: 0px 12px 32px -2px rgba(109,109,109,0.19);
}
.urlunfurl-post-extra .urlunfurl-image img{
    width: 100%;
    height: auto;
}
.urlunfurl-post-extra .urlunfurl-image:hover{
    position: relative;
    top: -1px;
    left: -1px;
}
.urlunfurl-post-extra .urlunfurl-description{
    font-size: 1.6rem;
    line-height: 1.4;
    text-align: justify;
    margin: 2%;
}




/**
    ########################################
    ###
    ###   Formatting of the logging window
    ###
    ########################################
*/
.antzlog-window.ui-draggable{
    opacity: 0.8;
}
.antzlog-window.ui-draggable:hover{
    opacity: 1;
}
.antzlog-window{
    position: fixed;
    top: 30px;
    right: 30px;
    min-height: 500px;
    width: 480px;
    border: solid 3px #0095ff;
    border-radius: 5px;
    background: #dbe9f7;
    z-index: 5000;
}
.antzlog-window .buttons{
    list-style-type: none;
    margin: 0 0 8px 8px;
    padding: 4px;
}
.antzlog-window .buttons li{
    display: inline-block;
    border-radius: 3px;
    font-weight: bold;
    cursor: pointer;
    padding: 3px 7px;
    margin: 0 12px 0 0;
    -webkit-box-shadow: 1px 3px 22px -2px #6D6E84;
    box-shadow: 1px 3px 22px -2px #6D6E84;
    border: solid 1px #808080;
    background-color: #808080;
    color: #FFFFFF;
}
.antzlog-window .buttons li:hover{
    position: relative;
    top: -1px;
    left: -1px;
    background-color: #9a9a9a;
    -webkit-box-shadow: 1px 5px 22px -2px #6D6E84;
    box-shadow: 1px 5px 22px -2px #6D6E84;
}
.antzlog-window .buttons li.start-stop{

}
.antzlog-window .buttons li.start-stop.run{
    background-color: #d80000;
    color: white;
    border-color: #d80000;
}
.antzlog-window .buttons li.start-stop.run:hover{
    background-color: #f10000;
}
.antzlog-window .buttons li.start-stop.halt{
    background-color: #969696;
    color: white;
    border-color: gray;
}
.antzlog-window .buttons li.start-stop.halt:hover{
    background-color: #03af00;
    border-color: #03af00;
}
.antzlog-window .states{
    margin: 8px;
}
.antzlog-window .states .state{
    margin: 0 0 8px 0;
    padding: 4px;
    -webkit-box-shadow: 1px 5px 22px -2px #6D6E84;
    box-shadow: 1px 5px 22px -2px #6D6E84;
}
.antzlog-window .state-title{
    font-weight: bold;
}
.antzlog-window .state-value{
    overflow-x: hidden;
}
.antzlog-window .queues{
    margin: 8px;
}
.antzlog-window .queues .queue{
    margin: 0 0 8px 0;
    padding: 4px;
    -webkit-box-shadow: 1px 5px 22px -2px #6D6E84;
    box-shadow: 1px 5px 22px -2px #6D6E84;
}
.antzlog-window .queue-title{
    font-weight: bold;
}
.antzlog-window .queue-items{
    overflow-x: hidden;
}
.antzlog-window .log-messages{
    overflow-y: scroll;
    height: 500px;
    margin: 0;
    padding: 0;
}
.antzlog-window .log-messages li{
    list-style-type: none;
    margin: 0;
    padding: 3px 7px;
    border-bottom: dashed 2px #CCC;
    font-family: monospace;
}
.antzlog-window .log-messages li pre{
    white-space: break-spaces;
    font-size: 11px;
    margin-bottom: 0;
}

