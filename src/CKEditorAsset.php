<?php
/**
 * Project: yii2-ckeditor-widget.
 * User: Max.wen
 * Date: <2016/09/29 - 14:26>
 */
namespace maxwen\ckeditor;

use yii\web\AssetBundle;

class CKEditorAsset extends AssetBundle
{
	public $sourcePath = '@vendor/ckeditor/ckeditor';

	public $js = [
		'ckeditor.js',
		'adapters/jquery.js'
	];

	public $depends = [
		'yii\web\YiiAsset',
		'yii\web\JqueryAsset'
	];
}