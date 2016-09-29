<?php
/**
 * Project: yii2-adminlte-rbac.
 * User: Max.wen
 * Date: <2016/09/29 - 14:55>
 */

namespace maxwen\ckeditor;


class CKEditorTrait
{
	public $toolbar = 'standard';

	public $clientOptions = [];

	protected function initOptions()
	{
		$options = [];
		switch ($this->toolbar) {
			case 'custom':
				$preset = null;
				break;
			case 'basic':
			case 'full':
			case 'standard':
				$preset = 'presets/' . $this->preset . '.php';
				break;
			default:
				$preset = 'presets/standard.php';
		}
		if ($preset !== null) {
			$options = require($preset);
		}
		$this->clientOptions = ArrayHelper::merge($options, $this->clientOptions);
	}
}