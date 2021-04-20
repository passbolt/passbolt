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
namespace Passbolt\WebInstaller\Test\TestCase\Controller;

use Cake\Datasource\ConnectionManager;
use Cake\Validation\Validation;
use Passbolt\WebInstaller\Test\Lib\WebInstallerIntegrationTestCase;

class InstallationControllerTest extends WebInstallerIntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->mockPassboltIsNotconfigured();
        $this->truncateTables();
        $this->initWebInstallerSession();
        $this->backupConfiguration();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->restoreConfiguration();
    }

    protected function getInstallSessionData()
    {
        $datasourceTest = $this->getTestDatasourceFromConfig();
        $data = [
            'initialized' => true,
            'hasExistingAdmin' => false,
            'database' => $datasourceTest,
            'gpg' => [
                'public_key_armored' => '-----BEGIN PGP PUBLIC KEY BLOCK-----

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
WXi2h/I=
=L/Vu
-----END PGP PUBLIC KEY BLOCK-----',
            'private_key_armored' => '-----BEGIN PGP PRIVATE KEY BLOCK-----

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
=GMak
-----END PGP PRIVATE KEY BLOCK-----',
            'fingerprint' => 'B16B0A095A9AF46D7D7FAA440A848FBED334B3EC',
            ],
            'email' => [
                'sender_name' => 'Webinstaller test',
                'sender_email' => 'webinstaller@passbolt.com',
                'host' => 'unreachable_host.dev',
                'tls' => 1,
                'port' => 587,
                'username' => 'webinstaller',
                'password' => 'webinstaller',
            ],
            'options' => [
                'full_base_url' => 'http://127.0.0.1:8081',
                'public_registration' => 0,
                'force_ssl' => 0,
            ],
            'first_user' => [
                'profile' => [
                    'first_name' => 'Web',
                    'last_name' => 'Installer',
                ],
                'username' => 'webinstaller@passbolt.com',
                'deleted' => 0,
                'role_id' => '0d6990c8-4aaa-4456-a333-00e803ba0828',
            ],
        ];

        return $data;
    }

    public function testWebInstallerInstallationViewSuccess()
    {
        $config = $this->getInstallSessionData();
        $this->initWebInstallerSession($config);
        $this->get('/install/installation');
        $data = $this->_getBodyAsString();
        $this->assertResponseOk();
        $this->assertStringContainsString('Installing', $data);
    }

    /**
     * @group triggerFilesystemChanges
     * @throws \PHPUnit\Exception
     */
    public function testWebInstallerInstallationDoInstallSuccess()
    {
        $this->skipTestIfNotWebInstallerFriendly();
        $connection = ConnectionManager::get('default');

        $config = $this->getInstallSessionData();
        $this->initWebInstallerSession($config);

        $tables = $connection->getSchemaCollection()->listTables();
        $this->assertEmpty($tables);

        $this->get('/install/installation/do_install.json');

        $tables = $connection->getSchemaCollection()->listTables();
        $this->assertNotEmpty($tables);

        $result = json_decode($this->_getBodyAsString(), true);

        $this->assertTrue(isset($result['user_id']));
        $this->assertTrue(isset($result['token']));
        $this->assertTrue(Validation::uuid($result['user_id']));
        $this->assertTrue(Validation::uuid($result['token']));
    }
}
