<?php
/**
 * Project: yii2-ckeditor-widget.
 * User: Max.wen
 * Date: <2016/09/29 - 14:33>
 */

namespace maxwen\ckeditor;

use yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\InputWidget;

/**
 * Class CKEditor
 * @package maxwen\ckeditor
 */
class CKEditor extends InputWidget
{
	use CKEditorTrait;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		$this->initOptions();
	}

	/**
	 * @inheritdoc
	 */
	public function run()
	{
		if ($this->hasModel()) {
			echo Html::activeTextarea($this->model, $this->attribute, $this->options);
		} else {
			echo Html::textarea($this->name, $this->value, $this->options);
		}
		$this->registerPlugin();
	}

	/**
	 * Registers CKEditor plugin
	 * @codeCoverageIgnore
	 */
	protected function registerPlugin()
	{
		$this->registerEditorPlugins();
		$js = [];
		$view = $this->getView();

		CKEditorWidgetAsset::register($view);
		$id = $this->options['id'];
		$options = $this->editorConfig !== false && !empty($this->editorConfig) ? Json::encode($this->editorConfig) : '{}';
		$js[] = "CKEDITOR.replace('$id', $options);";
		$js[] = "dosamigos.ckEditorWidget.registerOnChangeHandler('$id');";

		if (isset($this->editorConfig['filebrowserUploadUrl']) || isset($this->editorConfig['filebrowserImageUploadUrl'])) {
			$js[] = "dosamigos.ckEditorWidget.registerCsrfImageUploadHandler();";
		}


		$view->registerJs(implode("\n", $js));
	}

}