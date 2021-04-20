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
 * @since         3.3.0
 */
namespace Passbolt\EmailNotificationSettings\Test\TestCase;

use Cake\Database\Type;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Passbolt\EmailNotificationSettings\Database\Type\SerializedType;

class PluginTest extends TestCase
{
    /**
     * @var EmailQueueTable
     */
    public $EmailQueue;

    public function setUp(): void
    {
        $this->loadPlugins(['Passbolt/EmailNotificationSettings' => ['bootstrap' => true,]]);
        $this->EmailQueue = TableRegistry::getTableLocator()->get('EmailQueue.EmailQueue');
    }

    public function testEmailNotificationSettingsSerializationConfig()
    {
        $this->assertSame(
            'passbolt.serialized',
            $this->EmailQueue->getSchema()->getColumnType('template_vars')
        );
        $this->assertSame(
            'passbolt.serialized',
            $this->EmailQueue->getSchema()->getColumnType('headers')
        );
        $this->assertSame(
            'passbolt.serialized',
            $this->EmailQueue->getSchema()->getColumnType('attachments')
        );

        $this->assertSame(SerializedType::class, Type::getMap('passbolt.serialized'));
    }
}
