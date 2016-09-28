/**
 * @license Copyright (c) 2003-2016, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	
	//rabtor 2013-5 修改
    config.language = 'zh-cn'; //中文
    config.font_names = '宋体;楷体;黑体;微软雅黑;隶书;仿宋;Arial;Comic Sans MS;Courier New;Tahoma;Times New Roman;Verdana';
	

	//自定义工具栏
    config.toolbar_MyToolbar =
    [
	    ['Paste', 'PasteText', 'PasteFromWord'],
	    ['Undo', 'Redo', '-', 'Find', 'Replace'],
	    ['Source', 'Preview'],
	    ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'],
	    ['Outdent', 'Indent'],
	    ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
	    '/',
	    ['Format', 'Font', 'FontSize'],
	    ['Image', 'Flash', 'jwplayer', 'Table', 'HorizontalRule', 'Iframe'],
	    ['Link', 'Unlink', 'Anchor'], 
	    ['TextColor', 'BGColor'],
	    ['SelectAll', 'Maximize', 'ShowBlocks']		// No comma for the last row.
    ];
    config.toolbar_Full =
    [
	    { name: 'document', items: ['Source', '-', 'Save', 'NewPage', 'DocProps', 'Preview', 'Print', '-', 'Templates'] },
	    { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
	    { name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll', '-', 'SpellChecker', 'Scayt'] },
	    { name: 'forms', items: ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'] },
	    '/',
	    { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'] },
	    { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl'] },
	    { name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
	    { name: 'insert', items: ['Image', 'Flash', 'jwplayer', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe'] },
	    '/',
	    { name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize'] },
	    { name: 'colors', items: ['TextColor', 'BGColor'] },
	    { name: 'tools', items: ['Maximize', 'ShowBlocks', '-', 'About'] }
    ];
    config.toolbar_Basic =
     [
         ['Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink', '-', 'About']
     ];
	 
	var ckpath = CKEDITOR.basePath+'../ckfinder';  //上级目录,可根据ckfinder的实际目录作相应修改
    config.filebrowserBrowseUrl = ckpath + '/ckfinder.html';                  //上传文件的查看路径    
    config.filebrowserImageBrowseUrl = ckpath + '/ckfinder.html?Type=Images'; //上传图片的查看路径    
    config.filebrowserFlashBrowseUrl = ckpath + '/ckfinder.html?Type=Flash';  //上传Flash的查看路径    
    config.filebrowserUploadUrl = ckpath + '/core/connector/php/connector.php?command=QuickUpload&type=Files';        //上传文件的保存路径    
    config.filebrowserImageUploadUrl = ckpath + '/core/connector/php/connector.php?command=QuickUpload&type=Images'; //上传图片的保存路径    
    config.filebrowserFlashUploadUrl = ckpath + '/core/connector/php/connector.php?command=QuickUpload&type=Flash';  //上传Flash的保存路径
	
    config.extraPlugins = 'jwplayer';//扩展插件，插入视频
	
	config.allowedContent = true;//内容过滤器，对节点的属性进行过滤，对javascript进行过滤
	config.toolbar = "Full"; //工具栏
    // config.skin = "moono_blue";//皮肤
    config.image_previewText = ' ';
};
