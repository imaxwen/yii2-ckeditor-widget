CKEDITOR.dialog.add('jwplayer', function(editor) {
    var JWplayerJS = CKEDITOR.plugins.get('jwplayer').path + 'jwplayer/jwplayer.js';
    var JWplayerAttr = new Object();

    function UpdatePreview() {
        ReturnPlayer();
    }

    function ReturnPlayer() {
        var fileUrl = CKEDITOR.dialog.getCurrent().getContentElement('info', 'video_url').getValue();
        var filePreUrl = CKEDITOR.dialog.getCurrent().getContentElement('info', 'video_pre_url').getValue();
        var width = CKEDITOR.dialog.getCurrent().getContentElement('info', 'width').getValue();
        var height = CKEDITOR.dialog.getCurrent().getContentElement('info', 'height').getValue();
        var auto = CKEDITOR.dialog.getCurrent().getContentElement('info', 'auto').getValue();
        var skin = '';
        var selectskin = CKEDITOR.dialog.getCurrent().getContentElement('info', 'skin').getValue();
        if (selectskin != '') {
            skin = CKEDITOR.plugins.get('jwplayer').path + "jwplayer/skins/" + selectskin + ".xml"
        }
        var image = '';
        if (filePreUrl && filePreUrl != '') {
            image = "&image=" + filePreUrl;
        }
        //实时预览视频
        jwplayer("jw_video").setup({
            "file": fileUrl,
            "image": filePreUrl,
            "width": "480",
            "height": "320",
            "autostart": auto,
            "controlbar": "bottom",
            "abouttext": "",
            "aboutlink": "",
            "skin": skin,
            "analytics": {
                "enabled": "false",
                "cookies": "false"
            }
        });
        JWplayerAttr.file = fileUrl;
        JWplayerAttr.image = filePreUrl;
        JWplayerAttr.width = width;
        JWplayerAttr.height = height;
        JWplayerAttr.auto = auto;
        JWplayerAttr.skin = skin;
        return JWplayerAttr;
    }
    return {
        title: '添加视频文件',
        minWidth: 460,
        minHeight: 300,
        contents: [{
            id: 'info',
            label: '',
            title: '',
            expand: true,
            padding: 0,
            elements: [{
                type: 'vbox',
                widths: ['280px', '30px'],
                align: 'left',
                children: [{
                    type: 'hbox',
                    widths: ['280px', '30px'],
                    align: 'left',
                    children: [{
                        type: 'text',
                        id: 'video_url',
                        label: '选择视频文件或者视频文件的URL',
                        setup: function(jwJson) {
                            this.setValue(jwJson.file);
                        },
                        onChange: UpdatePreview
                    }, {
                        type: 'button',
                        id: 'browse',
                        filebrowser: 'info:video_url',
                        label: editor.lang.common.browseServer,
                        style: 'display:inline-block;margin-top:8px;'
                    }]
                }, {
                    type: 'hbox',
                    widths: ['280px', '30px'],
                    align: 'left',
                    children: [{
                        type: 'text',
                        id: 'video_pre_url',
                        label: '选择视频预览图片或者图片的URL',
                        setup: function(jwJson) {
                            this.setValue(jwJson.image);
                        },
                        onChange: UpdatePreview
                    }, {
                        type: 'button',
                        id: 'browse_pre',
                        filebrowser: 'info:video_pre_url',
                        label: editor.lang.common.browseServer,
                        style: 'display:inline-block;margin-top:8px;'
                    }]
                }, {
                    type: 'hbox',
                    widths: ['280px', '10px'],
                    align: 'left',
                    children: [{
                        type: 'vbox',
                        widths: ['280px', '10px'],
                        align: 'left',
                        children: [{
                            type: 'select',
                            id: 'skin',
                            'default': 'vapor',
                            label: '选择播放器皮肤',
                            items: [
                                ['vapor', 'vapor'],
                                ['bekle', 'bekle'],
                                ['five', 'five'],
                                ['six', 'five'],
                                ['stormtrooper', 'stormtrooper'],
                                ['White-Flat-Skin-for-JW6', 'White-Flat-Skin-for-JW6'] 
                            ],
                            setup: function(jwJson) {
                                var jwSkin = "";
                                if (jwJson.skin != "") {
                                    jwSkin = jwJson.skin;
                                    var lastPipe = jwSkin.lastIndexOf('/');
                                    var lastPeriod = jwSkin.lastIndexOf('.');
                                    if (lastPipe < lastPeriod)
                                        jwSkin = jwSkin.substring(lastPipe + 1, lastPeriod);
                                }
                                this.setValue(jwSkin);
                            },
                            onChange: UpdatePreview
                        }, {
                            type: 'text',
                            id: 'width',
                            style: 'width:95px',
                            label: '宽度',
                            'default': '480',
                            setup: function(jwJson) {
                                this.setValue(jwJson.width);
                            },
                            commit: function(element) {
                                var boxwidth = this.getValue().toLowerCase();
                                if (boxwidth.indexOf("px") < 0)
                                    boxwidth = boxwidth + "px";
                                element.setStyle('width', boxwidth);
                            },
                            onChange: UpdatePreview
                        }, {
                            type: 'text',
                            id: 'height',
                            style: 'width:95px',
                            label: '高度',
                            'default': '320',
                            setup: function(jwJson) {
                                this.setValue(jwJson.height);
                            },
                            commit: function(element) {
                                var boxheight = this.getValue().toLowerCase();
                                if (boxheight.indexOf("px") < 0)
                                    boxheight = boxheight + "px";
                                element.setStyle('height', boxheight);
                            },
                            onChange: UpdatePreview
                        }, {
                            type: 'checkbox',
                            id: 'auto',
                            'default': false,
                            label: editor.lang.flash.chkPlay,
                            setup: function(jwJson) {
                                this.setValue(jwJson.autostart == "true");
                            },
                            onChange: UpdatePreview
                        }]
                    }, {
                        type: 'vbox',
                        widths: ['280px', '10px'],
                        align: 'left',
                        children: [{
                            type: 'html',
                            id: 'preview',
                            html: '<div id="jw_video">加载预览视频...</div>'
                        }]
                    }]
                }]
            }]
        }],
        buttons: [CKEDITOR.dialog.okButton, CKEDITOR.dialog.cancelButton],
        onShow: function() {
            var selection = editor.getSelection();
            var element = selection.getStartElement();
            if (element && element.getName() == 'div' && element.hasClass('boxJWPlayer')) {
                this.insertMode = false;
            }
            else if(element.getParent().getName() == 'div' && element.getParent().hasClass('boxJWPlayer')){
                element = element.getParent();
                this.insertMode = false;
            }
            else {
                if (element)
                    element = element.getAscendant('div', true);

                if (element && element.getName() == 'div' && element.hasClass('jw_video')) {
                    element = element.getParent();
                    this.insertMode = false;
                } else {
                    this.insertMode = true;
                }
            }
            this.element = element;

            if (element && element.getId() && element.hasClass('boxJWPlayer')) {
                var JWScript = decodeURIComponent(element.getHtml());
                var reg = /setup\(([\s\S]*)\)/;
                var JWScriptArr = JWScript.match(reg);
                var JWJson = eval('(' + JWScriptArr[1] + ')');
                if (!this.insertMode)
                    this.setupContent(JWJson); //this function will call all the setup function for all uiElements
            }
        },
        onOk: function() {
            var jwAttr = ReturnPlayer();
            if (this.element && !this.insertMode) { //编辑播放器
                var currentElement = this.element;
                this.commitContent(currentElement);
                currentElement.getLast().remove();
                var currentJWID = currentElement.getChild(2).getId();
                var jwJS = '<script type="text/javascript">jwplayer("' + currentJWID + '").setup({"file": "' + jwAttr.file + '","image": "' + jwAttr.image + '","width": "' + jwAttr.width + '","height": "' + jwAttr.height + '","autostart": "' + jwAttr.auto + '","skin": "' + jwAttr.skin + '","controlbar": "bottom","analytics":{"enabled":"false","cookies":"false"}});</script>'
                this.element.appendHtml(jwJS);
                editor.updateElement();
            } else { //添加播放器
                var eData = editor.getData();
                //支持在一个页面中添加多个视频
                var i = 1;
                while (eData.indexOf("boxJWPlayer" + i) >= 0) {
                    i++;
                }
                var JWBoxID = "boxJWPlayer" + i;
                var JWID = "jw_video" + i;
                var JWHtml = '<div id="' + JWBoxID + '" class="boxJWPlayer" style="margin: 0px auto;width:' + jwAttr.width + 'px;height:' + jwAttr.height + 'px;"><script type="text/javascript" src="' + JWplayerJS + '"></script><div id="' + JWID + '" class="jw_video">加载视频...</div><script type="text/javascript">jwplayer("' + JWID + '").setup({"file": "' + jwAttr.file + '","image": "' + jwAttr.image + '","width": "' + jwAttr.width + '","height": "' + jwAttr.height + '","autostart": "' + jwAttr.auto + '","skin": "' + jwAttr.skin + '","controlbar": "bottom","analytics":{"enabled":"false","cookies":"false"}});</script></div>'
                var newElement = CKEDITOR.dom.element.createFromHtml(JWHtml, editor.document);
                this.commitContent(newElement);
                editor.insertElement(newElement);
            }
        }
    }
});