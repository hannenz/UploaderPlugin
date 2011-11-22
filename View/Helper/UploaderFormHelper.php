<?php
/*
 * uploader_form.php
 *
 * Copyright 2011 Johannes Braun <me@hannenz.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 *
 */

class UploaderFormHelper extends AppHelper {

	public $helpers = array('Html', 'Form', 'Number', 'Text', 'Session');

	function file($uploadAlias, $options = array()){
		$options = array_merge(array(
			'multiple' => false,
		),
		$options);
		if (empty($options['label'])){
			$options['label'] = Inflector::pluralize($uploadAlias);
		}
		$uploadErrors = (isset($this->_View->viewVars['uploadErrors']))
			? $this->_View->viewVars['uploadErrors']
			: array()
		;

		$out = '';
		$cssClass = array('input', 'file');
		if (!empty($uploadErrors[$uploadAlias])){
			$cssClass[] = 'error';
		}

		$inputName = $uploadAlias;
		$inputOpts = array(
			'id' => false
		);
		if ($options['multiple']){
			$inputName.='[]';
			$inputOpts['multiple'] = 'multiple';
		}
		$inputOpts['name'] = $inputName;

		$out .= $this->Html->tag('label', $options['label'], array('for' => $inputName));
		$out .= $this->Form->file(null, $inputOpts);

		if (!empty($uploadErrors[$uploadAlias])){
			$list = array();
			foreach ($uploadErrors[$uploadAlias] as $file => $errors){
				foreach ($errors as $field => $rules){
					foreach ($rules as $rule){
						$errorMessage = !empty($options['error'][$rule]) ? $options['error'][$rule] : __d('uploader', 'Upload failed', true);
						$list[] = $this->Html->tag('li', sprintf('%s: %s', $file, $errorMessage), array('class' => 'uploader error-message'));
					}
				}
			}
			$out .= $this->Html->tag('ul', join('', $list), array('class' => 'uploader errors'));
		}

		$out .= $this->uploadList($uploadAlias);
		$out = $this->Html->div(join(' ', $cssClass), $out);
		return $this->output($out);
	}


	function uploadList($alias, $data = null, $element = null){

		if ($data === null){
			$data = $this->request->data;
		}
		if ($element === null){
			$element = 'default_element';
		}

		$list = array();
		if (isset($data[$alias])){
			foreach ($data[$alias] as $upload){
				$item = $this->_View->element($element, array('upload' => $upload), array('plugin' => 'Uploader'));
				$checkboxLabel = __d('uploader', 'Delete this upload', true);
				$list[] = $this->Html->tag('li', $this->Form->input(null, array('type' => 'checkbox', 'name' => 'data[UploadsToDelete]['.$upload['id'].']', 'value' => $upload['id'], 'label' => false)) . $item);
			}
		}
		$out = '';
		$out .= $this->Html->css('/uploader/css/uploader.css', null, array('inline' => false));
		$out .= $this->Html->tag('ul', join('', $list), array('class' => 'uploader-list '. $alias));
		return ($this->output($out));
	}
}
