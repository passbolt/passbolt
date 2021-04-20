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
namespace App\Test\Lib\Model;

use App\Model\Entity\Group;
use App\Utility\UuidFactory;
use Cake\ORM\TableRegistry;

trait GroupsModelTrait
{
    /**
     * Add a dummy group.
     *
     * @param array|null $data The group data
     * @param array|null $options The entity options
     * @return Group
     */
    public function addGroup(?array $data = [], ?array $options = [])
    {
        $groupsTable = TableRegistry::getTableLocator()->get('Groups');
        $group = self::getDummyGroupEntity($data, $options);

        $groupsTable->save($group, ['checkRules' => true]);

        return $group;
    }

    /**
     * Get a new group entity
     *
     * @param array|null $data The group data.
     * @param array|null $options The new entity options.
     * @return Group
     */
    public function getDummyGroupEntity(?array $data = [], ?array $options = []): Group
    {
        $groupsTable = TableRegistry::getTableLocator()->get('Groups');
        $defaultOptions = [
            'validate' => false,
            'accessibleFields' => [
                'name' => true,
                'created_by' => true,
                'modified_by' => true,
                'groups_users' => true,
                'deleted' => true,
            ],
            'associated' => [
                'GroupsUsers' => [
                    'validate' => false,
                    'accessibleFields' => [
                        'user_id' => true,
                        'is_admin' => true,
                    ],
                ],
            ],
        ];

        $data = self::getDummyGroupData($data);
        $options = array_merge($defaultOptions, $options);

        return $groupsTable->newEntity($data, $options);
    }

    /**
     * Get a dummy group with test data.
     * The comment returned passes a default validation.
     *
     * @param array $data Custom data that will be merged with the default content.
     * @return array Comment data
     */
    public static function getDummyGroupData($data = [])
    {
        $entityContent = [
            'name' => 'New group name',
            'created_by' => UuidFactory::uuid('user.id.admin'),
            'modified_by' => UuidFactory::uuid('user.id.admin'),
            'deleted' => false,
        ];
        $entityContent = array_merge($entityContent, $data);

        return $entityContent;
    }

    /**
     * Asserts that an object has all the attributes a group should have.
     *
     * @param object $group
     */
    protected function assertGroupAttributes($group)
    {
        $attributes = ['id', 'name', 'deleted', 'created', 'modified', 'created_by', 'modified_by'];
        $this->assertObjectHasAttributes($attributes, $group);
    }

    /**
     * Asserts than a group is soft deleted.
     *
     * @param string $id
     */
    protected function assertGroupIsSoftDeleted($id)
    {
        $group = $this->Groups->get($id);
        $this->assertTrue($group->deleted);
        // Groups users have been deleted
        $groupsUsers = $this->Groups->GroupsUsers->find()->where(['group_id' => $id])->all();
        $this->assertEmpty($groupsUsers);
        // Permissions have been deleted
        $permissions = $this->Permissions->find()->where(['aro_foreign_key' => $id])->all();
        $this->assertEmpty($permissions);
    }

    /**
     * Asserts than a group is not soft deleted.
     *
     * @param string $id
     */
    protected function assertGroupIsNotSoftDeleted($id)
    {
        $group = $this->Groups->get($id);
        $this->assertFalse($group->deleted);
    }
}
