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
 * @since         2.5.0
 */
namespace Passbolt\WebInstaller\Test\Lib;

use Cake\Datasource\ConnectionManager;

trait DatabaseTrait
{
    /**
     * Truncate the database tables.
     */
    public function truncateTables()
    {
        $connection = ConnectionManager::get('default');
        $tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
        foreach ($tables as $table) {
            $quotedTableName = $connection->getDriver()->quoteIdentifier($table);
            $connection->query("DROP TABLE {$quotedTableName}");
        }
    }
}
