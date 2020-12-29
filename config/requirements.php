<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
if (version_compare(PHP_VERSION, '7.0.0') < 0) {
    trigger_error('Your PHP version must be equal or higher than 7.0.0 to use Passbolt.', E_USER_ERROR);
}

if (!extension_loaded('intl')) {
    trigger_error('You must enable the intl extension to use Passbolt.', E_USER_ERROR);
}

if (!extension_loaded('mbstring')) {
    trigger_error('You must enable the mbstring extension to use Passbolt.', E_USER_ERROR);
}

/*
 *  Passbolt requirements
 */
// if (!extension_loaded('gnupg')) {
//    trigger_error('You must enable the gnupg extension to use Passbolt.', E_USER_ERROR);
//}

if (!(extension_loaded('gd') || extension_loaded('imagick'))) {
    trigger_error('You must enable the gd or imagick extensions to use Passbolt.', E_USER_ERROR);
}
