# yii2-ckeditor-widget
CKEditor widget for yii2, with ckfinder integrated   
This repo was forked from [2amigos/yii2-ckeditor-widget](https://github.com/2amigos/yii2-ckeditor-widget).    
I made some customization and most impotant: with ckfinder integrated.

## Installation
```
composer require maxwen/yii2-ckeditor-widget
```


## Configuration

Inorder to add your custom configure,   
you need to add an controllerMap to you config/main.php:
```php
'controllerMap' => [
	'ckeditor' => [
		'class'    => 'maxwen\ckeditor\controllers\EditorController',
		'viewPath' => '@vendor/maxwen/yii2-ckeditor-widget/views/editor'
	]
]
```

Then add you custom configure into params.php:
see [CKEditor.config](http://docs.ckeditor.com/#!/api/CKEDITOR.config)

```php
 // CKEditor config rewrite
'ckeditorConfig' => [
		// custom options
		'language'   => 'en',
		'font_names' => 'Arial;Comic Sans MS;Courier New;Tahoma;Times New Roman;Verdana',
		'toolbar'    => 'Full',
		'skin'       => 'your skin name here'
		// etc...
	],
	
```


Usage
-----
The library comes with two widgets: `CKEditor` and `CKEditorInline`. One is for classic edition and the other for inline
editing respectively.

Using a model with a basic preset:

```php

use maxwen\ckeditor\CKEditor;


<?= $form->field($model, 'text')->widget(CKEditor::className(), [
        'options' => ['rows' => 6],
        'editorConfig' => [
        	'customConfig' => 'http://yourdoman.com/ckeditor/config.js',
        	// etc ...
        ]
    ]) ?>
```
Using inline edition with basic preset:

```

use maxwen\ckeditor\CKEditorInline;

<?php CKEditorInline::begin([
	'editorConfig' => [
		// ...
	]
	]);?>
	
    This text can be edited now :)
    
<?php CKEditorInline::end();?>
```

How to add custom plugins
-------------------------
This is the way to add custom plugins to the editor. Since version 2.0 we are working with the packagist version of the 
CKEditor library, therefore we are required to use its configuration API in order to add external plugins. 

Lets add the popular [Code Editor Plugin](http://ckeditor.com/addon/pbckcode) for example. This plugin would allow us to 
add a button to our editor's toolbar so we can add code to the content we are editing. 

Assuming you have downloaded the plugin and added to the root directory of your Yii2 site. I have it this way: 

<pre>
+ frontend 
+ -- web 
    + -- pbckcode 
</pre>

We can now add it to our CKEditor widget. For this example I am using `CKEditorInline` widget. One thing you notice on 
this example is that we do not use the preset attribute; this is highly important as we want to add a customized toolbar to our 
widget. No more talking, here is the code:
 
```php 
<?php
 
use maxwen\ckeditor\CKEditorInline;

// First we need to tell CKEDITOR variable where is our external plufin 
$this->registerJs("CKEDITOR.plugins.addExternal('pbckcode', '/pbckcode/plugin.js', '');");

// ... 
// Using the plugin
<?php CKEditorInline::begin(['editorConfig' => [
    'extraPlugins' => 'pbckcode',
    'toolbarGroups' => [
        ['name' => 'undo'],
        ['name' => 'basicstyles', 'groups' => ['basicstyles', 'cleanup']],
        ['name' => 'colors'],
        ['name' => 'links', 'groups' => ['links', 'insert']],
        ['name' => 'others', 'groups' => ['others', 'about']],
        
        ['name' => 'pbckcode'] // <--- OUR NEW PLUGIN YAY!
    ]
]]) ?>

<p>
    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
    dolore magna aliqua. 
</p>
<?php CKEditorInline::end() ?>
```

About CKFinder
--------------
[CKFinder](https://cksource.com/ckfinder) is a commercial software, this repo just contains a demo version, you can [purchase the full version here](https://cksource.com/ckfinder/buy).


Further Information
-------------------
Please, check the [CKEditor plugin site](http://www.ckeditor.com) documentation for further information about its configuration options.
