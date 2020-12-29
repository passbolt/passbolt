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
 * @since         2.0.0
 */
namespace App\Utility\OpenPGP\Backends;

use App\Utility\OpenPGP\OpenPGPBackend;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use OpenPGP as OpenPGP;
use OpenPGP_Message as OpenPGP_Message;
use OpenPGP_PublicKeyPacket as OpenPGP_PublicKeyPacket;
use OpenPGP_SecretKeyPacket as OpenPGP_SecretKeyPacket;

/**
 * This class provides tools for Gpg operations.
 * It is an alternative implementation to the default OpenPGPBackend implementation that uses the php gnupg extension.
 * Instead this uses the Crypt_GPG pear package that calls the gpg command on the shell.
 */
class CryptGPG extends OpenPGPBackend
{
    /**
     * Gpg object.
     */
    protected $_gpg = null;

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        if (!class_exists('Crypt_GPG')) {
            trigger_error('You must install Crypt_GPG PEAR package to use Passbolt.', E_USER_ERROR);
        }

        if (Configure::read('passbolt.gpg.putenv')) {
            putenv('GNUPGHOME=' . Configure::read('passbolt.gpg.keyring'));
        }

        $options = array();
        if (Configure::read('passbolt.gpg.cryptGpg')) {
            $options = Configure::read('passbolt.gpg.cryptGpg');
        }

