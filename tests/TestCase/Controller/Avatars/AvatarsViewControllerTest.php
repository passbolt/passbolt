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
 * @since         3.0.0
 */

namespace App\Test\TestCase\Controller\Avatars;

use App\Model\Table\AvatarsTable;
use App\Test\Lib\AppIntegrationTestCase;
use App\Test\Lib\Model\AvatarsModelTrait;
use App\Utility\Filesystem\DirectoryUtility;
use App\Utility\UuidFactory;
use App\View\Helper\AvatarHelper;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * App\Controller\AvatarsController Test Case
 *
 * @uses \App\Controller\AvatarsController
 */
class AvatarsViewControllerTest extends AppIntegrationTestCase
{
    use AvatarsModelTrait;
    use IntegrationTestTrait;

    /**
     * @var \App\Model\Table\AvatarsTable
     */
    public $Avatars;

    public function setUp(): void
    {
        parent::setUp();
        $this->Avatars = TableRegistry::getTableLocator()->get('Avatars');
        $this->assertFileExists($this->Avatars->getCacheDirectory());
        $this->Avatars->setCacheDirectory(TMP . 'tests' . DS . 'avatars');
        DirectoryUtility::removeRecursively($this->Avatars->getCacheDirectory());
    }

    public function tearDown(): void
    {
        unset($this->Avatars);
        parent::tearDown();
    }

    public function validFormatDataProvider()
    {
        return [
            [AvatarsTable::FORMAT_SMALL . AvatarHelper::IMAGE_EXTENSION],
            [AvatarsTable::FORMAT_MEDIUM . AvatarHelper::IMAGE_EXTENSION],
        ];
    }

    public function nonValidFormatDataProvider()
    {
        return [
            [AvatarsTable::FORMAT_SMALL],
            [AvatarsTable::FORMAT_MEDIUM],
            [AvatarsTable::FORMAT_MEDIUM . '.wrong_extension'],
        ];
    }

    /**
     * Test view method on non existent Avatar
     *
     * @dataProvider validFormatDataProvider
     * @param string $format
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testViewNonExistent(string $format)
    {
        $id = UuidFactory::uuid();
        $this->get("avatars/view/$id/$format");
        $this->assertFileResponse($this->Avatars->getFallBackFileName($format));
    }

    /**
     * Test view method on non existent Avatar
     *
     * @dataProvider validFormatDataProvider
     * @param string $format
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testViewOnExistent(string $format)
    {
        $avatar = $this->createAvatar();

        $expectedFileName =
            $this->Avatars->getCacheDirectory() .
            $this->Avatars->getOrCreateAvatarDirectory($avatar) . $format;

        $this->get('avatars/view/' . $avatar->id . '/' . $format);
        $this->assertFileResponse($expectedFileName);

        // Ensure that the virtual field is correctly constructed.
        $virtualField = [
            AvatarsTable::FORMAT_MEDIUM => AvatarHelper::getAvatarUrl($avatar, AvatarsTable::FORMAT_MEDIUM),
            AvatarsTable::FORMAT_SMALL => AvatarHelper::getAvatarUrl($avatar, AvatarsTable::FORMAT_SMALL),
        ];
        $this->assertSame($virtualField, $avatar->url);
    }

    /**
     * Test view on a non valid format.
     *
     * @dataProvider nonValidFormatDataProvider
     */
    public function testViewOnWrongFormat(string $format)
    {
        $avatar = $this->createAvatar();

        $this->get('avatars/view/' . $avatar->id . '/' . $format);
        $this->assertFileResponse($this->Avatars->getFallBackFileName());
    }
}
