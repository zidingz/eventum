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

namespace Eventum\Test\Mail;

use Eventum\Mail\MailMessage;
use Eventum\Test\TestCase;

/**
 * @group mail
 */
class MailParseTest extends TestCase
{
    /**
     * Test that HTML entities used in text/html part get decoded
     */
    public function testParseHtmlEntities()
    {
        $full_message = $this->readDataFile('encoding.txt');

        $mail = MailMessage::createFromString($full_message);
        $text = $mail->getMessageBody();
        $this->assertEquals(
            "\npöördumise töötaja.\n<b>Võtame</b> töösse võimalusel.\npöördumisele süsteemis\n\n", $text
        );
    }

    public function testBug684922()
    {
        $message = $this->readDataFile('bug684922.txt');
        $mail = MailMessage::createFromString($message);

        $this->assertEquals('', $mail->getMessageBody());
    }
}
