<?php
	echo $this->Html->image($upload['icon']);
?>
<dl>
<?php
	echo $this->Html->tag('dt', __d('uploader', 'Pos', true));
	echo $this->Html->tag('dd', '#'.$upload['pos']);
	echo $this->Html->tag('dt', __d('uploader', 'Filename', true));
	echo $this->Html->tag('dd', $upload['name']);
	echo $this->Html->tag('dt', __d('uploader', 'Size', true));
	echo $this->Html->tag('dd', $this->Number->toReadableSize($upload['size']));
	echo $this->Html->tag('dt', __d('uploader', 'Type', true));
	echo $this->Html->tag('dd', $upload['type']);
	echo $this->Html->tag('dt', __d('uploader', 'Actions', true));
	echo $this->Html->tag('dd', join(' | ', array($this->Html->link(__d('uploader', 'edit', true), '/uploader/uploads/edit/' . $upload['id']), $this->Html->link(__d('uploader', 'delete', true), '/uploader/uploads/delete/' . $upload['id']))));
?>
</dl>
