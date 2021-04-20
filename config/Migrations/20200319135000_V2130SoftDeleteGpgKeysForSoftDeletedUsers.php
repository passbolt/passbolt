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
 * @since         2.13.0
 */

use Migrations\AbstractMigration;

class V2130SoftDeleteGpgKeysForSoftDeletedUsers extends AbstractMigration
{
    /**
     * Up
     *
     * @return void
     */
    public function up()
    {
        // gpgkeys were not marked as soft deleted so we mark them as deleted if their associated user is soft deleted
        $this->execute("UPDATE gpgkeys SET deleted=TRUE where user_id IN (SELECT id from users where deleted=TRUE)");
    }
}
