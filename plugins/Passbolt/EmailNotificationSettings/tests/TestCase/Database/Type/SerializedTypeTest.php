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
namespace Passbolt\EmailNotificationSettings\Test\TestCase\Database\Type;

use App\Model\Entity\User;
use App\Test\Factory\UserFactory;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class SerializedTypeTest extends TestCase
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

    public function testThatEmailsGetCorrectlySerializedAndUnserialized()
    {
        $users = UserFactory::make(2)->user()->getEntities();
        $someText = 'Foo bar';
        $someInteger = 123;

        // Sets variables in the email.
        $data = compact('users', 'someText', 'someInteger');
        $result = $this->EmailQueue->enqueue('foo@bar.test', $data);
        $this->assertTrue($result);

        $email = $this->EmailQueue->find()->firstOrFail();
        $vars = $email->get('template_vars');

        $this->assertEquals($users, $vars['users']);
        $this->assertInstanceOf(User::class, $vars['users'][0]);
        $this->assertInstanceOf(User::class, $vars['users'][1]);

        $this->assertSame($someText, $vars['someText']);
        $this->assertSame($someInteger, $vars['someInteger']);
    }
}
