//<script>
/**
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
 */

Ossn.register_callback('ossn', 'init', 'urlunfurl_init');

function urlunfurl_init(){

    $(document).ready(function() {

        UrlUnfurl.init();

        $('.ossn-wall-container')
            .delegate('.urlunfurl-menu-button', 'click', function (e) {
                UrlUnfurl.toggleMenu(this);
            })
            .delegate('.urlunfurl-menu-button', 'mouseenter', function(e) {
                UrlUnfurl.mouseOverMenu = true;
            })
            .delegate('.urlunfurl-menu-button', 'mouseleave', function(e) {
                UrlUnfurl.mouseOverMenu = false;
            });

        $('body').click(function(){
            if(!UrlUnfurl.mouseOverMenu){
                UrlUnfurl.hideMenu();
            }
        });

        $('.ossn-wall-container textarea').on('keyup', function(event){
            UrlUnfurl.handleChange(event, this);
        });

    });

}


var UrlUnfurl = {
    // the keypressWaitTime sets a speed for how soon the URL's will be detected while the user is typing
    // it enables a user to finish typing a url before scanning for URL's in their post
    // too much wait time will seem sluggish, but too little wait time will cause incomplete URL's to be cached
    keypressWaitTime : 950,
    unfurlDisabled : false,
    imagePrefix : '<?php echo ossn_site_url('/action/urlunfurl/image?img=') ?>',
    keypressTimeout : null,
    queueFetchInterval : null,
    queueWorkerInterval : null,
    urlCache : [],
    fetchQueue : [],
    workerQueue : [],
    processQueue : [],
    currentFetching : [],
    currentProcessing : [],
    previewContainer : null,
    previewTitle : null,
    previewImage : null,
    previewDescription : null,
    allUrls : [],
    availableUrls : [],
    form : null,
    formFieldUrlId : null,
    formFieldImageGuid : null,
    mouseOverMenu : false,
    imagePickerOpen : false,

    init : function(){

        setTimeout(() => {
            AntzLog.addState('Mouse is over menu', 'mouse-over-menu', () => UrlUnfurl.mouseOverMenu, v => v() ? 'yes' : 'no');
            AntzLog.addQueue('All URL\s', 'all-urls', UrlUnfurl.allUrls, v => v);
            AntzLog.addQueue('Available URL\'s', 'available-urls', UrlUnfurl.availableUrls, v => v.url);
            AntzLog.addQueue('Fetch Queue', 'fetch-queue', UrlUnfurl.fetchQueue, v => v.url);
            AntzLog.addQueue('Currently Fetching', 'currently-fetching', UrlUnfurl.currentFetching, v => v);
            AntzLog.addQueue('Process Queue', 'process-queue', UrlUnfurl.processQueue, v => v);
            AntzLog.addQueue('Currently Processing', 'currently-processing', UrlUnfurl.currentProcessing, v => v);
            AntzLog.createLogWindow();
        }, 100);

        UrlUnfurl.startQueues();

        UrlUnfurl.createPreviewContainer();
        UrlUnfurl.createFormFields();
        UrlUnfurl.createMenuContainer();
        $('.urlunfurl-menu-button').hide();

        $(UrlUnfurl.form).on('submit', function(){
            setTimeout(function(){
                UrlUnfurl.hidePreview();
                UrlUnfurl.clearQueues();
                //UrlUnfurl.clearCache();
            }, 300);
        });
    },

    createTextChangeListener : function(textarea){
        $(textarea).on('keypress', (e) => UrlUnfurl.handleChange(e, textarea));
    },

    startQueues : function(){
        if(UrlUnfurl.queueFetchInterval !== null){
            clearInterval(UrlUnfurl.queueFetchInterval);
        }
        UrlUnfurl.queueFetchInterval = window.setInterval(() => {UrlUnfurl.processFetchQueue();}, 150);

        if(UrlUnfurl.queueWorkerInterval !== null){
            clearInterval(UrlUnfurl.queueWorkerInterval);
        }
        UrlUnfurl.queueWorkerInterval = setInterval(function(){UrlUnfurl.processWorkerQueue();}, 160);
    },

    stopQueues : function(){
        if(UrlUnfurl.queueFetchInterval !== null){
            clearInterval(UrlUnfurl.queueFetchInterval);
        }
        if(UrlUnfurl.queueWorkerInterval !== null){
            clearInterval(UrlUnfurl.queueWorkerInterval);
        }
    },

    clearCache : function(){
        while(UrlUnfurl.allUrls.length > 0){
            UrlUnfurl.allUrls.splice(0, 1);
        }
        while(UrlUnfurl.availableUrls.length > 0){
            UrlUnfurl.availableUrls.splice(0, 1);
        }
    },

    clearQueues : function(){
        UrlUnfurl.fetchQueue = [];
        UrlUnfurl.processQueue = [];
        UrlUnfurl.currentProcessing = [];
        UrlUnfurl.currentFetching = [];
    },

    hidePreview : function(){
        UrlUnfurl.previewContainer.addClass('hidden');
        UrlUnfurl.unsetActiveUrl();
    },

    showPreview : function(urlData){
        UrlUnfurl.enableUnfurl();
        UrlUnfurl.previewContainer.removeClass('hidden');
        UrlUnfurl.setActiveUrl(urlData);
    },

    updateView : function(){
        if(UrlUnfurl.availableUrls.length < 1){
            // hide the button
            $('.urlunfurl-menu-button').hide();
        }else{
            $('.urlunfurl-menu-button').show();
        }

        if(UrlUnfurl.unfurlDisabled || UrlUnfurl.availableUrls.length < 1){
            UrlUnfurl.unsetActiveUrl();
            UrlUnfurl.hidePreview();
            UrlUnfurl.mouseOverMenu = false;
            UrlUnfurl.hideMenu();
            return;
        }

        if(UrlUnfurl.availableUrls.length === 1){
            // only one to show

            let urlData = UrlUnfurl.getPreferredUrlData(UrlUnfurl.availableUrls[0]);
            UrlUnfurl.setActiveUrl(urlData);
            UrlUnfurl.showPreview(urlData);

        }else{

            if(UrlUnfurl.activeUrl){
                // make sure the active URL is still available
                let found = false;
                for(let availableUrl of UrlUnfurl.availableUrls){
                    let urlData = UrlUnfurl.getPreferredUrlData(availableUrl);
                    if(urlData.id === UrlUnfurl.activeUrl.id){
                        found = true;
                        break;
                    }
                }

                if(found) {
                    UrlUnfurl.showPreview(UrlUnfurl.activeUrl);
                }else{
                    UrlUnfurl.unsetActiveUrl();
                    // let's set the active URL to the first in the list
                    let urlData = UrlUnfurl.getPreferredUrlData(UrlUnfurl.availableUrls[0]);
                    UrlUnfurl.setActiveUrl(urlData);
                    UrlUnfurl.showPreview(urlData);
                }

            }
        }
    },

    updateMenu : function(forceRebuild=false){

        if(UrlUnfurl.unfurlDisabled){
            return;
        }

        // check first whether rebuild is needed
        if(UrlUnfurl.activeUrl){
            if(!$('#urlunfurl-url-'+UrlUnfurl.activeUrl.id+' .is-active').hasClass('active')){
                // we will rebuild if the current active url is not marked as active in the list
                forceRebuild = true;
            }
        }

        if(!forceRebuild){

            if(UrlUnfurl.availableUrls.length === $('.urls', UrlUnfurl.menuContainer).get().length) {
                let availableUrls = UrlUnfurl.availableUrls.map(v => v.url);
                let elUrls = [];
                $('.url', UrlUnfurl.menuContainer).each((el) => {
                    elUrls.push($(el).data('url'));
                });
                availableUrls = availableUrls.filter(v => elUrls.indexOf(v) > -1);
                if(availableUrls.length > 0){
                    // we will force rebuild if there are any available urls that are not shown in the list
                    forceRebuild = true;
                }

            }else{

                // we will force rebuild when the number of available urls does not match the number of urls in the list
                forceRebuild = true;

            }
        }

        if(forceRebuild){

            $('.urls', UrlUnfurl.menuContainer).html('');

            let disableEl = $('<div />').addClass('disable').text('Disable Preview');
            let disableIconEl = $('<i class="fa fa-trash"></i>').addClass('disable-icon');

            $(disableEl).click(function(){
                UrlUnfurl.disableUnfurl();
                UrlUnfurl.updateView();
            });

            $('.urls', UrlUnfurl.menuContainer).append(disableEl);

            for(let availableUrl of UrlUnfurl.availableUrls){
                let urlData = UrlUnfurl.getPreferredUrlData(availableUrl);

                let urlEl = $('<div />').addClass('url').prop('id', 'urlunfurl-url-'+urlData.id);
                let titleEl = $('<div />').addClass('title').text(urlData.title);
                let activeEl = $('<div />').addClass('is-active')
                let isActiveEl = $('<i class="fa fa-check active-icon" title="<?php echo ossn_print('ossn:urlunfurl:is_active') ?>"></i>');
                let actionsEl = $('<div />').addClass('url-actions');
                let imageBtnEl = $('<i class="fa fa-picture-o image-icon" title="<?php echo ossn_print('ossn:urlunfurl:choose_image') ?>"></i>');

                if(urlData.id === UrlUnfurl.activeUrl.id){
                    activeEl.addClass('active');
                }

                titleEl.data('url', availableUrl.url);

                $(titleEl).click(function(){
                    UrlUnfurl.enableUnfurl();
                    let availableUrl = UrlUnfurl.getAvailableUrl($(this).data('url'));
                    let urlData = UrlUnfurl.getPreferredUrlData(availableUrl);
                    UrlUnfurl.setActiveUrl(urlData);
                    $('.urlunfurl-menu .url .is-active').removeClass('active');
                    $('#urlunfurl-url-'+urlData.id+' .is-active').addClass('active');
                });

                $(imageBtnEl).click(() => {
                    if($('.image-picker-container', urlEl).get().length > 0){
                        UrlUnfurl.hideImagePicker(urlData, urlEl);
                    }else{
                        $('.image-picker-container').hide();
                        UrlUnfurl.showImagePicker(urlData, urlEl);
                    }
                });

                disableEl.append(disableIconEl);
                activeEl.append(isActiveEl);

                if(urlData.images.length > 1){
                    // only show the image picker icon if there is more than one image to choose from
                    actionsEl.append(imageBtnEl);
                }

                urlEl.append(activeEl).append(titleEl).append(actionsEl);
                $('.urls', UrlUnfurl.menuContainer).append(urlEl);

            }
        }
    },

    hideImagePicker : function(urlData, positionTarget){
        $('.image-picker-container', positionTarget).remove();
        UrlUnfurl.mouseOverMenu = false;
        setTimeout(() => UrlUnfurl.imagePickerOpen = false, 300);
    },

    showImagePicker : function(urlData, positionTarget){

        UrlUnfurl.imagePickerOpen = true;
        let imagePicker = $('<div><div /></div>').addClass('image-picker-container');
        let container = $('.ossn-inner-page .container');
        $(positionTarget).append(imagePicker);

        imagePicker.css({
            'width' : container.width() + 'px'
        });

        UrlUnfurl.positionMenuRelativeTo(imagePicker, positionTarget, 'ul');

        let i = 0;
        while(imagePicker.offset().left < container.offset().left && i < imagePicker.width()){
            imagePicker.css('left', (imagePicker.position().left+1)+'px');
            i++;
        }

        for(let imageData of urlData.images){

            let imageEl = $('<img src="'+UrlUnfurl.imagePrefix+imageData.filename+'" class="image" />');

            $(imageEl).data('guid', imageData.guid);
            $(imageEl).click(function() {
                UrlUnfurl.enableUnfurl();
                UrlUnfurl.formFieldImageGuid.val($(this).data('guid'));
                UrlUnfurl.setActiveUrl(urlData);
                UrlUnfurl.setPreviewImage(imageData);
                UrlUnfurl.hideImagePicker(urlData, positionTarget);
                UrlUnfurl.imagePickerOpen = false;
                UrlUnfurl.hideMenu();
            });

            $('>div', imagePicker).append(imageEl);

            $(imageEl).on('load', function(){
                while(imagePicker.offset().top < container.offset().top){
                    imagePicker.css('top', (imagePicker.position().top+1)+'px');
                }
            });

        }

        imagePicker.css('min-height', $('>div', imagePicker).css('height'));

        // @TODO : are we going to support the showing of an unfurled url without an image?
        // let imageEl = $('<img src="'+UrlUnfurl.imagePrefix+'no-image.png" class="image no-image" />');
        // imageEl.click(() => {
        //     UrlUnfurl.formFieldImageGuid.val('');
        //     UrlUnfurl.hidePreviewImage();
        // });
        // imagePicker.append(imageEl);

        let closeButton = $('<i class="fa fa-close close-button"></i>');
        closeButton.click(() => UrlUnfurl.hideImagePicker(urlData, positionTarget));
        imagePicker.append(closeButton);

    },

    positionMenuRelativeTo : function(menuEl, targetEl, direction='ur', external=false){

        menuEl.css({
            position: 'absolute'
        });

        let externalH;
        let externalV;

        if(external === true){
            externalH = true;
            externalV = true;
        }else if(external === false){
            externalH = false;
            externalV = false;
        }else{
            externalH = external.match(/h/);
            externalV = external.match(/v/);
        }

        switch(direction){

            case 'dr':
                $(menuEl).css({
                    bottom: 'auto',
                    right: 'auto',
                    left: externalH ? $(targetEl).css('width') : 0,
                    top: externalV ? $(targetEl).css('height') : 0
                });
                break;

            case 'dl':
                $(menuEl).css({
                    bottom: 'auto',
                    right: externalH ? $(targetEl).css('width') : 0,
                    left: 'auto',
                    top: externalV ? $(targetEl).css('height') : 0
                });
                break;

            case 'ul':
                $(menuEl).css({
                    bottom: externalV ? $(targetEl).css('height') : 0,
                    right: externalH ? $(targetEl).css('width') : 0,
                    left: 'auto',
                    top: 'auto'
                });
                break;

            case 'ur':
            default:
                $(menuEl).css({
                    bottom: externalV ? $(targetEl).css('height') : 0,
                    right: 'auto',
                    left: externalH ? $(targetEl).css('width') : 0,
                    top: 'auto'
                });
                break;

        }

    },

    toggleMenu : function(button){

        if(UrlUnfurl.menuContainer.is(':hidden')){
            UrlUnfurl.showMenu(button);
        }else{
            if(UrlUnfurl.mouseOverMenu){
                return;
            }
            UrlUnfurl.hideMenu(button);
        }

    },

    showMenu : function(button){

        UrlUnfurl.updateMenu();

        if(UrlUnfurl.menuContainer.get(0).parentNode !== $(button).get(0)){
            $(button).get(0).appendChild(UrlUnfurl.menuContainer.get(0));
        }

        if(!UrlUnfurl.menuContainer.hasClass('open')){
            UrlUnfurl.positionMenuRelativeTo(UrlUnfurl.menuContainer, button, 'ur', 'v');
            UrlUnfurl.menuContainer.addClass('open');
        }else{
            UrlUnfurl.positionMenuRelativeTo(UrlUnfurl.menuContainer, button, 'ur', 'v');
        }

    },

    hideMenu : function(){

        if(UrlUnfurl.imagePickerOpen){
            return;
        }

        if(UrlUnfurl.menuContainer.hasClass('open')){
            UrlUnfurl.menuContainer.removeClass('open');
        }

    },

    createMenuContainer : function(){
        UrlUnfurl.menuContainer = $('<div />').addClass('urlunfurl-menu');
        UrlUnfurl.menuContainer.append($('<div />').addClass('urls'));
    },

    createFormFields : function(){

        UrlUnfurl.form = $('#ossn-wall-form');

        let form = UrlUnfurl.form.get(0);

        if(typeof form.urlunfurl_url_id === 'undefined'){
            UrlUnfurl.formFieldUrlId = $('<input />').prop({type: 'hidden', name: 'urlunfurl_url_id'}).val('');
            UrlUnfurl.form.append(UrlUnfurl.formFieldUrlId);
        }

        if(typeof form.urlunfurl_url_image === 'undefined'){
            UrlUnfurl.formFieldImageGuid = $('<input />').prop({type: 'hidden', name: 'urlunfurl_url_image'}).val('');
            UrlUnfurl.form.append(UrlUnfurl.formFieldImageGuid);
        }

    },

    createPreviewContainer : function(){

        UrlUnfurl.previewContainer = $('<div />').addClass(['urlunfurl-preview-container', 'hidden']);
        UrlUnfurl.previewTitle = $('<div />').addClass('urlunfurl-preview-title').text('Title');
        UrlUnfurl.previewImage = $('<div />').addClass('urlunfurl-preview-image').append($('<img />'));
        UrlUnfurl.previewDescription = $('<div />').addClass('urlunfurl-preview-description').text('Description');

        UrlUnfurl.previewContainer.append(UrlUnfurl.previewTitle);
        UrlUnfurl.previewContainer.append(UrlUnfurl.previewImage);
        UrlUnfurl.previewContainer.append(UrlUnfurl.previewDescription);

        UrlUnfurl.previewContainer.insertAfter('.ossn-wall-container-data-post textarea[name=post]');

    },

    enableUnfurl : function(){
        UrlUnfurl.setDisableUnfurl(false);
    },

    disableUnfurl : function(){
        UrlUnfurl.setDisableUnfurl(true);
        UrlUnfurl.hidePreview();
    },


    setDisableUnfurl : function(bool){

        let wasDisabled = UrlUnfurl.unfurlDisabled;

        UrlUnfurl.unfurlDisabled = (bool ? true : false);

        if(UrlUnfurl.unfurlDisabled){
            UrlUnfurl.stopQueues();
            UrlUnfurl.clearQueues();
            UrlUnfurl.formFieldUrlId.val('');
            UrlUnfurl.formFieldImageGuid.val('');

        }else{
            UrlUnfurl.startQueues();

            if(wasDisabled){
                if(!UrlUnfurl.activeUrl){
                    if(UrlUnfurl.availableUrls.length > 0){
                        UrlUnfurl.setActiveUrl(UrlUnfurl.getPreferredUrlData(UrlUnfurl.availableUrls[0]));
                    }
                }
                if(UrlUnfurl.activeUrl){
                    UrlUnfurl.showPreview(UrlUnfurl.activeUrl);
                }
            }

        }

    },

    setPreviewTitle : function(html){
        UrlUnfurl.previewTitle.html(html);
    },

    setPreviewDescription : function(html){
        UrlUnfurl.previewDescription.html(html);
    },

    setPreviewImage : function(imageData){
        $('img', UrlUnfurl.previewImage).prop('src', UrlUnfurl.imagePrefix+imageData.filename);
    },

    unsetActiveUrl : function(){
        UrlUnfurl.activeUrl = null;
        UrlUnfurl.formFieldUrlId.val('');
        UrlUnfurl.formFieldImageGuid.val('');
    },

    setActiveUrl : function(urlData){

        if(urlData){

            if(UrlUnfurl.formFieldUrlId.val() != urlData.id){
                UrlUnfurl.activeUrl = urlData;
                AntzLog.log('Setting formFieldUrlId: '+urlData.id);
                UrlUnfurl.formFieldUrlId.val(urlData.id);
                AntzLog.log('Setting formFieldImageGuid: '+urlData.id);
                UrlUnfurl.formFieldImageGuid.val(urlData.images[0].guid);
                UrlUnfurl.setPreviewTitle(urlData.title);
                UrlUnfurl.setPreviewImage(urlData.images[0]);
                UrlUnfurl.setPreviewDescription(urlData.description);
            }

            return true;

        }else if(UrlUnfurl.activeUrl){
            return UrlUnfurl.setActiveUrl(UrlUnfurl.activeUrl);
        }

        AntzLog.log('Failed to set formfieldUrlId: '+url);
        return false;

    },

    setActiveImage : function(imageGuid){

        if(UrlUnfurl.activeUrl){
            if(UrlUnfurl.activeUrl.images.map(img => image.guid).indexOf(imageGuid) > -1){
                AntzLog.log('Setting formFieldImageGuid: '+imageGuid);
                UrlUnfurl.formFieldImageGuid.val(imageGuid);
                return true;
            }
        }

        AntzLog.log('Failed to set formfieldImageGuid: '+imageGuid);
        return false;

    },

    hasUrlCache : function(url){

        let cached = UrlUnfurl.getUrlCache(url);

        if(cached === false){
            return false;
        }else{
            return true;
        }

    },

    getUrlCache : function(url){
        for(let i=0; i<UrlUnfurl.urlCache.length; i++){
            if(UrlUnfurl.urlCache[i].url === url){
                return UrlUnfurl.urlCache[i];
            }
        }
        return false;
    },

    setUrlCache : function(url, data){
        if(!UrlUnfurl.hasUrlCache(url)){
            UrlUnfurl.urlCache[UrlUnfurl.urlCache.length] = {
                url : url,
                data : data
            }
        }
    },

    addToAllUrls : function(url){
        AntzLog.log('Add to All Urls: '+url);
        let i  = UrlUnfurl.allUrls.indexOf(url);
        if(i > -1){
            return;
        }
        UrlUnfurl.allUrls.push(url);
    },

    addToAvailableUrls : function(urlData){
        AntzLog.log('Add to Available Urls: '+urlData.url);
        let i  = UrlUnfurl.availableUrls.map(value => value.url).indexOf(urlData.url);
        if(i > -1){
            return;
        }
        UrlUnfurl.availableUrls.push(urlData);
    },

    addToWorkerQueue : function(processId){
        AntzLog.log('Add to Worker Queue: '+processId);
        if(UrlUnfurl.workerQueue.indexOf(processId) < 0){
            UrlUnfurl.workerQueue.push(processId);
        }
    },

    addToFetchQueue : function(url, callback){
        let i  = UrlUnfurl.fetchQueue.map(value => value.url).indexOf(url);
        if(i > -1){
            return;
        }
        UrlUnfurl.fetchQueue.push({url, callback});
    },

    addToCurrentProcessing : function(processId){
        if(UrlUnfurl.currentProcessing.indexOf(processId) > -1){
            return;
        }
        UrlUnfurl.currentProcessing.push(processId);
    },

    addToCurrentFetching : function(url){
        if(UrlUnfurl.currentFetching.indexOf(url) > -1){
            return;
        }
        UrlUnfurl.currentFetching.push(url);
    },

    removeFromWorkerQueue : function(processId){
        AntzLog.log('Remove from Worker Queue: '+processId);
        let i  = UrlUnfurl.workerQueue.indexOf(processId);
        if(i > -1){
            UrlUnfurl.workerQueue.splice(i, 1);
        }
    },

    removeFromAllUrls : function(url){
        AntzLog.log('Remove from All Urls: '+url);
        let i  = UrlUnfurl.allUrls.indexOf(url);
        if(i > -1){
            UrlUnfurl.allUrls.splice(i, 1);
        }
    },

    removeFromFetchQueue : function(url){
        AntzLog.log('Remove from Fetch Queue: '+url);
        let i  = UrlUnfurl.fetchQueue.map(value => value.url).indexOf(url);
        if(i > -1){
            UrlUnfurl.fetchQueue.splice(i, 1);
        }
    },

    removeFromAvailableUrls : function(url){
        AntzLog.log('Remove from Available Urls: '+url);
        let i  = UrlUnfurl.availableUrls.map(value => value.url).indexOf(url);
        if(i > -1){
            UrlUnfurl.availableUrls.splice(i, 1);
        }
    },

    removeFromCurrentFetching : function(url){
        AntzLog.log('Remove from Current Fetching: '+url);
        let i  = UrlUnfurl.currentFetching.indexOf(url);
        if(i > -1){
            UrlUnfurl.currentFetching.splice(i, 1);
        }
    },

    removeFromCurrentProcessing : function(processId){
        AntzLog.log('Remove from Current Processing: '+processId);
        let i  = UrlUnfurl.currentProcessing.indexOf(processId);
        if(i > -1){
            UrlUnfurl.currentProcessing.splice(i, 1);
        }
    },

    getAvailableUrl : function(url){
        AntzLog.log('Get Available Url: '+url);
        let i  = UrlUnfurl.availableUrls.map(value => value.url).indexOf(url);
        if(i > -1){
            return UrlUnfurl.availableUrls[i];
        }
    },

    processWorkerQueue : function() {

        if (UrlUnfurl.workerQueue.length < 1) {
            return;
        }

        let queuedItems = UrlUnfurl.workerQueue.splice(0, UrlUnfurl.workerQueue.length);

        // remove duplicates
        queuedItems.filter((value, index, self) => self.indexOf(value) === index);

        AntzLog.log('Iterating process queue ('+queuedItems.length+' items)');

        for(let id of queuedItems){

            if(UrlUnfurl.currentProcessing.indexOf(id) > -1){
                continue;
            }

            UrlUnfurl.addToCurrentProcessing(id);

            AntzLog.log('API process call: '+id);

            Ossn.PostRequest({
                url: Ossn.site_url + 'action/urlunfurl/process',
                params: '&process_id='+id,
                callback: function(data) {

                    AntzLog.log('API process response', data);

                    if(data.processId){
                        AntzLog.log('Remove from Current Processing queue: '+data.processId);
                        UrlUnfurl.removeFromCurrentProcessing(data.processId)

                        UrlUnfurl.removeFromWorkerQueue(data.processId)
                    }

                    if(data.queued){
                        // place back into the queue for further processing
                        AntzLog.log('Add to Worker queue: '+data.processId+' ('+data.status+')');
                        UrlUnfurl.addToWorkerQueue(data.processId);

                    }else if(data.status === 'success'){
                        // successfully unfurled, requeue the fetch
                        AntzLog.log('Processed ok! '+data.processId);

                        UrlUnfurl.removeFromWorkerQueue(data.processId);

                        if(data.url){
                            UrlUnfurl.removeFromCurrentFetching(data.url);

                            AntzLog.log('Attempt to get data for url: '+data.url);
                            UrlUnfurl.getUrlInfo(data.url, UrlUnfurl.processUnfurled);
                        }

                    }else if(data.status === 'fail'){
                        // cannot unfurl this url
                        AntzLog.log('Processing failed! '+data.processId);
                        UrlUnfurl.removeFromCurrentFetching(data.url);

                        UrlUnfurl.removeFromWorkerQueue(data.processId);


                    }
                }
            });
        }
    },

    getPreferredUrlData : function(urlData){
        return urlData.facebook ?? urlData.twitter ?? urlData.derived ?? null;
    },

    processFetchQueue : function(){

        if(UrlUnfurl.fetchQueue.length < 1){
            return;
        }

        let queuedItems = UrlUnfurl.fetchQueue.splice(0, UrlUnfurl.fetchQueue.length);

        AntzLog.log('Iterating fetch queue ('+queuedItems.length+' items)');

        let urls = [];

        for(let item of queuedItems){

            if(UrlUnfurl.currentFetching.indexOf(item.url) > -1){
                // it's already being fetched
                AntzLog.log('Skipping currently fetching ('+item.url+')');
                UrlUnfurl.addToFetchQueue(item.url, item.callback);
                continue;
            }

            // remove duplicates
            if(urls.indexOf(item) < 0){
                urls.push(item.url);
            }

            // pop it back into the fetch queue
            UrlUnfurl.addToFetchQueue(item.url, item.callback);

        }

        AntzLog.log('Fetching from API ('+urls.length+' urls)');

        for(let url of urls){

            UrlUnfurl.addToCurrentFetching(url);

            AntzLog.log('API fetch call: '+url);

            Ossn.PostRequest({
                url: Ossn.site_url + 'action/urlunfurl/fetch',
                params: '&url='+url,
                callback: function(data) {

                    AntzLog.log('API fetch response: '+data.url);

                    for(let item of UrlUnfurl.fetchQueue){

                        if(data.url === item.url){
                            // we will process this one

                            if(data.queued) {

                                if(data.processId){
                                    UrlUnfurl.addToWorkerQueue(data.processId);
                                    UrlUnfurl.removeFromFetchQueue(item.url);

                                }else{
                                    if(data.url){
                                        setTimeout(() => {
                                            UrlUnfurl.removeFromCurrentFetching(data.url);
                                        }, 3000);
                                    }
                                }

                            }else{
                                item.callback(data);
                            }
                        }
                    }
                },
            });
        }
    },

    getUrlInfo : function(url, callback){
        
        if(UrlUnfurl.hasUrlCache(url)){
            AntzLog.log('Has cache '+url);
            callback(UrlUnfurl.getUrlCache(url).data);
        }else{
            AntzLog.log('Push to fetch queue '+url);
            UrlUnfurl.addToFetchQueue(url, callback);
        }

    },

    handleChange : function(event, textarea) {

        if(UrlUnfurl.keypressTimeout != null){
            clearTimeout(UrlUnfurl.keypressTimeout);
        }

        UrlUnfurl.keypressTimeout = setTimeout(() => {

            let urls = anchorme.list($(textarea).val());
            let allUrls = [];

            for(let url of urls){
                allUrls.push(url.string);
                UrlUnfurl.addToAllUrls(url.string);
                AntzLog.log('UrlUnfurl.getUrlInfo '+url.string);
                UrlUnfurl.getUrlInfo(url.string, UrlUnfurl.processUnfurled);
            }

            // remove obsolete urls
            let obsoleteUrls = UrlUnfurl.allUrls.filter(v => allUrls.indexOf(v) < 0);
            for(let url of obsoleteUrls){
                UrlUnfurl.removeFromAvailableUrls(url);
                UrlUnfurl.removeFromAllUrls(url);
            }

        }, UrlUnfurl.keypressWaitTime);

    },

    processUnfurled : function(resultData){

        AntzLog.log('process resultData');

        //console.log(resultData);

        if(typeof resultData !== 'object'){
            // only happens if no url can be found in the database
            UrlUnfurl.updateView();
            return;
        }

        if(resultData.queued){
            // post a call to the worker process to make sure the job gets processed
            if(resultData.processId){
                UrlUnfurl.addToWorkerQueue(resultData.processId);
            }

        }else{
            // handle the result

            UrlUnfurl.removeFromCurrentFetching(resultData.url);
            UrlUnfurl.removeFromFetchQueue(resultData.url);

            UrlUnfurl.setUrlCache(resultData.url, resultData);

            if(!resultData.valid){
                AntzLog.log('Could not unfurl data for url: '+resultData.url);
                UrlUnfurl.updateView();
                return;
            }

            let ourData = UrlUnfurl.getPreferredUrlData(resultData);

            if(ourData){
                AntzLog.log('Has unfurled data: '+ourData.type, ourData);
                UrlUnfurl.addToAvailableUrls(resultData);
                UrlUnfurl.updateView();
            }else{
                // skip this url
                AntzLog.log('No unfurled data');
                UrlUnfurl.removeFromAvailableUrls(resultData.url);
                UrlUnfurl.updateView();
            }
        }
    }
};

