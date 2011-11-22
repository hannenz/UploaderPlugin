<?php
/*
 * uploadable.php
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
 * File: /app/plugins/uploader/models/behaviors/uploadable.php
 *
 * Uploader Plugin: Uploadable behavior
 * This class extends your model's class as it adds the necessary callbacks
 * for behaving according to the Uploader plugin.
 *
 */
class UploadableBehavior extends ModelBehavior {

	function setup(Model $Model, $settings){
		if (!isset($this->settings[$Model->alias])){
			$this->settings[$Model->alias] = array(
				// Default setting
			);
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);

		foreach ($this->settings[$Model->alias] as $uploadAlias => $data){
			$this->settings[$Model->alias][$uploadAlias]['model'] = $Model->alias;
			$type = (isset($this->settings[$Model->alias][$uploadAlias]['max']) && $this->settings[$Model->alias][$uploadAlias]['max'] == 1)
				? 'hasMany'
				: 'hasMany'
			;
			$Model->bindModel(array(
				$type => array(
					$uploadAlias => array(
						'className' => 'Uploader.Upload',
						'foreignKey' => 'foreign_key',
						'conditions' => array(
							$uploadAlias . '.alias' => $uploadAlias,
							$uploadAlias . '.model' => $Model->name
						),
						'dependent' => false,
						'order' => array($uploadAlias . '.pos' => 'ASC')
					)
				)
			), false);
		}
		App::uses('Upload', 'Uploader.Model');
		$this->Upload = new Upload();
		$this->Upload->config = $this->settings[$Model->alias];
	}

	function beforeSave($Model){
		//~ App::uses('Upload', 'Uploader.Model');
		//~ $this->Upload = new Upload();
		//~ $this->Upload->config = $this->settings[$Model->alias];

		$Model->uploadErrors = null;
		$Model->wasUploading = false;
		$Model->wasDeleting = false;

		if (!empty($Model->data['UploadsToDelete'])){
			foreach ($Model->data['UploadsToDelete'] as $id => $delete){
				if ($delete > 0){
					$Model->wasDeleting = true;
					$this->Upload->delete($id);
				}
			}
		}

		if (empty($_FILES)){
			return (true);
		}

		foreach ($_FILES as $uploadAlias => $fileData){
			if (!empty($fileData['name'])){
				$uploads = array();
				if (is_array($fileData['name'])){
					if (empty($fileData['name'][0])){
						continue;
					}
					foreach ($fileData['name'] as $n => $value){
						$uploads[] = array(
							'name' => $fileData['name'][$n],
							'tmp_name' => $fileData['tmp_name'][$n],
							'type' => $fileData['type'][$n],
							'size' => $fileData['size'][$n],
							'error' => $fileData['error'][$n]
						);
					}
				}
				else {
					if (empty($fileData['name'])){
						continue;
					}
					$uploads[] = array(
						'name' => $fileData['name'],
						'type' => $fileData['type'],
						'tmp_name' => $fileData['tmp_name'],
						'size' => $fileData['size'],
						'error' => $fileData['error']
					);
				}
				$errors = array();
				$Model->wasUploading = count($uploads) > 0;
				foreach ($uploads as $upload){
					$upload['alias'] = $uploadAlias;
					$upload['model'] = $Model->name;
					$upload['foreign_key'] = isset($Model->data[$Model->name]['id']) ? $Model->data[$Model->name]['id'] : null;

					$this->Upload->alias = $uploadAlias;
					$this->Upload->create();
					if (!$this->Upload->save($upload)){
						$errors[$uploadAlias][$upload['name']] = $this->Upload->validationErrors;
					}
				}
				if (!empty($errors)){
					$Model->uploadErrors = $errors;
				}
			}
		}

		return (true);
	}


/* Assigns any pending uploads to the record that has been saved
 *
 * name: afterSave
 *
 */
	function afterSave(&$Model, $created){
		if ($created){
			//~ App::uses('Upload', 'Uploader.Model');
			//~ $this->Upload = new Upload();
			//~ $this->Upload->config = $this->settings[$Model->alias];
			$this->Upload->savePending($Model->id);
		}
	}

/* Deletes any uploads that belong to the record which is going to be
 * deleted. Removes all according files, too (in the Upload model).
 *
 * This is some kind of workaround, since this SHOULD be done via the
 * dependent parameter in the model's association but Cake produces
 * wrong(?) SQL statements so I decided to implement this functionality
 * in this behavior. A pity :(
 *
 * BE sure to have the dependent parameter set to FALSE for the upload's
 * association, or else Cake will produce those wrong SQL statements and
 * you will end up with error messages, a cluttered database and a load
 * of unneccessary files... ;)
 *
 * name: beforeDelete
 */
	function beforeDelete($Model, $cascade = true){
		//~ App::uses('Upload', 'Uploader.Model');
		//~ $this->Upload = new Upload();
		//~ $this->Upload->config = $this->settings[$Model->alias];

		foreach ($this->settings[$Model->alias] as $uploadAlias => $data){
			$conditions = array(
				'Upload.model' => $Model->name,
				'Upload.alias' => $uploadAlias,
				'Upload.foreign_key' => $Model->id
			);

			$records = $this->Upload->find('all', array('conditions' => $conditions, 'fields' => array('id')));
			if (!empty($records)){
				foreach ($records as $record){
					$this->Upload->delete($record['Upload']['id']);
				}
			}
		}
		return (true);
	}

	function afterFind($Model, $results, $primary = false){
		if ($primary){

			//~ App::uses('Upload', 'Uploader.Model');
			//~ $this->Upload = new Upload();
			//~ $this->Upload->config = $this->settings[$Model->alias];

			foreach ($results as $n => $result){
				foreach ($this->settings[$Model->alias] as $uploadAlias => $setting){
					if (isset($result[$uploadAlias])){
						foreach ($result[$uploadAlias] as $m => $data){
							$results[$n][$uploadAlias][$m]['files'] = array();
							foreach ($setting['files'] as $name => $path){
								$results[$n][$uploadAlias][$m]['files'][$name] = DS .trim($path['path'], '/') . DS . $data['filename'];
							}
							$results[$n][$uploadAlias][$m]['icon'] = $this->Upload->getIcon($data);
						}
					}
				}

			}
		}
		return ($results);
	}

}
