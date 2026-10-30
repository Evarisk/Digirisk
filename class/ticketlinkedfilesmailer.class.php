<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    class/ticketlinkedfilesmailer.class.php
 * \ingroup digiriskdolibarr
 * \brief   Ticket that adds files of its directory to the mails of the native message form
 */

// load Dolibarr librairies
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';

/**
 * Ticket whose mails also carry files already in its directory (#5581)
 *
 * The native message form of ticket/card.php can only attach files uploaded on the spot:
 * Ticket::newMessage() moves each file of the session list into the ticket directory, and renames it
 * when the name is taken. A file of the ticket put in that list would come back as a copy under
 * another name. This class leaves the list alone and adds the files at the last step, to every mail
 * newMessage() sends: neither copied, nor moved, nor tied to the new message.
 */
class TicketLinkedFilesMailer extends Ticket
{
    /**
     * @var array Files of the ticket to add, dol_dir_list() entries
     */
    public $linkedFiles = [];

    /**
     * @var string[] Names of the files that left with at least one mail
     */
    public $linkedFilesSent = [];

    /**
     * Send the mail with the files of the ticket added to the uploaded ones
     *
     * @param  string   $subject           Email subject
     * @param  string   $message           Email message
     * @param  int      $send_internal_cc  Receive a copy on internal email at TICKET_NOTIFICATION_EMAIL_FROM
     * @param  array    $array_receiver    Array of receivers
     * @param  string[] $filename_list     List of files to attach (full path of filename on file system)
     * @param  string[] $mimetype_list     List of MIME type of attached files
     * @param  string[] $mimefilename_list List of attached file names in message
     * @param  array    $array_receiver_cc Array of receivers in CC
     * @param  string   $from              Email from
     * @param  string   $replyto           Reply to
     * @return bool                        True if mail sent to at least one receiver, false otherwise
     */
    public function sendTicketMessageByEmail($subject, $message, $send_internal_cc = 0, $array_receiver = [], $filename_list = [], $mimetype_list = [], $mimefilename_list = [], $array_receiver_cc = [], $from = '', $replyto = '')
    {
        $addedNames = [];
        foreach ($this->linkedFiles as $linkedFile) {
            // An upload under the same name was renamed by newMessage(): a name met here is the same file.
            if (!in_array($linkedFile['name'], $mimefilename_list, true)) {
                $filename_list[]     = $linkedFile['fullname'];
                $mimetype_list[]     = dol_mimetype($linkedFile['name']);
                $mimefilename_list[] = $linkedFile['name'];
                $addedNames[]        = $linkedFile['name'];
            }
        }

        $result = parent::sendTicketMessageByEmail($subject, $message, $send_internal_cc, $array_receiver, $filename_list, $mimetype_list, $mimefilename_list, $array_receiver_cc, $from, $replyto);
        if ($result) {
            $this->linkedFilesSent = array_values(array_unique(array_merge($this->linkedFilesSent, $addedNames)));
        }

        return $result;
    }
}
