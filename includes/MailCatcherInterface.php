<?php
/*
 * Interface MailCatcherInterface.
 * Copyright (C) 2020 Krishna Moniz
 * Copyright (C) 2007 WPForms
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
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @since 0.1
 */

namespace Kingmailer;

/**
 * Interface MailCatcherInterface allows to overload interactions between 
 * our API mailer and different versions of the MailCatcher class
 *
 * @since 0.1
 */
interface MailCatcherInterface {

	/**
	 * Modify the default send() behaviour.
	 * For those mailers, that relies on PHPMailer class - call it directly.
	 * For others - init the correct provider and process it.
	 *
	 * @since 0.1
	 *
	 * @throws \phpmailerException|\PHPMailer\PHPMailer\Exception When sending via PhpMailer fails for some reason.
	 *
	 * @return bool
	 */
	public function send();

	/**
	 * Get the PHPMailer line ending.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function get_line_ending();

	/**
	 * Create a unique ID to use for multipart email boundaries.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function generate_id();
}
