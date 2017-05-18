<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\actions;

use tuyakhov\jsonapi\Inflector;
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

        $formName = Inflector::type2form($name);
        $name = str_replace('-', '_', $name);

        $related = $model->getRelation($name, false);
        if (!$related) {
            throw new NotFoundHttpException($name . ' is not related to ' . $model->getType());
        }
        $relatedModel = new $related->modelClass();

        $request = Yii::$app->getRequest();
        switch ($request->getMethod()) {
            case 'PATCH':
                $ids = $this->getIds($formName);
                $items = $relatedModel->findAll($ids);
                if (count($items) != count($ids)) {
                    $missingIds = [];
                    foreach ($items as $item) {
                        if (!isset($ids[$item->getId()])) {
                            $missingIds[] = $item->getId();
                        }
                    }
                    throw new BadRequestHttpException('Invalid ID ' . implode(',', $missingIds));
                }

                // Unlink previous relationship
                $model->unlinkAll($name);

                // Reload after unlinking
                $items = $relatedModel->findAll($ids);

                foreach ($items as $item) {
                    $model->link($name, $item);
                }
                Yii::$app->getResponse()->setStatusCode(204);
                break;
            case 'POST':
                $ids = $this->getIds($formName);
                $items = $relatedModel->findAll($ids);
                foreach ($items as $item) {
                    $model->link($name, $item);
                }
                Yii::$app->getResponse()->setStatusCode(204);
                break;
            case 'DELETE':
                $ids = $this->getIds($formName);
                foreach ($related->all() as $item) {
                    if (in_array($item->getId(), $ids)) {
                        $model->unlink($name, $item);
                    }
                }
                Yii::$app->getResponse()->setStatusCode(204);
                break;
            case 'GET':
                $resourceRelationship = new ResourceRelationship();
                $resourceRelationship->relationshipName = $name;
                $resourceRelationship->relationshipItems = $related->all();
                $resourceRelationship->model = $model;
                return $resourceRelationship;
                break;
            default:
                throw new MethodNotAllowedHttpException($request->getMethod() . ' is not supported yet');
                break;
        }
    }

    protected function getIds($formName)
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $ids = [];
        foreach ($params as $index => $relObjects) {
            if ($index != $formName) {
                throw new BadRequestHttpException('Type mismatch');
            }
            if (!is_array($relObjects)) {
                $relObjects = [$relObjects];
            }
            foreach ($relObjects as $relObject) {
                if (!isset($relObject['id'])) {
                    throw new BadRequestHttpException('Missing id');
                }
                $ids[] = $relObject['id'];
            }
        }
        return $ids;
    }
}