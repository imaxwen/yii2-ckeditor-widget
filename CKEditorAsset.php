<?php
/**
 * Project: yii2-adminlte-rbac.
 * User: Max.wen
 * Date: <2016/09/29 - 14:26>
 */
namespace maxwen\ckeditor;

class CKEditorAsset extends \yii\web\AssetBundle
{
	public $sourcePath = '@bower/ckeditor';

	public $js = [
		'ckeditor.js',
		'adapters/jquery.js'
	];

	public $depends = [
		'yii\web\YiiAsset',
		'yii\web\JqueryAsset'
	];
}