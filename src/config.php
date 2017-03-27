<?php
/**
 * Project: yii2-ckeditor-widget.
 * User: Max.wen
 * Date: <2016/10/09 - 15:47>
 */

// define finder assetPath and Url route here.
//$assetUrl    = Yii::$app->assetManager->getPublishedUrl('@vendor/maxwen/yii2-ckeditor-widget/assets');
//$finderPath  = $assetUrl   . '/ckfinder';
//$finderRoute = $finderPath . '/ckfinder.html';

return [
	// custom toolbar presets
	// you can define your own preset named as "toolbar_Mypreset"
	'toolbar_Basic' => [
		['Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink', '-', 'About']
	],
	'toolbar_Standard' => [
		['Paste', 'PasteText', 'PasteFromWord'],
		['Undo', 'Redo', '-', 'Find', 'Replace'],
		['Source', 'Preview'],
		['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'],
		['Outdent', 'Indent'],
		['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
		['Format', 'Font', 'FontSize'],
		['Image', 'Flash', 'Table', 'HorizontalRule', 'Iframe'],
		['Link', 'Unlink', 'Anchor'],
		['TextColor', 'BGColor'],
		['SelectAll', 'Maximize', 'ShowBlocks']
	],
	'toolbar_Full' => [
		[ 'name'=> 'document', 'items' => ['Source', '-', 'Save', 'NewPage', 'DocProps', 'Preview', 'Print', '-', 'Templates'] ],
		[ 'name'=> 'clipboard', 'items' => ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] ],
		[ 'name'=> 'editing', 'items' => ['Find', 'Replace', '-', 'SelectAll', '-', 'SpellChecker', 'Scayt'] ],
		[ 'name'=> 'forms', 'items' => ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'] ],
		[ 'name'=> 'basicstyles', 'items' => ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'] ],
		[ 'name'=> 'paragraph', 'items' => ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl'] ],
		[ 'name'=> 'links', 'items' => ['Link', 'Unlink', 'Anchor'] ],
		[ 'name'=> 'insert', 'items' => ['Image', 'Flash', 'Video', 'Audio', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe'] ],
		[ 'name'=> 'styles', 'items' => ['Styles', 'Format', 'Font', 'FontSize'] ],
		[ 'name'=> 'colors', 'items' => ['TextColor', 'BGColor'] ],
		[ 'name'=> 'tools', 'items' => ['Maximize', 'ShowBlocks', '-', 'About'] ]
	],
	// EOF presets

	// add other custom options here
	'allowedContent'    => true,
	'toolbar'           => 'Standard',
	'image_previewText' => ' ',
	'extraPlugins'      => 'video,audio', # extra plugins in assets/plugins


	// ckfinder options
	//	'filebrowserBrowseUrl'      => $finderRoute,
	//	'filebrowserImageBrowseUrl' => $finderRoute . '?Type=Images',
	//	'filebrowserFlashBrowseUrl' => $finderRoute . '?Type=Flash',
	//	'filebrowserUploadUrl'      => $finderPath  . '/core/connector/php/connector.php?command=QuickUpload&type=Files',
	//	'filebrowserImageUploadUrl' => $finderPath  . '/core/connector/php/connector.php?command=QuickUpload&type=Images',
	//	'filebrowserFlashUploadUrl' => $finderPath  . '/core/connector/php/connector.php?command=QuickUpload&type=Flash',
];