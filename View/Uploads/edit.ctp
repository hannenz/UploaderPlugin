<?php
echo $this->Form->create('Upload', array('action' => 'edit', 'type' => 'file'));
echo $this->Form->input('Upload.id');
echo $this->Form->input('Upload.name', array(
	'label' => __d('uploader', 'Filename')
));
echo $this->Form->input('Upload.title', array(
	'label' => __d('uploader', 'Title')
));
echo $this->Form->input('Upload.description', array(
	'label' => __d('uploader', 'Description')
));
echo $this->Form->input('Upload.type', array(
	'type' => 'select',
	'label' => __d('uploader', 'Type')
));

echo $this->Html->tag('label', __d('uploader', 'Poster image'));
if (!empty($this->data['Upload']['poster'])){
	echo $this->Html->image($this->data['Upload']['poster'], array('style' => 'width:160px'));
	echo $this->Html->link(__d('uploader', 'Delete poster'), array('plugin' => 'uploader', 'controller' => 'uploads', 'action' => 'delete_poster', $this->data['Upload']['id']));
}
echo $this->Form->file('poster', array('name' => 'Poster'));

echo $this->Form->end(__d('uploader', 'Save changes'));
?>
