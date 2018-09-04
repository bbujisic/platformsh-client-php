<?php

namespace Platformsh\Client\Model;

use GuzzleHttp\ClientInterface;

/**
 * Represents a Platform.sh region.
 *
 * @property-read int    $id
 * @property-read string $label
 * @property-read bool   $available
 * @property-read bool   $private
 * @property-read string $zone
 * @property-read string $endpoint
 */
class Region extends ApiResourceBase
{
    // @todo: Move these constants to methods, so that they can be documented in an appropriate interface.
    const COLLECTION_NAME = 'regions';
    const COLLECTION_PATH = 'regions';

    /**
     * Prevent deletion.
     */
    public function delete()
    {
        throw new \BadMethodCallException("Regions cannot be deleted.");
    }

    /**
     * @inheritdoc
     */
    protected function setData(array $data)
    {
        $data = isset($data['regions'][0]) ? $data['regions'][0] : $data;
        $data['available'] = !empty($data['available']);
        $data['private'] = !empty($data['private']);
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function operationAvailable($op)
    {
        if ($op === 'edit') {
            return true;
        }

        return parent::operationAvailable($op);
    }

    /**
     * @inheritdoc
     */
    public function getLink($rel, $absolute = false)
    {
        if ($rel === '#edit') {
            return $this->getUri($absolute);
        }

        return parent::getLink($rel, $absolute);
    }
}
