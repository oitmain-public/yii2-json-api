<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi;

/**
 * Interface for a “resource relationship” that represent an
 */
interface ResourceRelationshipInterface
{

    /**
     * The "relationships" member of the resource object describing relationships between the resource and other JSON API resources.
     * @return ResourceIdentifierInterface[] represent references from the resource object in which it’s defined to other resource objects.
     */
    public function getRelationshipItems();

    public function getRelationshipName();

    public function getModel();

}