var AntzLog = {
    queues : [],
    states : [],
    logWindow : null,
    logInterval : null,
    logMessages : [],
    lineLength: 70,
    showOutput: true,

    log: function(msg, data){
        AntzLog.logMessages.push({msg,data});
    },

    createLogWindow : function(){
        // disabled in production
        return;

        AntzLog.logWindow = $('<div />').addClass('antzlog-window').append($('<div />').addClass('states')).append($('<div />').addClass('queues'));

        for(let state of AntzLog.states){
            let stateEl = $('<div />').addClass('state '+state.classname);
            stateEl.append($('<div />').text(state.title).addClass(state.classname+'-title state-title'));
            stateEl.append($('<div />').addClass(state.classname+' state-value'));
            $('.states', AntzLog.logWindow).append(stateEl);
        }

        for(let queue of AntzLog.queues){
            let queueEl = $('<div />').addClass('queue '+queue.classname);
            queueEl.append($('<div />').text(queue.title).addClass(queue.classname+'-title queue-title'));
            queueEl.append($('<div />').addClass(queue.classname+' queue-items'));
            $('.queues', AntzLog.logWindow).append(queueEl);
        }

        AntzLog.logWindow.append($('<ul />').addClass('buttons'));

        // add the buttons
        $('.buttons', AntzLog.logWindow).append($('<li />').text('Start/Stop').addClass('start-stop run'));
        AntzLog.logWindow.delegate('.start-stop', 'click', function(e){
            AntzLog.showOutput = !AntzLog.showOutput;
            if(AntzLog.showOutput){
                $(this).removeClass('halt').addClass('run');
            }else{
                $(this).removeClass('run').addClass('halt');
            }
        });

        $('.buttons', AntzLog.logWindow).append($('<li />').text('Clear').addClass('clear-logs'));
        AntzLog.logWindow.delegate('.clear-logs', 'click', function(e){
            $('.log-messages', AntzLog.logWindow).html('');
        });

        AntzLog.logWindow.append($('<ul />').addClass('log-messages'));
        $('body').append(AntzLog.logWindow);

        $(AntzLog.logWindow).draggable();

        if(AntzLog.logInterval !== null){
            clearInterval(AntzLog.logInterval);
        }

        AntzLog.logInterval = setInterval(AntzLog.displayLog, 300);

    },

    addQueue : function(title, classname, handle, mutator){
        AntzLog.queues.push({title, classname, handle, mutator});
    },

    addState : function(title, classname, handle, mutator){
        AntzLog.states.push({title, classname, handle, mutator});
    },

    displayLog : function(){
        if(!AntzLog.showOutput){
            return;
        }
        for(let state of AntzLog.states){
            let logMessage = state.mutator(state.handle);
            $('.state.'+state.classname+' .state-value', AntzLog.logWindow).text(logMessage);
        }
        for(let queue of AntzLog.queues){
            let logs = [];

            for(let item of queue.handle){
                let logMessage = queue.mutator(item);
                logs.push($('<div />').text(logMessage).addClass('queue-item'));
            }

            if(logs.length < 1){
                logs.push($('<div />').text('none...').addClass('queue-item', 'queue-empty'));
            }
            $('.queue-items.'+queue.classname, AntzLog.logWindow).html('');
            for(let log of logs){
                $('.queue-items.'+queue.classname, AntzLog.logWindow).append(log);
            }
        }

        for(let logItem of AntzLog.logMessages.splice(0, AntzLog.logMessages.length)){
            let li = $('<li />');
            let pre = $('<pre />').text(($('.log-messages li', AntzLog.logWindow).get().length+1)+' '+logItem.msg.match(new RegExp('.{1,' + AntzLog.lineLength + '}', 'g')).join("\n"));
            if(typeof logItem.data !== 'undefined'){
                let logItemData = JSON.stringify(logItem.data).substr(0, AntzLog.lineLength * 6);
                pre.text(pre.text()+"\n"+logItemData);
            }
            li.append(pre);
            $('.log-messages', AntzLog.logWindow).prepend(li);
        }

    }

}

