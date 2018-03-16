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

use Eventum\Crypto\CryptoManager;
use Eventum\Crypto\CryptoUpgradeManager;
use Eventum\Db\AbstractMigration;

class EventumCrypto2Upgrade extends AbstractMigration
{
    /**
     * There is no downgrade support.
     */
    public function up()
    {
        if (CryptoManager::encryptionEnabled()) {
            $cm = new CryptoUpgradeManager();
            $cm->regenerateKey();
        }
    }
}
