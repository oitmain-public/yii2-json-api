<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\actions;

use tuyakhov\jsonapi\ResourceIdentifierInterface;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

class UpdateAction extends Action
{
    /**
     * @var string the scenario to be assigned to the model before it is validated and updated.
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * Updates an existing resource.
     * @param string $id the primary key of the model.
     * @return \yii\db\ActiveRecordInterface the model being updated
     * @throws ServerErrorHttpException if there is any error when updating the model
     */
    public function run($id)
    {
        /* @var $model ActiveRecord */
        $model = $this->findModel($id);

        if (!$model instanceof ResourceIdentifierInterface) {
            throw new ServerErrorHttpException('Model does not implement ResourceIdentifierInterface');
        }

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        $request = Yii::$app->getRequest();
        $model->scenario = $this->scenario;
        if (!$model->load($request->getBodyParams())) {
            throw new BadRequestHttpException('Expecting object type ' . $model->getType());
        }
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }

        $this->linkRelationships($model, $request->getBodyParam('relationships', []));

        return $model;
    }
}