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
 * @since         3.1.0
 */
namespace App\Test\TestCase\Command;

use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use CakephpTestSuiteLight\Sniffer\SnifferRegistry;

class DropTablesCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();
    }

    /**
     * Basic help test
     */
    public function testDropTablesCommandHelp()
    {
        $this->exec('passbolt drop_tables -h');
        $this->assertExitSuccess();
        $this->assertOutputContains('Drop all the tables. Dangerous but useful for a full reinstall.');
        $this->assertOutputContains('cake passbolt drop_tables');
    }

    /**
     * Basic test
     */
    public function testDropTablesCommand()
    {
        $this->exec('passbolt drop_tables');
        $this->assertExitSuccess();

        // Assert that all tables were dropped.
        $tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
        $this->assertEmpty($tables);

        // Run migrations to recreate the lost tables.
        $this->exec('migrations migrate -c test -q --no-lock');
        SnifferRegistry::get('test')->restart();
        SnifferRegistry::get('test')->markAllTablesAsDirty();
    }

    /**
     * Basic failing test
     */
    public function testDropTablesCommandWrongDataSource()
    {
        $this->expectException(MissingDatasourceConfigException::class);
        $this->exec('passbolt drop_tables -d wrong_connection');
        $this->assertExitError();
    }
}
