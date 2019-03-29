<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

namespace Eventum\Crypto;

use Email_Account;
use Eventum\Event\ConfigUpdateEvent;
use Eventum\Event\SystemEvents;
use Eventum\EventDispatcher\EventManager;
use Setup;
use Zend\Config\Config;

class CryptoUpgradeManager
{
    /** @var Config */
    private $config;

    public function __construct()
    {
        $this->config = Setup::get();
    }

    /**
     * Perform few checks that enable/disable can be performed
     *
     * @param bool $enable TRUE if action is to enable, FALSE if action is to disable
     * @throws CryptoException
     */
    private function canPerform($enable): void
    {
        $enabled = CryptoManager::encryptionEnabled();
        if (($enabled && $enable) || (!$enabled && !$enable)) {
            $state = $enabled ? 'enabled' : 'disabled';
            throw new CryptoException("Can not perform, already $state");
        }

        CryptoManager::canEncrypt();

        // test that config can be saved before doing anything
        $res = Setup::save();
        if ($res !== 1) {
            throw new CryptoException('Can not save config');
        }

        // test that key file can be updated
        $km = new CryptoKeyManager();
        $km->canUpdate();
    }

    /**
     * Enable encryption
     *
     * @throws CryptoException if that can not be performed
     */
    public function enable(): void
    {
        $this->canPerform(true);

        $this->config['encryption'] = 'enabled';
        if (!CryptoManager::encryptionEnabled()) {
            throw new CryptoException('bug');
        }

        $this->upgradeConfig();
        $this->upgradeEmailAccounts();

        Setup::save();
    }

    /**
     * Disable encryption
     */
    public function disable(): void
    {
        $this->canPerform(false);
        $this->downgradeConfig();
        $this->downgradeEmailAccounts();

        $this->config['encryption'] = 'disabled';
        if (CryptoManager::encryptionEnabled()) {
            throw new CryptoException('bug');
        }

        Setup::save();
    }

    /**
     * Generate new encryption key and re-encrypt data
     */
    public function regenerateKey(): void
    {
        if (!CryptoManager::encryptionEnabled()) {
            throw new CryptoException('Encryption not enabled');
        }

        $this->disable();
        $km = new CryptoKeyManager();
        $km->regen();
        $this->enable();
    }

    /**
     * Upgrade config so that values contain EncryptedValue where some secrecy is wanted
     */
    private function upgradeConfig(): void
    {
        $event = new ConfigUpdateEvent($this->config);

        EventManager::dispatch(SystemEvents::CONFIG_CRYPTO_UPGRADE, $event);
    }

    /**
     * Downgrade config: remove all EncryptedValue elements
     */
    private function downgradeConfig(): void
    {
        $event = new ConfigUpdateEvent($this->config);
        EventManager::dispatch(SystemEvents::CONFIG_CRYPTO_DOWNGRADE, $event);
    }

    private function upgradeEmailAccounts(): void
    {
        // encrypt email account passwords
        $accounts = Email_Account::getList();
        foreach ($accounts as $account) {
            $account = Email_Account::getDetails($account['ema_id'], true);
            /** @var EncryptedValue $password */
            $password = $account['ema_password'];
            // the raw value contains the original plaintext
            Email_Account::updatePassword($account['ema_id'], $password->getEncrypted());
        }
    }

    private function downgradeEmailAccounts(): void
    {
        $accounts = Email_Account::getList();

        // collect passwords when encryption enabled
        $passwords = [];
        $this->config['encryption'] = 'enabled';
        foreach ($accounts as $account) {
            $account = Email_Account::getDetails($account['ema_id'], true);
            /** @var EncryptedValue $password */
            $password = $account['ema_password'];
            $passwords[$account['ema_id']] = $password->getValue();
        }

        // save passwords when encryption disabled
        $this->config['encryption'] = 'disabled';
        foreach ($passwords as $ema_id => $password) {
            Email_Account::updatePassword($ema_id, $password);
        }
    }

    /**
     * Key rotation method -- decrypt with your old key then re-encrypt with your new key
     *
     * @param string $ciphertext
     * @param string $key the new key
     * @return string
     */
    public function rotate($ciphertext, $key)
    {
        if (!CryptoManager::encryptionEnabled()) {
            return $ciphertext;
        }

        return CryptoManager::encrypt(CryptoManager::decrypt($ciphertext), $key);
    }
}
