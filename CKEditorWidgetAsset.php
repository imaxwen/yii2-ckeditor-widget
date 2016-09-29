<?php
/**
 * Project: yii2-adminlte-rbac.
 * User: Max.wen
 * Date: <2016/09/29 - 14:53>
 */

namespace maxwen\ckeditor;


use yii\web\AssetBundle;

class CKEditorWidgetAsset extends AssetBundle
{
	public $sourcePath = 'assets';

	public $js = [
		'ckeditor.widget.js'
	];

	public $depends = [
		'maxwen\ckeditor\CKEditorAsset'
	];
}