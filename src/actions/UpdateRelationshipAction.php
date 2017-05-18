<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\actions;

use tuyakhov\jsonapi\ResourceRelationship;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\BaseActiveRecord;
use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;

/**
 * UpdateRelationshipAction implements the API endpoint for updating relationships.
 * @link http://jsonapi.org/format/#crud-updating-relationships
 */
class UpdateRelationshipAction extends Action
{
    /**
     * Update of relationships independently.
     * @param string $id an ID of the primary resource
     * @param string $name a name of the related resource
     * @return ActiveDataProvider|BaseActiveRecord
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function run($id, $name)
    {
        /** @var BaseActiveRecord $model */
        $model = $this->findModel($id);

        if (!$related = $model->getRelation($name, false)) {
            throw new NotFoundHttpException('Relationship does not exist');
        }

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model, $name);
        }

        $request = Yii::$app->getRequest();
        if ($request->getMethod() != 'GET') {
            throw new MethodNotAllowedHttpException($request->getMethod() . ' is not supported yet');
            // $this->linkRelationships($model, [$name => Yii::$app->getRequest()->getBodyParams()]);
        }

        $relationships = $model->getResourceRelationships();
        if (!isset($relationships[$name])) {
            throw new NotFoundHttpException($name . ' is not related to ' . $model->getType());
        }

        $resourceRelationship = new ResourceRelationship();
        $resourceRelationship->relationshipName = $name;
        $resourceRelationship->relationshipItems = $relationships[$name];
        $resourceRelationship->model = $model;

        return $resourceRelationship;

    }
}