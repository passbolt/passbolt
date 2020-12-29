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
 * @since         2.10.0
 */
namespace App\Utility\OpenPGP;

use App\Utility\OpenPGP\Backends\Gnupg;
use App\Utility\OpenPGP\Backends\CryptGPG;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Http\Exception\InternalErrorException;

class OpenPGPBackendFactory
{
    const GNUPG = 'gnupg';
    const CRYPT_GPG = 'cryptGPG';

    /**
     * @var OpenPGPBackend
     */
    private static $instance = null;

    /**
     * Instantiate an OpenPGP Backend
     *
     * @param string $backend one of the supported backend
     * @throws InternalErrorException if backend if not supported
     * @return OpenPGPBackend
     */
    public static function create(string $backend = self::GNUPG)
    {
        switch ($backend) {
            case self::GNUPG:
                try {
                    return new Gnupg();
                } catch (Exception $exception) {
                    throw new InternalErrorException($exception->getMessage());
                }
                break;
            case self::CRYPT_GPG:
                try {
                    return new CryptGPG();
                } catch (Exception $exception) {
                    throw new InternalErrorException($exception->getMessage());
                }
                break;
            default:
                throw new InternalErrorException(__('This OpenPGP backend is not supported'));
        }
    }

    /**
     * Get a OpenPGP backend (Singleton pattern)
     *
     * @return OpenPGPBackend
     * @throws InternalErrorException if backend if not supported
     */
    public static function get()
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        self::$instance = self::create(Configure::read('passbolt.gpg.backend'));

        return self::$instance;
    }

    /**
     * Reset current instance
     * Useful if you want to change the config on the fly
     *
     * @return void
     */
    public static function reset()
    {
        self::$instance = null;
    }
}
