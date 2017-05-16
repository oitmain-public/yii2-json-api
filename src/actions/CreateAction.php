<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\actions;

use tuyakhov\jsonapi\ResourceIdentifierInterface;
use Yii;
use yii\base\Model;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

class CreateAction extends Action
{
    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * @var string the name of the view action. This property is need to create the URL when the model is successfully created.
     */
    public $viewAction = 'view';

    /**
     * Links the relationships with primary model.
     * @var callable
     */
    public $linkRelationships;

    /**
     * Creates a new resource.
     * @return \yii\db\ActiveRecordInterface the model newly created
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException if there is any error when creating the model
     */
    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }

        /* @var $model \yii\db\ActiveRecord */
        $model = new $this->modelClass([
            'scenario' => $this->scenario,
        ]);

        $request = Yii::$app->getRequest();
        if (!$model->load($request->getBodyParams())) {
            throw new BadRequestHttpException('Expecting object type ' . $model->getType());
        }
        if ($model->save()) {
            $this->linkRelationships($model, $request->getBodyParam('relationships', []));
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
            $id = implode(',', array_values($model->getPrimaryKey(true)));
            $response->getHeaders()->set('Location', Url::toRoute([$this->viewAction, 'id' => $id], true));
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }

        return $model;
    }
}