        $this->_gpg = new \Crypt_GPG($options);
    }

    /**
     * Set a key for encryption.
     *
     * @param string $armoredKey ASCII armored key data
     * @throws Exception if the key cannot be used to encrypt
     * @return bool true if success
     */
    public function setEncryptKey(string $armoredKey)
    {
        $this->_encryptKeyFingerprint = null;
        // Get the key info.
        $encryptKeyInfo = $this->getPublicKeyInfo($armoredKey);
        $fingerprint = $encryptKeyInfo['fingerprint'];

        try {
            $this->_gpg->addEncryptKey($fingerprint);
            $this->_encryptKeyFingerprint = $fingerprint;
        } catch (\Exception $e) {
            // It didn't work, maybe only key is not in the keyring
            // we import the key and retry
            $this->importKeyIntoKeyring($armoredKey);
            try {
                $this->_gpg->addEncryptKey($fingerprint);
                $this->_encryptKeyFingerprint = $fingerprint;
            } catch (\Exception $e) {
                throw new Exception(__('The key {0} cannot be used to encrypt.', $fingerprint));
            }
        }

        return true;
    }

    /**
     * Set a key for encryption.
     *
     * @param string $fingerprint fingerprint
     * @throws Exception if key is not present in keyring
     * @throws Exception if there was an issue to use the key to encrypt
     * @return bool true if success
     */
    public function setEncryptKeyFromFingerprint(string $fingerprint)
    {
        $this->_encryptKeyFingerprint = null;
        $this->assertKeyInKeyring($fingerprint);
        try {
            $this->_gpg->addEncryptKey($fingerprint);
            $this->_encryptKeyFingerprint = $fingerprint;
        } catch (\Exception $e) {
            throw new Exception(__('The key {0} cannot be used to encrypt.', $fingerprint));
        }

        return true;
    }

    /**
     * Set a key for decryption.
     *
     * @param string $armoredKey ASCII armored key data
     * @param string $passphrase to decrypt secret key
     * @throws Exception if the key cannot be found in the keyring
     * @throws Exception if the key is using a passphrase
     * @throws Exception if the key cannot be used to decrypt
     * @return bool true if success
     */
    public function setDecryptKey(string $armoredKey, string $passphrase)
    {
        $this->_decryptKeyFingerprint = null;

        // Get the key info.
        $decryptKeyInfo = $this->getKeyInfo($armoredKey);
        $fingerprint = $decryptKeyInfo['fingerprint'];

        try {
            $this->_gpg->addDecryptKey($fingerprint, $passphrase);
            $this->_decryptKeyFingerprint = $fingerprint;
        } catch (\Exception $e) {
            // It didn't work, maybe only key is not in the keyring
            // we import the key and retry
            $this->importKeyIntoKeyring($armoredKey);
            try {
                $this->_gpg->addDecryptKey($fingerprint, $passphrase);
                $this->_decryptKeyFingerprint = $fingerprint;
            } catch (\Exception $e) {
                $msg = __('The key {0} cannot be used to decrypt.', $fingerprint);
                throw new Exception($msg . ' ' . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Set a key for decryption.
     *
     * @param string $fingerprint fingerprint of a key in the keyring
     * @param string $passphrase to decrypt secret key
     * @throws Exception if the key cannot be found in the keyring
     * @throws Exception if the key is using a passphrase
     * @throws Exception if the key cannot be used to decrypt
     * @return bool true if success
     */
    public function setDecryptKeyFromFingerprint(string $fingerprint, string $passphrase)
    {
        $this->_decryptKeyFingerprint = null;

        try {
            $this->_gpg->addDecryptKey($fingerprint, $passphrase);
            $this->_decryptKeyFingerprint = $fingerprint;
        } catch (\Exception $e) {
            throw new Exception(__('The key {0} cannot be used to decrypt.', $fingerprint));
        }

        return true;
    }

    /**
     * Set a key for signing.
     *
     * @param string $armoredKey ASCII armored key data
     * @param string $passphrase passphrase
     * @throws Exception if the key is not already in the keyring
     * @throws Exception if the passphrase is not empty
     * @throws Exception if the key cannot be used for signing
     * @return bool
     * @throws Exception
     */
    public function setSignKey(string $armoredKey, string $passphrase)
    {
        $this->_signKeyFingerprint = null;

        $signKeyInfo = $this->getKeyInfo($armoredKey);
        $fingerprint = $signKeyInfo['fingerprint'];

        try {
            // The key is in the keyring try to use it as a sign key
            $this->_gpg->addSignKey($fingerprint, $passphrase);
            $this->_signKeyFingerprint = $fingerprint;
        } catch (\Exception $e) {
            // It didn't work, maybe only public key was in the keyring
            // we try to re-import the key
            $this->importKeyIntoKeyring($armoredKey);
            try {
                $this->_gpg->addSignKey($fingerprint, $passphrase);
                $this->_signKeyFingerprint = $fingerprint;
            } catch (\Exception $e) {
                $msg = __('Could not use key {0} for signing.', $fingerprint);
                throw new Exception($msg . ' ' . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Set key to be used for signing
     *
     * @throws Exception if the key is not already in the keyring
     * @throws Exception if the passphrase is not empty
     * @throws Exception if the key cannot be used for signing
     * @param string $fingerprint fingerprint
     * @param string $passphrase passphrase
     * @return true if success
     */
    public function setSignKeyFromFingerprint(string $fingerprint, string $passphrase)
    {
        $this->_signKeyFingerprint = null;

        try {
            $this->_gpg->addSignKey($fingerprint, $passphrase);
            $this->_signKeyFingerprint = $fingerprint;
        } catch (\Exception $e) {
            $msg = __('Could not use key {0} for signing.', $fingerprint);
            throw new Exception($msg . ' ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Check if an ASCII armored public key is parsable
     *
     * To do this, we try to unarmor the key. If the operation is successful, then we consider that
     * the key is a valid one.
     *
     * @param string $armoredKey ASCII armored key data
     * @return bool true if valid, false otherwise
     */
    public function isParsableArmoredPublicKey(string $armoredKey)
    {
        try {
            $this->assertGpgMarker($armoredKey, self::PUBLIC_KEY_MARKER);
        } catch (Exception $e) {
            return false;
        }

        // If we don't manage to unarmor the key, we consider it's not a valid one.
        $keyUnarmored = OpenPGP::unarmor($armoredKey, self::PUBLIC_KEY_MARKER);
        if ($keyUnarmored === false) {
            return false;
        }

        // Try to parse the key
        // @codingStandardsIgnoreStart
        $publicKey = @(\OpenPGP_PublicKeyPacket::parse($keyUnarmored));
        // @codingStandardsIgnoreEnd
        if (empty($publicKey) || empty($publicKey->fingerprint) || empty($publicKey->key)) {
            return false;
        }

        return true;
    }

    /**
     * Check if an ASCII armored private key is parsable
     *
     * To do this, we try to unarmor the key. If the operation is successful, then we consider that
     * the key is a valid one.
     *
     * @param  string $armoredKey ASCII armored key data
     * @return bool true if parsable false otherwise
     */
    public function isParsableArmoredPrivateKey(string $armoredKey)
    {
        try {
            $this->assertGpgMarker($armoredKey, self::PRIVATE_KEY_MARKER);
        } catch (Exception $e) {
            return false;
        }

        // If we don't manage to unarmor the key, we consider it's not a valid one.
        $keyUnarmored = OpenPGP::unarmor($armoredKey, self::PRIVATE_KEY_MARKER);
        if ($keyUnarmored == false) {
            return false;
        }

        // Try to parse the key
        // @codingStandardsIgnoreStart
        $privateKey = @(OpenPGP_SecretKeyPacket::parse($keyUnarmored));
        // @codingStandardsIgnoreEnd
        if (empty($privateKey) || empty($privateKey->fingerprint) || empty($privateKey->key)) {
            return false;
        }

        return true;
    }

    /**
     * Check if an ASCII armored signed message is parsable
     *
     * @param  string $armored ASCII armored signed message
     * @return bool
     */
    public function isParsableArmoredSignedMessage($armored)
    {
        try {
            $marker = $this->getGpgMarker($armored);
        } catch (Exception $e) {
            return false;
        }
        if ($marker !== self::SIGNED_MESSAGE_MARKER) {
            return false;
        }

        return true;
    }

    /**
     * Check if a message is valid.
     *
     * To do this, we try to unarmor the message. If the operation is successful, then we consider that
     * the message is a valid one.
     *
     * @param string $armored ASCII armored message data
     * @return bool true if valid, false otherwise
     */
    public function isValidMessage(string $armored)
    {
        try {
            $this->assertGpgMarker($armored, self::MESSAGE_MARKER);
        } catch (Exception $e) {
            return false;
        }
        $unarmored = OpenPGP::unarmor($armored, self::MESSAGE_MARKER);

        return !($unarmored === false || $unarmored === null);
    }

    /**
     * Get public key information.
     *
     * @param string $armoredKey the ASCII armored key block
     * @throws Exception if the armored key cannot be parsed
     * @return array key information (see getKeyInfo)
     */
    public function getPublicKeyInfo(string $armoredKey)
    {
        if (
            $this->isParsableArmoredPublicKey($armoredKey) === false
            && $this->isParsableArmoredPrivateKey($armoredKey) === false
        ) {
            throw new Exception(__('The public key could not be parsed.'));
        }

        return $this->getKeyInfo($armoredKey);
    }

    /**
     * Get key information
     *
     * Extract the information from the key and return them in an array:
     *  - fingerprint   : fingerprint of the key, string(40)
     *  - bits          : size / number of bits (int)
     *  - type          : algorithm used by the key (RSA, ELGAMAL, DSA, etc..)
     *  - key_id        : key id, string(8)
     *  - key_created   : date of creation of the key, timestamp
     *  - uid           : user id of the key following gpg standard (usually name surname (comment) <email>), string
     *  - expires       : expiration date or empty if no expiration date, timestamp
     *
     * Important note : this function is using OpenPgp-PHP library instead of php-gnupg to pre-validate the key.
     *
     * @param string $armoredKey the ASCII armored key block
     * @return array as described above
     */
    public function getKeyInfo(string $armoredKey)
    {
        // Unarmor the key.
        $keyUnarmored = OpenPGP::unarmor($armoredKey, $this->getGpgMarker($armoredKey));

        // Get the message.
        $msg = OpenPGP_Message::parse($keyUnarmored);

        // Parse public key.
        $publicKey = OpenPGP_PublicKeyPacket::parse($keyUnarmored);

        // Get Packets for public key.
        $publicKeyPacket = $msg->packets[0];

        // If the packet is not a valid publicKey Packet, then we can't retrieve the uid.
        if (!$publicKeyPacket instanceof OpenPGP_PublicKeyPacket) {
            throw new Exception(__('Invalid key. No public key package found.'));
        }

        // Get userId.
        $userIds = [];
        foreach ($msg->signatures() as $signatures) {
            foreach ($signatures as $signature) {
                if ($signature instanceof \OpenPGP_UserIDPacket) {
                    $userIds[] = sprintf('%s', $signature);
                }
            }
        }

        // Retrieve algorithm type.
        $type = OpenPGP_PublicKeyPacket::$algorithms[$publicKeyPacket->algorithm];

        // Retrieve key size.
        $bits = 0;
        if (isset(OpenPGP_PublicKeyPacket::$key_fields[$publicKeyPacket->algorithm])) {
            $keyFirstElt = OpenPGP_PublicKeyPacket::$key_fields[$publicKeyPacket->algorithm][0];
            $bits = OpenPGP::bitlength($publicKeyPacket->key[$keyFirstElt]);
        }

        // Build key information array.
        $info = [
            'key_id' => $publicKeyPacket->key_id,
            'fingerprint' => $publicKeyPacket->fingerprint(),
            'key_created' => $publicKey->timestamp,
            'expires' => $publicKeyPacket->expires($msg),
            'bits' => $bits,
            'type' => $type,
            'uid' => $userIds[0],
        ];

        return $info;
    }

    /**
     * Is key currently in keyring
     *
     * @param string $fingerprint fingerprint
     * @return bool true if in keyring false otherwise
     */
    public function isKeyInKeyring(string $fingerprint)
    {
        try {
            $keys = array_filter($this->_gpg->getKeys(), function($key) use ($fingerprint) {
                return $key->getPrimaryKey()->getFingerPrint() === strtoupper($fingerprint);
            }, ARRAY_FILTER_USE_BOTH);
        } catch (\Exception $e) {
            return false;
        }
        if (empty($keys) || $keys === false) {
            return false;
        }

        return true;
    }

    /**
     * Import a key into the local keyring.
     *
     * @param string $armoredKey the ASCII armored key block
     * @throws Exception if the key could not be imported
     * @return string key fingerprint
     */
    public function importKeyIntoKeyring(string $armoredKey)
    {
        $msg = __('Could not import the key.');
        try {
            $import = $this->_gpg->importKey($armoredKey);
        } catch (\Exception $e) {
            throw new Exception($msg);
        }
        if (!is_array($import)) {
            throw new Exception($msg);
        }
        if (!isset($import['fingerprint'])) {
            throw new Exception($msg);
        }

        return $import['fingerprint'];
    }

    /**
     * Encrypt a text and optionally sign it too
     * Do not forget to add a key to encrypt and optionally to sign
     *
     * @param string $text plain text to be encrypted.
     * @param bool $sign whether the encrypted message should be signed.
     * @throws Exception if no key was set to encrypt and optionally to sign
     * @throws Exception if there is an issue with the key to encrypt and optionally to sign
     * @return string encrypted text
     */
    public function encrypt(string $text, bool $sign = false)
    {
        $this->assertEncryptKey();
        if ($sign === true) {
            $msg = __('Could not use the key to sign and encrypt.');
            $this->assertSignKey();
            try {
                $encryptedText = $this->_gpg->encryptAndSign($text);
            } catch (\Exception $e) {
                throw new Exception($msg . $e->getMessage());
            }
            if ($encryptedText === false) {
                throw new Exception($msg);
            }
            $this->clearSignKeys();
        } else {
            $msg = __('Could not use the key to encrypt.');
            $this->assertEncryptKey();
            try {
                $encryptedText = $this->_gpg->encrypt($text);
            } catch (\Exception $e) {
                throw new Exception($msg . ' ' . $e->getMessage());
            }
            if ($encryptedText === false) {
                throw new Exception($msg);
            }
        }
        $this->clearDecryptKeys();

        return $encryptedText;
    }

    /**
     * Decrypt a text
     *
     * @param string $text ASCII armored encrypted text to be decrypted.
     * @param bool $verifySignature should signature be verified
     * @throws Exception if decryption fails
     * @return string decrypted text
     */
    public function decrypt(string $text, bool $verifySignature = false)
    {
        $decrypted = false;
        $this->assertDecryptKey();
        if ($verifySignature) {
            $this->assertVerifyKey();
            $fingerprint = $this->_verifyKeyFingerprint;
            $this->clearVerifyKeys();
        }
        try {
            if ($verifySignature === false) {
                $decrypted = $this->_gpg->decrypt($text);
            } else {
                $v = $this->_gpg->decryptAndVerify($text);
                $signatureInfo = $v['signatures'];
                $decrypted = $v['data'];
            }
        } catch (\Exception $e) {
            $this->clearDecryptKeys();
            throw new Exception(__('Decryption failed.'));
        }
        $this->clearDecryptKeys();

        if ($decrypted === false) {
            throw new Exception(__('Decryption failed.'));
        }
        if ($verifySignature) {
            if (empty($signatureInfo) || $signatureInfo[0]->getKeyFingerprint() !== $fingerprint) {
                $msg = __('Expected {0} and got {1}.', $fingerprint, $signatureInfo[0]->getKeyFingerprint());
                $msg = __('Decryption failed. Invalid signature.') . ' ' . $msg;
                throw new Exception($msg);
            }
        }

        return $decrypted;
    }

    /**
     * Verify a signed message.
     *
     * @param string $armored The armored signed message to verify.
     * @param string $fingerprint The fingerprint of the key to verify for.
     * @param mixed $plainText (optional) if this parameter is passed, it will be filled with the plain text.
     * @return void
     * @throws Exception If the armored signed message cannot be verified.
     */
    public function verify($armored, $fingerprint, &$plainText = null)
    {


        $msg = __('The message cannot be verified.');
        try {
            $signature = $this->_gpg->verify($armored, false, $plainText);
            if (empty($signature) || $signature[0]->getKeyFingerprint() !== $fingerprint) {
                throw new Exception($msg);
            }
        } catch (\Exception $e) {
            throw new Exception($msg);
        }
    }

    /**
     * Sign a text.
     *
     * @param string $text plain text to be signed.
     * @throws Exception if no key was set to sign
     * @throws Exception if there is an issue with the key to sign
     * @return string signed text
     */
    public function sign(string $text)
    {
        $msg = __('Could not sign the text. ');
        $this->assertSignKey();
        try {
            $signedText = $this->_gpg->sign($text);
            $this->clearSignKeys();
        } catch (\Exception $e) {
            $this->clearSignKeys();
            throw new Exception($msg . $e->getMessage());
        }
        if ($signedText === false) {
            throw new Exception($msg);
        }

        return $signedText;
    }

    /**
     * Removes all keys which were set for decryption before
     *
     * @return void
     */
    public function clearDecryptKeys()
    {
        $this->_decryptKeyFingerprint = null;
        $this->_gpg->cleardecryptkeys();
    }

    /**
     * Removes all keys which were set for signing before
     *
     * @return void
     */
    public function clearSignKeys()
    {
        $this->_signKeyFingerprint = null;
        $this->_gpg->clearsignkeys();
    }

    /**
     * Removes all keys which were set for encryption before
     *
     * @return void
     */
    public function clearEncryptKeys()
    {
        $this->_encryptKeyFingerprint = null;
        $this->_gpg->clearencryptkeys();
    }
}
