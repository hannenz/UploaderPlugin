<div>
	<?php
		$list = '';
		foreach ($uploadErrors as $err){
			foreach ($err as $rule){
				$list .= $this->Html->tag('li', $error[$rule]);
			}
		}

		$upload = array_shift($upload);
		echo __d('uploader', 'I tried to upload the %s `%s` but errors occured:', $upload['alias'], $upload['name']);
		echo $this->Html->tag('ul', $list);
	?>
</div>
