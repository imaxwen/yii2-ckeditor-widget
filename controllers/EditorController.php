<?php
/**
 * Project: yii2-ckeditor-widget.
 * User: Max.wen
 * Date: <2016/09/30 - 16:08>
 */

namespace maxwen\ckeditor\controllers;

use yii;
use yii\web\Controller;
use yii\helpers\ArrayHelper;

class EditorController extends Controller
{
	public $layout = false;

	public function actionConfig()
	{
		Yii::$app->response->format = yii\web\Response::FORMAT_RAW;
		$headers = Yii::$app->response->headers;
		$headers->add('Content-Type', 'application/javascript; charset=UTF-8');
		$headers->add('Cache-Control', 'public, max-age=31536000');
		$editorConfig = require(__DIR__ . '/../config.php');
		$editorConfig = ArrayHelper::merge($editorConfig, Yii::$app->params['ckeditorConfig']);

		return $this->render('config', [
			'editorConfig' => $editorConfig
		]);
	}
}