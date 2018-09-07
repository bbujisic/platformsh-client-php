<?php

namespace Platformsh\Client\Model\Git;

use GuzzleHttp\ClientInterface;
use Platformsh\Client\Model\Project;
use Platformsh\Client\Model\ApiResourceBase;
use Platformsh\Client\PlatformClient;

/**
 * Git ref resource.
 *
 * @property-read string $id
 *   The ID of this ref.
 * @property-read string $ref
 *   The fully qualified ref name.
 * @property-read array  $object
 *   An object containing 'type' and 'sha'.
 */
class Ref extends ApiResourceBase
{
    /**
     * Get a Ref object in a project.
     */
    public static function fromName(string $refName, Project $project, PlatformClient $client): ?self
    {
        $uri = $project->getUri().'/git/refs/'.$refName;

        if ($data = $client->getConnector()->sendToUri($uri)) {
            return new static($data, $uri, $client);
        }
        return null;
    }

    /**
     * Get the commit for this ref.
     */
    public function getCommit(): ?Commit
    {
        $data = $this->object;
        if ($data['type'] !== 'commit') {
            throw new \RuntimeException('This ref is not a commit');
        }
        $uri = Project::getProjectBaseFromUrl($this->getUri()).'/git/commits/'.$data['sha'];

        if ($data = $client->getConnector()->sendToUri($uri)) {
            return new static($data, $uri, $client);
        }

        return null;
    }
}
