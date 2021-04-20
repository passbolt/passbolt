<?php
declare(strict_types=1);

/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */

namespace App\Model\Traits\Resources;

use App\Model\Event\TableFindIndexBefore;
use App\Model\Table\AvatarsTable;
use App\Model\Table\Dto\FindIndexOptions;
use App\Model\Table\PermissionsTable;
use App\Model\Table\ResourceTypesTable;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Database\Expression\IdentifierExpression;
use Cake\ORM\Query;
use Cake\Validation\Validation;

/**
 * @method \Cake\Event\EventManager getEventManager()
 */
trait ResourcesFindersTrait
{
    /**
     * Build the query that fetches data for resource index
     *
     * @param string $userId The user to get the resources for
     * @param array $options options
     * @return \Cake\ORM\Query
     * @throws \InvalidArgumentException if the userId parameter is not a valid uuid.
     */
    public function findIndex(string $userId, ?array $options = [])
    {
        if (!Validation::uuid($userId)) {
            throw new \InvalidArgumentException('The user identifier should be a valid UUID.');
        }

        $query = $this->find();

        $findIndexOptions = FindIndexOptions::createFromArray($options);
        $findIndexOptions->setUserId($userId);

        $event = TableFindIndexBefore::create($query, $findIndexOptions, $this);

        /** @var \App\Model\Event\TableFindIndexBefore $event */
        $this->getEventManager()->dispatch($event);

        // Filter out deleted resources
        $query->where(['Resources.deleted' => false]);

        if (isset($options['filter']['has-id'])) {
            $query->where(['Resources.id IN' => $options['filter']['has-id']]);
        }

        // If filtered by favorite.
        if (isset($options['filter']['is-favorite'])) {
            // Filter on the favorite resources.
            if ($options['filter']['is-favorite']) {
                $query->innerJoinWith('Favorites', function ($q) use ($userId) {
                    return $q->where(['Favorites.user_id' => $userId]);
                });
            } else {
                // Filter out the favorite resources.
                $query->notMatching('Favorites', function ($q) use ($userId) {
                    return $q->where(['Favorites.user_id' => $userId]);
                });
            }
        }

        // Filter on resources owned by me.
        if (isset($options['filter']['is-owned-by-me'])) {
            $query = $this->_filterQueryIsOwnedByUser($query, $userId);
        }

        // Filter on resource shared with me.
        if (isset($options['filter']['is-shared-with-me'])) {
            $query = $this->_filterQuerySharedWithUser($query, $userId);
        }

        // Filter on resources shared with group.
        if (isset($options['filter']['is-shared-with-group'])) {
            $query = $this->_filterQuerySharedWithGroup($query, $options['filter']['is-shared-with-group']);
        }

        // Filter on resources I have permission.
        $query = $this->_filterQueryByPermissions($query, $userId);

        // If contains the user permission.
        if (isset($options['contain']['permission'])) {
            $query->contain('Permission', function (Query $q) use ($userId) {
                $subQueryOptions = ['checkGroupsUsers' => true];
                $permissionIdSubQuery = $this->Permissions
                    ->findAllByAro(PermissionsTable::RESOURCE_ACO, $userId, $subQueryOptions)
                    ->where(['Permissions.aco_foreign_key' => new IdentifierExpression('Resources.id')])
                    ->orderDesc('Permissions.type')
                    ->limit(1)
                    ->select(['Permissions.id']);

                return $q->where(['Permission.id' => $permissionIdSubQuery]);
            });
        }

        // If contains Secrets.
        if (isset($options['contain']['secret'])) {
            $query->contain('Secrets', function ($q) use ($userId) {
                return $q->where(['Secrets.user_id' => $userId]);
            });
        }

        // If contains creator.
        if (isset($options['contain']['creator'])) {
            $query->contain('Creator');
        }

        // If contains modifier.
        if (isset($options['contain']['modifier'])) {
            $query->contain('Modifier');
        }

        // If contains favorite.
        if (isset($options['contain']['favorite'])) {
            $query->contain('Favorites', function ($q) use ($userId) {
                return $q->where(['Favorites.user_id' => $userId]);
            });
        }

        // Retrieve the permission and the details of a user attach to it if any
        if (isset($options['contain']['permissions.user.profile'])) {
            $query->contain([
                'Permissions' => [
                    'Users' => [
                        'Profiles' => AvatarsTable::addContainAvatar(),
                    ],
                ],
            ]);
        }

        // Retrieve the permission and the details of a group attach to it if any
        if (isset($options['contain']['permissions.group'])) {
            $query->contain('Permissions.Groups');
        }

        // Retrieve the permissions
        if (isset($options['contain']['permissions'])) {
            $query->contain('Permissions');
        }

        // If contains Resource type.
        if (isset($options['contain']['resource-type'])) {
            $query->contain('ResourceTypes')
                ->formatResults(ResourceTypesTable::resultFormatter(true));
        }

        // Handle the sorting of modified for compatibility with the
        // approach prior to pagination.
        if (isset($options['order']['Resources.modified'])) {
            $query->order('Resources.modified DESC');
        } else {
            $query->orderAsc('Resources.name');
        }

        // Remove resource type if plugin is disabled
        if (!Configure::read('passbolt.plugins.resourceTypes.enabled')) {
            $query->formatResults(function (CollectionInterface $results) {
                return $results->map(function ($row) {
                    if (isset($row['resource_type_id'])) {
                        unset($row['resource_type_id']);
                    }

                    return $row;
                });
            });
        }

        return $query;
    }

