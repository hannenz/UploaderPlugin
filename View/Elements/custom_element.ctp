<?php
	/*
	 * This could be a custom element to render the items
	 * that the UploaderFormHelper outputs when displaying the
	 * list of uploads.
	 *
	 * Pass the element's name as option parameter when calling
	 * UploaderFormHelper::file()
	 *
	 * The $upload variable contains the upload's data
	 */

	// Output a checkbox to allow selecting multiple uploads to delete
	echo $this->Form->input('uploadsToDelete', array(
		'type' => 'checkbox',
		'label' => false,
		'value' => $upload['id'],
		'name' => 'uploadsToDelete[]',
		'hiddenField' => false,
		'id' => false
	));

	// Display upload; do what you want here...
	echo $this->Html->image($upload['icon'], array('title' => sprintf("%s\n%s", $upload['name'], $this->Number->toReadableSize($upload['size']))));
	//~ echo 'I AM A CUSTOM ELEMENT! >> ' . $upload['name'];
?>
<ul class="uploader-list-actions">
	<li><?php echo $this->Html->link(__('edit'), array('plugin' => 'uploader', 'controller' => 'uploads', 'action' => 'edit', $upload['id']), array('title' => __d('uploader', 'Edit this upload'), 'class' => 'uploader-list-edit')); ?></li>
	<li><?php echo $this->Html->link(__('up'), array('plugin' => 'uploader', 'controller' => 'uploads', 'action' => 'move', $upload['id'], -1), array('title' => __d('uploader', 'Move up'), 'class' => 'uploader-list-up')); ?></li>
	<li><?php echo $this->Html->link(__('down'), array('plugin' => 'uploader', 'controller' => 'uploads', 'action' => 'move', $upload['id'], 1), array('title' => __d('uploader', 'Move down'), 'class' => 'uploader-list-down')); ?></li>
	<li><?php echo $this->Html->link(__('delete'), array('plugin' => 'uploader', 'controller' => 'uploads', 'action' => 'delete', $upload['id']), array('title' => __d('uploader', 'Delete this upload'), 'class' => 'uploader-list-delete'), __d('uploader', 'Do you really want to delete this upload?')); ?></li>
</ul>


