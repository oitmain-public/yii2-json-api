<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi;

use yii\base\Arrayable;
use yii\db\ActiveRecordInterface;
use yii\web\Link;
use yii\web\Linkable;

class ResourceRelationship implements ResourceRelationshipInterface
{

    /**
     * @var array
     */
    public $relationshipItems;

    /**
     * @var string
     */
    public $relationshipName;

    /**
     * @var ResourceInterface
     */
    public $model;

    /**
     * @return ResourceInterface
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return null|string
     */
    public function getRelationshipItems()
    {
        return $this->relationshipItems;
    }

    /**
     * @return string
     */
    public function getRelationshipName()
    {
        return $this->relationshipName;
    }

}
