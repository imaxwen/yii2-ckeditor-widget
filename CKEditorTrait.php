<?php
/**
 * Project: yii2-ckeditor-widget.
 * User: Max.wen
 * Date: <2016/09/29 - 14:33>
 */
namespace maxwen\ckeditor;

use yii;
use yii\helpers\Url;

/**
 * Class CKEditorTrait
 * @package maxwen\ckeditor
 */
trait CKEditorTrait
{

	/**
	 * Additional CKEditor.config options
	 * The main config options merged from config.php and `@common/config/params.php ckeditorConfig`
	 * @var array
	 */
	public $editorConfig = [];

	/**
	 * Initializes the widget options.
	 * This method sets the default values for various options.
	 */
	protected function initOptions()
	{
		if(!isset($this->editorConfig['customConfig']) || empty($this->editorConfig['customConfig']) ) {
			$this->editorConfig['customConfig'] = Url::toRoute(['ckeditor/config']);
		}
	}

	/**
	 * register thirdparty plugins for CKEditor
	 */
	public function registerEditorPlugins()
	{
		$js = [];
		$view = $this->getView();

		// register H5 video & audio plugins
		$assetBundle = new CKEditorWidgetAsset();
		$relativeAssetPath = '/assets/'. basename(Yii::$app->assetManager->getPublishedPath($assetBundle->sourcePath)) . '/ckeditor/plugins';
		$js[] = "CKEDITOR.plugins.addExternal('video', '$relativeAssetPath/video/plugin.js', '');";
		$js[] = "CKEDITOR.plugins.addExternal('audio', '$relativeAssetPath/audio/plugin.js', '');";

		$view->registerJs(implode("\n", $js));
	}
}
