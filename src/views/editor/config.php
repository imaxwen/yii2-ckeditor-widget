<?php
/**
 * Project: yii2-ckeditor-widget.
 * User: Max.wen
 * Date: <2016/09/29 - 17:17>
 * @var array $editorConfig
 */

?>
CKEDITOR.editorConfig = function( config ) {
	<?php foreach ($editorConfig as $key => $option): ?>
		<?php
		switch ($option){
			case is_string($option):
				$option = '"'.$option.'"';
				break;
			case is_array($option):
				$option = \yii\helpers\Json::encode($option);
				break;
			case is_bool($option);
				$option = $option ? 'true' : 'false';
				break;
		}
		?>
	config.<?=$key?> = <?=$option?>; <?="\n" ?>
	<?php endforeach; ?>
};