    /**
     * Build the query that fetches data for resource view
     *
     * @param string $userId The user to get the resources for
     * @param string $resourceId The resource to retrieve
     * @param array $options options
     * @return \Cake\ORM\Query
     * @throws \InvalidArgumentException if the resourceId parameter is not a valid uuid.
     * @throws \InvalidArgumentException if the userId parameter is not a valid uuid.
     */
    public function findView(string $userId, string $resourceId, ?array $options = [])
    {
        if (!Validation::uuid($userId)) {
            throw new \InvalidArgumentException('The parameter userId should be a valid UUID.');
        }
        if (!Validation::uuid($resourceId)) {
            throw new \InvalidArgumentException('The parameter resourceId should be a valid UUID.');
        }

        $query = $this->findIndex($userId, $options)
            ->where(['Resources.id' => $resourceId]);

        return $query;
    }

    /**
     * Build the query that fetches the resources that a group has access on.
     *
     * @param string $groupId uuid The group to fetch the resources for
     * @return \Cake\ORM\Query
     */
    public function findAllByGroupAccess(string $groupId)
    {
        if (!Validation::uuid($groupId)) {
            throw new \InvalidArgumentException('The group identifier should be a valid UUID.');
        }

        $query = $this->find()
            ->where(['Resources.deleted' => false]);
        $this->_filterQuerySharedWithGroup($query, $groupId);

        return $query;
    }

    /**
     * Get a list of resources with a given list of ids
     *
     * @param string $userId uuid
     * @param array $resourceIds array of resource uuids
     * @param array $options array of options
     * @return \Cake\ORM\Query
     * @throws \InvalidArgumentException if the resourceId parameter is not a valid uuid.
     * @throws \InvalidArgumentException if the userId parameter is not a valid uuid.
     */
    public function findAllByIds(string $userId, array $resourceIds = [], ?array $options = [])
    {
        if (!Validation::uuid($userId)) {
            throw new \InvalidArgumentException('The user identifier should be a valid UUID.');
        }
        if (empty($resourceIds)) {
            throw new \InvalidArgumentException('The resources ids array can not be empty.');
        } else {
            foreach ($resourceIds as $resourceId) {
                if (!Validation::uuid($resourceId)) {
                    $msg = 'The array of resources identifiers should contain only valid UUID.';
                    throw new \InvalidArgumentException($msg);
                }
            }
        }

        return $this->findIndex($userId, $options)
            ->where(['Resources.id IN' => $resourceIds]);
    }

