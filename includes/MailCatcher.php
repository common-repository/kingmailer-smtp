<?php
/*
 * Class MailCatcher.
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
 */

namespace Kingmailer;

// Load PHPMailer class, so we can subclass it.
if ( ! class_exists( 'PHPMailer', false ) ) {
	require_once ABSPATH . WPINC . '/class-phpmailer.php';
}

if ( ! class_exists( 'MailCatcherInterface', false ) ) {
	require_once dirname(__FILE__) . '/MailCatcherInterface.php';
}

if ( ! class_exists( 'APIMailer', false ) ) {
	require_once dirname(__FILE__) . '/APIMailer.php';
}

/**
 * Class MailCatcher replaces the \PHPMailer and modifies the email sending logic.
 * Thus, we can use other mailers API to do what we need, or stop emails completely.
 *
 * @since 0.1
 */
class MailCatcher extends \PHPMailer implements MailCatcherInterface {

	/**
	 * Callback Action function name.
	 *
	 * The function that handles the result of the send email action.
	 * It is called out by send() for each email sent.
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	public $action_function = '\WPMailSMTP\Processor::send_callback';

	/**
	 * Modify the default send() behaviour.
	 * For those mailers, that relies on PHPMailer class - call it directly.
	 * For others - init the correct provider and process it.
	 *
	 * @since 0.1
	 *
	 * @throws \phpmailerException When sending via PhpMailer fails for some reason.
	 *
	 * @return bool
	 */
	public function send() {

		// Get the plugin options. These specify the mailer type
		$options = get_option('kingmailer-smtp');

		// TODO: test if adding an XMailer will improve the chances of something not being labelled spam
		// Define a custom header, that will be used to identify the plugin and the mailer.
		// $this->XMailer = 'Kingmailer ' . KINGMAILERCO_SMTP_PLUGIN_VER;

		// Use the default PHPMailer if the user specified SMTP
		if (! (bool) $options['use_api']) {
			try {
				// Allow to hook early to catch any early failed emails.
				do_action( 'kingmailer_smtp_pre_send_before', $this );

				// Prepare all the headers.
				if ( ! $this->preSend() ) {
					return false;
				}

				// Allow to hook after all the preparation before the actual sending.
				do_action( 'kingmailer_smtp_send_before', $this );

				// Send the actual mail
				return $this->postSend();

			} catch ( \phpmailerException $e ) {

				// Clear the mail header and throw an error if found
				$this->mailHeader = '';
				$this->setError( $e->getMessage() );
				if ( $this->exceptions ) {
					throw $e;
				}
				return false;
			}
		}

		// We need this so that the \PHPMailer class will correctly prepare all the headers.
		$this->Mailer = 'mail';

		// Prepare everything (including the message) for sending.
		if ( ! $this->preSend() ) {
			return false;
		}

		// Get the api_mailer  
		$api_mailer = new APIMailer( $this );

		if ( ! $api_mailer ) {
			return false;
		}

		/*
		 * Send the actual email.
		 * We reuse everything, that was preprocessed for usage in PHPMailer.
		 */
		$api_mailer->send();

		$is_sent = $api_mailer->is_email_sent();

		// Allow to perform any actions with the data.
		do_action( 'kingmailer_smtp_send_after', $api_mailer, $this );

		return $is_sent;
	}

	/**
	 * Returns all custom headers.
	 * In older versions of \PHPMailer class this method didn't exist.
	 * As we support WordPress 3.6+ - we need to make sure this method is always present.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function getCustomHeaders() {

		return $this->CustomHeader;
	}

	/**
	 * Get the PHPMailer line ending.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function get_line_ending() {

		return $this->LE; // phpcs:ignore
	}

	/**
	 * Create a unique ID to use for multipart email boundaries.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function generate_id() {

		return $this->generateId();
	}
}
