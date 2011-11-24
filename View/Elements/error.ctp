<div class="uploader error">
	<?php
		$list = '';
		foreach ($uploadErrors as $err){
			foreach ($err as $rule){
				$list .= $this->Html->tag('li', $error[$rule]);
			}
		}

		$upload = array_shift($upload);
		echo __d('uploader', 'Uploading the file `%s` failed:', $upload['name']);
		echo $this->Html->tag('ul', $list);
	?>
</div>
