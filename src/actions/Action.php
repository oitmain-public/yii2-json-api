<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\actions;

use tuyakhov\jsonapi\ResourceIdentifierInterface;
use tuyakhov\jsonapi\ResourceInterface;
use yii\db\ActiveRecordInterface;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;

class Action extends \yii\rest\Action
{
    /**
     * Links the relationships with primary model.
     * @var callable
     */
    public $linkRelationships;

    /**
     * @var bool Weather allow to do a full replacement of a to-many relationship
     */
    public $allowFullReplacement = true;

    /**
     * @param string $id
     * @return void|ActiveRecordInterface
     * @throws ServerErrorHttpException
     */
    public function findModel($id)
    {
        $model = parent::findModel($id);
        if (!$model instanceof ResourceIdentifierInterface) {
            throw new ServerErrorHttpException('Model does not implement ResourceIdentifierInterface');
        }
        return $model;
    }

    /**
     * Links the relationships with primary model.
     * @param $model ActiveRecordInterface
     * @param array $data
     */
    protected function linkRelationships($model, array $data = [])
    {
        if ($this->linkRelationships !== null) {
            call_user_func($this->linkRelationships, $this, $model, $data);
            return;
        }

        if (!$model instanceof ResourceInterface) {
            return;
        }

        foreach ($data as $name => $relationship) {
            if (!$related = $model->getRelation($name, false)) {
                continue;
            }
            /** @var BaseActiveRecord $relatedClass */
            $relatedClass = new $related->modelClass;
            $relationships = ArrayHelper::keyExists($relatedClass->formName(), $relationship) ? $relationship[$relatedClass->formName()] : [];

            $ids = [];
            foreach ($relationships as $index => $relObject) {
                if (!isset($relObject['id'])) {
                    continue;
                }
                $ids[] = $relObject['id'];
            }

            if (!$records = $relatedClass::find()->andWhere(['in', $relatedClass::primaryKey(), $ids])->all()) {
                continue;
            }

            if ($related->multiple && !$this->allowFullReplacement) {
                continue;
            }
            $model->unlinkAll($name);
            $model->setResourceRelationship($name, $records);
        }
    }
}