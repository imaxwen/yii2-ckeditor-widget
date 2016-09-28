//JWPlayerForCKEditor V2.0
//rabtor 2013-12 升级，支持JW Player 6.x和CKEditor 4.x，支持mp4视频在PC端和移动端都能播放
//本插件所用的视频播放器为JW Player 6.7破解版，带8套皮肤，完全去除Logo和右键菜单
CKEDITOR.plugins.add('jwplayer', {
	requires: ['dialog'],
	init: function(editor) {
		var pluginName = 'jwplayer';
		CKEDITOR.dialog.add(pluginName, this.path + 'dialogs/jwplayer.js');
		CKEDITOR.scriptLoader.load(CKEDITOR.plugins.get('jwplayer').path + 'jwplayer/jwplayer.js', function(completed, failed) {
			//jwplayer.key = ""
		});
		editor.addCommand(pluginName, new CKEDITOR.dialogCommand(pluginName));
		CKEDITOR.addCss('.boxJWPlayer{border:1px dotted red;}');
		editor.ui.addButton('jwplayer', {
			label: '添加视频',
			command: pluginName,
			icon: this.path + 'jwplayer/jwPlayer.gif'
		});

		//创建右键菜单
		if ( editor.contextMenu ) {
            editor.addMenuGroup( 'mediaGroup' );
            editor.addMenuItem( 'mediaItem', {
                label: '视频属性',
                icon: this.path + 'jwplayer/jwPlayer.gif',
                command: pluginName,
                group: 'mediaGroup'
            });

            editor.contextMenu.addListener( function( element ) {
                if ( element.getAscendant( 'div', true )&&(element.hasClass('boxJWPlayer')||element.getParent().hasClass('boxJWPlayer')||element.hasClass('jw_video')) ) {
                    return { mediaItem: CKEDITOR.TRISTATE_OFF };
                }
            });
        }
	}
});