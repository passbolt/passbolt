<?php
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
 * @since         3.0.0
 */
// @codingStandardsIgnoreStart
use Migrations\AbstractMigration;
use App\Utility\UuidFactory;
use App\Model\Table\ResourcesTable;

class V320FixResourceTypesDefaultData extends AbstractMigration
{
    /**
     * Up
     *
     * @return void
     */
    public function up()
    {
        $toUpdate = [
            UuidFactory::uuid('resource-types.id.password-string') => json_encode([
                "resource" => [
                    "type" => "object",
                    "required" => [
                        "name"
                    ],
                    "properties" => [
                        "name" => [
                            "type" => "string",
                            "maxLength" => 64
                        ],
                        "username" => [
                            "anyOf" => [[
                                "type" => "string",
                                "maxLength" => 64
                            ], [
                                "type" => "null"
                            ]]
                        ],
                        "uri" => [
                            "anyOf" => [[
                                "type" => "string",
                                "maxLength" => 1024
                            ], [
                                "type" => "null"
                            ]]
                        ],
                        "description" => [
                            "anyOf" => [[
                                "type" => "string",
                                "maxLength" => 10000
                            ], [
                                "type" => "null"
                            ]]
                        ],
                    ]
                ],
                "secret" => [
                    "type" => "string",
                    "maxLength" => 4096
                ],
            ]),
            UuidFactory::uuid('resource-types.id.password-and-description') => json_encode([
                "resource" => [
                    "type" => "object",
                    "required" => [
                        "name"
                    ],
                    "properties" => [
                        "name" => [
                            "type" => "string",
                            "maxLength" => 64
                        ],
                        "username" => [
                            "anyOf" => [[
                                "type" => "string",
                                "maxLength" => 64
                            ], [
                                "type" => "null"
                            ]]
                        ],
                        "uri" => [
                            "anyOf" => [[
                                "type" => "string",
                                "maxLength" => 1024
                            ], [
                                "type" => "null"
                            ]]
                        ],
                    ]
                ],
                "secret" => [
                    "type" => "object",
                    "required" => [
                        "password"
                    ],
                    "properties" => [
                        "password" => [
                            "type" => "string",
                            "maxLength" => 4096
                        ],
                        "description" => [
                            "anyOf" => [[
                                "type" => "string",
                                "maxLength" => 10000
                            ], [
                                "type" => "null"
                            ]]
                        ],
                    ],
                ],
            ]),
        ];
        foreach ($toUpdate as $id => $definition) {
            $sql = "UPDATE resource_types SET definition= '$definition' WHERE id = '$id'";
            $this->execute($sql);
        }
    }
}
// @codingStandardsIgnoreEnd
