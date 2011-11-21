<div class="uploader-upload-properties Upload<?php echo $Upload['alias']; ?>">
	<?php
	echo $this->Html->css('/uploader/css/uploader', null, array('inline' => false));
	echo $this->Html->image($Upload['icon']);
	?>
	<dl>
		<dt><?php echo __d('uploader', 'Title', true); ?></dt>
		<dd><?php echo $Upload['title']; ?></dd>
		<dt><?php echo __d('uploader', 'Description', true); ?></dt>
		<dd><?php echo $this->Text->truncate($Upload['description']); ?></dd>
		<dt><?php echo __d('uploader', 'Filename', true); ?></dt>
		<dd><?php echo $Upload['name']; ?></dd>
		<dt><?php echo __d('uploader', 'Upload type', true); ?></dt>
		<dd><?php echo $Upload['alias']; ?></dd>
		<dt><?php echo __d('uploader', 'Belongs to', true); ?></dt>
		<dd><?php echo $Upload['model']?>#<?php echo $Upload['foreign_key']; ?></dd>
		<dt><?php echo __d('uploader', 'Type', true); ?></dt>
		<dd><?php echo $Upload['type']; ?></dd>
		<dt><?php echo __d('uploader', 'Size', true); ?></dt>
		<dd><?php echo $this->Number->toReadableSize($Upload['size']); ?></dd>
		<dt><?php echo __d('uploader', 'Uploaded', true); ?></dt>
		<dd><?php echo $this->Time->niceShort($Upload['created']);?></dd>
		<dt><?php echo __d('uploader', 'Files', true); ?></dt>
		<dd>
			<ul>
				<?php foreach ($Upload['files'] as $name => $file){
					echo $this->Html->tag('li',
						$this->Html->link($Upload['filename'], DS . $file, array('title' => $file))
					);
				}
				?>
			</ul>
		</dd>
	</dl>
</div>