    /**
     * Augment any Resources queries joined with Permissions to ensure the query returns only the
     * resources a user has access.
     *
     * A user has access to a resource if one the following conditions is respected :
     * - A permission is defined directly for the user and for a given resource.
     * - A permission is defined for a group the user is member of and for a given resource.
     *
     * This function can be used on any queries joined with Permissions as following
     * > $query->innerJoinWith('Permissions') or $query->matching('Permissions')
     * > _filterQueryByPermissions($query, $userId);
     *
     * @param \Cake\ORM\Query $query The query to filter.
     * @param string $userId The user to check the permissions for.
     * @return \Cake\ORM\Query
     * @throws \InvalidArgumentException if the user id is not a uuid
     */
    private function _filterQueryByPermissions(Query $query, string $userId)
    {
        $subQueryOptions = [
            'checkGroupsUsers' => true,
        ];
        $resourcesFilterByPermissionTypeSubQuery = $this->Permissions
            ->findAllByAro(PermissionsTable::RESOURCE_ACO, $userId, $subQueryOptions)
            ->select(['Permissions.aco_foreign_key'])
            ->distinct();

        $query->where(['Resources.id IN' => $resourcesFilterByPermissionTypeSubQuery]);

        return $query;
    }

    /**
     * Augment any Resources queries to filter on resources owned by the given user.
     * A owned resource means a resource that is shared with the OWNER permission.
     *
     * @param \Cake\ORM\Query $query The query to filter.
     * @param string $userId The user identifier to filter on.
     * @return \Cake\ORM\Query
     */
    private function _filterQueryIsOwnedByUser(Query $query, string $userId)
    {
        $resourcesUserIsOwnerSubQueryOptions = ['checkGroupsUsers' => true];
        $resourcesUserIsOwnerSubQuery = $this->Permissions
            ->findAcosByAroIsOwner(PermissionsTable::RESOURCE_ACO, $userId, $resourcesUserIsOwnerSubQueryOptions);
        $query = $query->where(['Resources.id IN' => $resourcesUserIsOwnerSubQuery]);

        return $query;
    }

    /**
     * Augment any Resources queries to filter on resources shared with the given user.
     * We consider that a resource is shared with a user when it is accessible by the user but is not owner or one
     * of a group he is member is owner.
     *
     * @param \Cake\ORM\Query $query The query to filter.
     * @param string $userId The user identifier to filter on.
     * @return \Cake\ORM\Query
     */
    private function _filterQuerySharedWithUser(Query $query, string $userId)
    {
        $resourcesUserIsOwnerSubQueryOptions = ['checkGroupsUsers' => true];
        $resourcesUserIsOwnerSubQuery = $this->Permissions
            ->findAcosByAroIsOwner(PermissionsTable::RESOURCE_ACO, $userId, $resourcesUserIsOwnerSubQueryOptions);
        $query = $query->where(['Resources.id NOT IN' => $resourcesUserIsOwnerSubQuery]);

        return $query;
    }

    /**
     * Augment any Resources queries to filter on resources shared with a given group.
     *
     * @param \Cake\ORM\Query $query The query to filter.
     * @param string $groupId The group to check the permissions for.
     * @return \Cake\ORM\Query
     * @throws \InvalidArgumentException if the group id is not a uuid
     */
    private function _filterQuerySharedWithGroup(Query $query, string $groupId)
    {
        if (!Validation::uuid($groupId)) {
            throw new \InvalidArgumentException('The group identifier should be a valid UUID.');
        }

        $resourcesSharedWithGroupSubQuery = $this->Permissions->findAllByAro(PermissionsTable::RESOURCE_ACO, $groupId)
            ->select(['Permissions.aco_foreign_key']);
        $query->where(['Resources.id IN' => $resourcesSharedWithGroupSubQuery]);

        return $query;
    }
}
