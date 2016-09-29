<?php
/**
 * Project: yii2-adminlte-rbac.
 * User: Max.wen
 * Date: <2016/09/29 - 17:12>
 */

namespace maxwen\ckeditor\controllers;


use yii\web\Controller;

class EditorController extends Controller
{

	public function actionGetconfig()
	{
		header('Content-Type: application/x-javascript');
		return $this->render('config');
	}
}