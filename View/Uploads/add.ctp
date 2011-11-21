<?php
	echo $this->Form->create('Upload', array('url' => array('plugin' => 'uploader', 'controller' => 'uploads', 'action' => 'add'), 'type' => 'file'));
	echo $this->Form->input('Upload.model', array('type' => 'select', 'options' => $models));
	echo $this->Form->input('Upload.alias', array(
		'type' => 'select',
		'options' => $aliases,
		'selected' => $this->data['Upload']['alias']
	));
	echo $this->Form->input('Upload.foreign_key');
	echo $this->Form->input('Upload.title');
	echo $this->Form->input('Upload.description');
	echo $this->Form->file('Upload.file', array(
		'multiple' => 'multiple',
		'name' => 'Upload[]',
		'error' => array(
			'maxSize' => __d('uploader', 'The file is too large', true),
			'fileType' => __d('uploader', 'This filetype is not allowed for uploads', true),
			'max' => __d('uploader', 'Too many uploads yet', true),
			'isUploadedFile' => __d('uploader', 'Illegal upload', true),
			'isNoError' => __d('uploader', 'Upload failed', true)
		)
	));
	foreach ($errors as $file => $error){
			debug ($error);
		echo $this->Html->tag('li', sprintf('%s: %s', $file, $error));
	}
	echo $this->Form->end(__d('uploader', 'Upload', true));
?>
