<?php
/**
 * Project: yii2-ckeditor-widget
 * User: Max.wen
 * Date: <2016/09/29 - 14:53>
 */

namespace maxwen\ckeditor;


use yii\web\AssetBundle;

/**
 * Class CKEditorWidgetAsset
 * @package maxwen\ckeditor
 */
class CKEditorWidgetAsset extends AssetBundle
{
	public $sourcePath = '@vendor/maxwen/yii2-ckeditor-widget/assets';

	public $js = [
		'dosamigos-ckeditor.widget.js'
	];

	public $depends = [
		'maxwen\ckeditor\CKEditorAsset'
	];
}