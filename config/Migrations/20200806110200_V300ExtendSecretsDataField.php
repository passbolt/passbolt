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

class V300ExtendSecretsDataField extends AbstractMigration
{
    /**
     * Up
     *
     * @return void
     */
    public function up()
    {
        switch($this->getAdapter()->getOptions()["adapter"]) {
        case "pgsql": {
            $this->execute('ALTER TABLE secrets ALTER COLUMN data TYPE TEXT;');
            break;
        }
        default:
            $this->execute('ALTER TABLE `secrets` MODIFY `data` MEDIUMTEXT NOT NULL;');
        }

    }
}
// @codingStandardsIgnoreEnd
