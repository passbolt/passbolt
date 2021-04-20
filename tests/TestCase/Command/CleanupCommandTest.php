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

use App\Command\CleanupCommand;
use App\Test\Factory\UserFactory;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use CakephpTestMigrator\Migrator;

class CleanupCommandTest extends TestCase
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

        CleanupCommand::resetCleanups();
    }

    /**
     * Basic help test
     */
    public function testCleanupCommandHelp()
    {
        $this->exec('passbolt cleanup -h');
        $this->assertExitSuccess();
        $this->assertOutputContains('Cleanup and fix issues in database.');
        $this->assertOutputContains('cake passbolt cleanup');
    }

    /**
     * Basic test
     */
    public function testCleanupCommandFixMode()
    {
        Migrator::migrate();
        UserFactory::make()->admin()->persist();
        $this->exec('passbolt cleanup');
        $this->assertExitSuccess();
        $this->assertOutputContains('Cleanup shell');
        $this->assertOutputContains('(fix mode)');
    }

    /**
     * Basic test dry run
     */
    public function testCleanupCommandDryRun()
    {
        UserFactory::make()->admin()->persist();
        $this->exec('passbolt cleanup --dry-run');
        $this->assertExitSuccess();
        $this->assertOutputContains('Cleanup shell');
        $this->assertOutputContains('(dry-run)');
    }
}
