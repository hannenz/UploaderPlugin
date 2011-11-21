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
			$Model->bindModel(array(
				'hasMany' => array(
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
			));
		}
	}

	function beforeSave($Model){
		App::uses('Upload', 'Uploader.Model');
		$this->Upload = new Upload();
		$this->Upload->config = $this->settings[$Model->alias];

		if (isset($Model->data['Uploader']) && is_array($Model->data['Uploader'])){
			foreach ($Model->data['Uploader'] as $uploadAlias => $data){
					if (!empty($data['name'])){
					$upload = array(
						$uploadAlias => array(
							'alias' => $uploadAlias,
							'model' => $Model->name,
							'foreign_key' => isset($Model->data[$Model->name]['id']) ? $Model->data[$Model->name]['id'] : null,
							'name' => $data['name'],
							'type' => $data['type'],
							'tmp_name' => $data['tmp_name'],
							'size' => $data['size'],
							'error' => $data['error']
						)
					);
					$this->Upload->alias = $uploadAlias;
					$this->Upload->create();
					if (!$this->Upload->save($upload)){
						//~ return (false);
						//~ debug ($this->Upload->validationErrors);
					}
				}
			}
		}
		return (true);

//		$data['alias'] =


		/* If we have something to delete... do it */
		if (!empty($model->data['UploadDelete'])){
			foreach ($model->data['UploadDelete'] as $alias => $ids){
				foreach ($ids as $id){
					$model->{$alias}->delete($id);
				}
			}
		}

		// In case there are no files to upload...
		if (empty($_FILES)){
			return (true);
		}

		/* Process upload(s) from $_FILES and normalize to array */
		foreach ($_FILES as $alias => $upload_data){
			$data = array($upload_data);
			if (is_array($upload_data['name'])){
				$data = array();
				foreach ($upload_data['name'] as $key => $name){
					$data[] = array(
						'name' => $upload_data['name'][$key],
						'type' => $upload_data['type'][$key],
						'tmp_name' => $upload_data['tmp_name'][$key],
						'error' => $upload_data['error'][$key],
						'size' => $upload_data['size'][$key]
					);
				}
			}

			$model->data[$alias]['upload_data'] = $data;

			/* Call Uploader.Upload to upload the files. */
			$model->{$alias}->data = $model->data[$alias];
			$uploads = $model->{$alias}->uploadAll($data);

			/* Do save all uploads...
			 * It's a pity that it seems not to work to have Cake's saveAll
			 * model method handle this, but i didn't get it to work...
			 * Maybe look at this again later. For now doing it "by hand"
			 */
			foreach ($uploads as $upload) {
				$data = array(
					'Upload' => $upload
				);
				$data['Upload']['id'] = !empty($model->data[$alias]['id']) ? $model->data[$alias]['id'] : null;
				// If we replace an existing upload, remove all of its files first.
				if ($data['Upload']['id'] > 0){
					$up = $model->{$alias}->findById($data['Upload']['id']);
					$model->{$alias}->deleteFiles($up);
				}
				$model->{$alias}->save($data);
			}
		}
		return (true);
	}


/* Assigns any pending uploads to the record that has been saved
 *
 * name: afterSave
 *
 */
	function afterSave(&$model, $created){
		foreach (array_merge($model->hasOne, $model->hasMany) as $assoc => $data){
			if ($data['className'] == 'Upload'){
				$model->{$assoc}->savePending($model->name, $assoc, $model->id);
			}
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
	function beforeDelete(&$model, $cascade = true){
		foreach (array_merge($model->hasOne, $model->hasMany) as $assoc => $data){
			if ($data['className'] == 'Upload'){
				$conditions = array(
					'Upload.model' => $model->name,
					'Upload.alias' => $assoc,
					'Upload.foreign_key' => $model->id
				);
				$records = $model->{$assoc}->find('all', array('conditions' => $conditions, 'fields' => array('id')));
				if (!empty($records)){
					foreach ($records as $record){
						$model->{$assoc}->delete($record['Upload']['id']);
					}
				}
			}
		}
		return (true);
	}

	function afterFind($Model, $results, $primary = false){
		if ($primary){

			App::uses('Upload', 'Uploader.Model');
			$this->Upload = new Upload();
			$this->Upload->config = $this->settings[$Model->alias];

			foreach ($results as $n => $result){
				foreach ($this->settings[$Model->alias] as $uploadAlias => $setting){
					if (isset($result[$uploadAlias])){
						foreach ($result[$uploadAlias] as $m => $data){
							$results[$n][$uploadAlias][$m]['files'] = array();
							foreach ($setting['files'] as $name => $path){
								$results[$n][$uploadAlias][$m]['files'][$name] = $path['path'] . $data['name'];
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
