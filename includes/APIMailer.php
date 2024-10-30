<?php
/*
 * Class MailCatcherV6.
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

// use WPMailSMTP\MailCatcherInterface;
// use WPMailSMTP\Providers\MailerAbstract;
// use WPMailSMTP\WP;

/**
 * Class APIMailer.
 *
 * @since 0.1
 */
class APIMailer {


	/**
	 *  TODO Delete (from wp-mail-smtp)
	 */
	protected $mailer = '';
	protected $options;


	/**
	 * Which response code from HTTP provider is considered to be successful?
	 *
	 * @since 0.1
	 *
	 * @var int
	 */
	protected $email_sent_code = 200;
	/**
	 * @since 0.1
	 *
	 * @var MailCatcherInterface
	 */
	protected $phpmailer;


	/**
	 * URL to make an API request to.
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $url = 'https://kingmailer.org/api/v1/send/message';
	/**
	 * @since 0.1
	 *
	 * @var array
	 */
	protected $headers = array();
	/**
	 * @since 0.1
	 *
	 * @var array
	 */
	protected $body = array();
	/**
	 * @since 0.1
	 *
	 * @var mixed
	 */
	protected $response = array();

	/**
	 * Mailer constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param MailCatcherInterface $phpmailer The MailCatcher object.
	 */
	public function __construct( $phpmailer ) {

		// TODO Get the url and api key from the options
		$this->options = get_option('kingmailer-smtp',  
			array(
				"api_key" => "",
				"api_host" => ""
		  ));

		// Make sure we have a valid URL and api key to for this instance to be useful.
		if ( empty($this->options['api_key']) ) {
		// if ( empty($this->options['api_host']) || empty($this->options['api_key']) ) {
			return;
		}

		// Now we can copy the values from PHPMailer to our API mailer
		$this->process_phpmailer($phpmailer);

	}
	
	/**
	 * Set individual header key=>value pair for the email.
	 *
	 * @since 0.1
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function set_api_header( $name, $value ) {

		$name = sanitize_text_field( $name );

		if ( !empty( $name ) ) {
			// TODO consider not sanitizing the value (specifically for API keys)
			$this->headers[ $name ] = APIMailer::sanitize_value( $value );
		}
	}

	/**
	 * Email-related custom headers should be in the body of API call.
	 *
	 * @since 0.1
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function set_body_header( $name, $value , $body_key = 'headers') {

		$name = sanitize_text_field( $name );
		if ( ! empty( $name ) ) {
			return;
		}

		$headers = isset( $this->body[$body_key] ) ? (array) $this->body[$body_key] : array();
		$headers[ $name ] = APIMailer::sanitize_value( $value );
		$this->set_body_param( array( $body_key => $headers ));
	}

	/**
	 * From address should be in the body of API call.
	 *
	 * @since 0.1
	 *
	 * @param string $email
	 * @param string $name
	 */
	public function set_from( $email, $name = '', $body_key = 'from') {

		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return;
		}

		// Both the "name <local-part@domain.tld>" and "local-part@domain.tld" form are correct.
		if ( ! empty( $name ) ) {
			$from = "{$name} <{$email}>";
		} else {
			$from = $email;
		}

		$this->set_body_param(
			array(
				$body_key => $from,
			)
		);
	}

	/**
	 * Recipients should be in the body of API call.
	 *
	 * @since 0.1
	 *
	 * @param array $emails
	 */
	public function set_recipients( $emails, $body_key = 'to' ) {

		if ( empty( $emails ) ) {
			return;
		}

	    // The API expects the recipient field to be an array
		$data    = array();

		// Iterate over all emails add them to our data
		foreach ( $emails as $email ) {
			$addr   = isset( $email[0] ) ? $email[0] : false;
			$name   = isset( $email[1] ) ? $email[1] : false;

			if ( ! filter_var( $addr, FILTER_VALIDATE_EMAIL ) ) {
				continue;
			}

			// Both the "name <local-part@domain.tld>" and "local-part@domain.tld" form are correct.
			if ( ! empty( $name ) ) {
				$holder = "{$name} <{$addr}>";
			} else {
				$holder = $addr;
			}

			array_push( $data, $holder );
		}

		if ( ! empty( $data ) ) {
			$this->set_body_param(
				array(
					$body_key => $data
				)
			);
		}
	}	

	/**
	 * Set email subject.
	 *
	 * @since 0.1
	 *
	 * @param string $subject
	 */
	public function set_subject( $subject, $body_key = 'subject' ) {

		$this->set_body_param(
			array(
				$body_key => $subject,
			)
		);
	}

	/**
	 * Set content body.
	 *
	 * @since 0.1
	 *
	 * @param string $content
	 */
	public function set_content( $content, $body_key = 'plain_body' ) {

		$this->set_body_param(
			array(
				$body_key => $content,
			)
		);
	}

	/**
	 * Kingmailer does not support return_path params.
	 * So we do nothing.
	 *
	 * @since 0.1
	 *
	 * @param string $from_email
	 */
	public function set_return_path( $from_email ) {}

	/**
	 * The reply_to address should be in the body of API call.
	 *
	 * @since 0.1
	 *
	 * @param string $email
	 * @param string $name
	 */
	public function set_reply_to( $email, $name = '', $body_key = 'reply_to') {

		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return;
		}

		// Both the "name <local-part@domain.tld>" and "local-part@domain.tld" form are correct.
		if ( ! empty( $name ) ) {
			$reply_to = "{$name} <{$email}>";
		} else {
			$reply_to = $email;
		}

		$this->set_body_param(
			array(
				$body_key => $reply_to,
			)
		);
	}

	/**
	 * Set a tag.
	 * Needs to be figured out, do nothing for now.
	 *
	 * @since 0.1
	 *
	 * @param string $tag
	 */
	public function set_tag( $tag, $body_key = 'tag' ) {}


	/**
	 * Kingmailer accepts an array of files content in body, so we will include all files and send.
	 * Doesn't handle exceeding the limits etc, as this is done and reported by Kingmailer API. (TODO verify)
	 *
	 * @since 0.1
	 *
	 * @param array $attachments
	 */
	public function set_attachments( $attachments, $body_key = 'attachments' ) {

		if ( empty( $attachments ) ) {
			return;
		}

		$data = array();

		foreach ( $attachments as $attachment ) {
			$file = false;

			/*
			 * We are not using WP_Filesystem API as we can't reliably work with it.
			 * It is not always available, same as credentials for FTP.
			 */
			try {
				if ( is_file( $attachment[0] ) && is_readable( $attachment[0] ) ) {
					$file = file_get_contents( $attachment[0] ); // phpcs:ignore
				}
			}
			catch ( \Exception $e ) {
				$file = false;
			}

			if ( $file === false ) {
				continue;
			}

			$filetype = str_replace( ';', '', trim( $attachment[4] ) );

			$data[] = array(
				'data'     => base64_encode( $file ), // string, 1 character.
				'content_type'        => $filetype, // string, no ;, no CRLF.
				'name'    => empty( $attachment[2] ) ? 'file-' . wp_hash( microtime() ) . '.' . $filetype : trim( $attachment[2] ), // required string, no CRLF.
				'disposition' => in_array( $attachment[6], array( 'inline', 'attachment' ), true ) ? $attachment[6] : 'attachment', // either inline or attachment.
				'content_id'  => empty( $attachment[7] ) ? '' : trim( (string) $attachment[7] ), // string, no CRLF.
			);
		}

		if ( ! empty( $data ) ) {
			$this->set_body_param(
				array(
					$body_key => $data,
				)
			);
		}
	}

	/**
	 * Set the request params, that goes to the body of the HTTP request.
	 *
	 * @since 0.1
	 *
	 * @param array $param Key=>value of what should be sent to a 3rd party API.
	 */
	protected function set_body_param( $param ) {

		$this->body = APIMailer::array_merge_recursive( $this->body, $param );
	}

	/**
	 * Re-use the MailCatcher class methods and properties.
	 * This implementation of process_phpmailer is Kingmailer specific
	 * Different APIs should have a different implementation of process_phpmailer
	 *
	 * @since 0.1
	 *
	 * @param MailCatcherInterface $phpmailer The MailCatcher object.
	 */
	public function process_phpmailer( $phpmailer ) {

		// Make sure that we have access to PHPMailer class methods.
		if ( ! $phpmailer instanceof MailCatcherInterface ) {
			return;
		}

		// Save the PHPMailer instance to simplify references
		$this->phpmailer = $phpmailer;

		// Set the API headers
		$this->set_api_header( 'X-Server-API-Key', $this->options['api_key'] );
		$this->set_api_header( 'Content-Type' , 'application/json' );		

		// Set the body headers
		$headers = $this->phpmailer->getCustomHeaders();
		foreach ( $headers as $header ) {
			$name  = isset( $header[0] ) ? $header[0] : false;
			$value = isset( $header[1] ) ? $header[1] : false;

			$this->set_body_header( $name, $value );
		}

		// Set the sender		
		$this->set_from( $this->phpmailer->From, $this->phpmailer->FromName );
		$this->set_from( $this->phpmailer->From, $this->phpmailer->FromName, 'sender' );
		
		// Set the recipients
		$this->set_recipients($this->phpmailer->getToAddresses(), 'to');
		$this->set_recipients($this->phpmailer->getCcAddresses(), 'cc');
		$this->set_recipients($this->phpmailer->getBccAddresses(), 'bcc');

		// Set the subject
		$this->set_subject( $this->phpmailer->Subject );

		// Set the content
		if ( $this->phpmailer->ContentType === 'text/plain' ) {
			$this->set_content( $this->phpmailer->Body , 'plain_body');
		} else {
			$this->set_content( $this->phpmailer->Body, 'html_body');

			$alt_body = $this->phpmailer->AltBody;
			if(empty($alt_body) ){
				$alt_body = wp_strip_all_tags( $alt_body, false );
			}
			$this->set_content( $alt_body, 'plain_body');
		}

		// Set the return path and reply address TODO
		$this->set_return_path( $this->phpmailer->From );
		// $this->set_reply_to( $this->phpmailer->getReplyToAddresses() );

		/*
		 * In some cases we will need to modify the internal structure
		 * of the body content, if attachments are present.
		 * So lets make this call the last one.
		 */
		$this->set_attachments( $this->phpmailer->getAttachments() );

		// TODO: test if adding an XMailer will improve the chances of something not being labelled spam
		// Define a custom header, that will be used to identify the plugin and the mailer.
		// $this->set_body_header( 'X-Mailer', 'Kingmailer ' . KINGMAILERCO_SMTP_PLUGIN_VER);

	}

	/**
	 * Get the API headers.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function get_api_headers() {

		return apply_filters( 'kingmailer_apimailer_get_headers', $this->headers );
	}
	
	/**
	 * Get the email body as encoded JSON object.
	 *
	 * @since 0.1
	 *
	 * @return string|array
	 */
	public function get_body() {

		return wp_json_encode( apply_filters( 'kingmailer_apimailer_get_body', $this->body ));
	}	

	/**
	 * Get the default params, required for wp_safe_remote_post().
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	protected function get_remote_post_params() {

		$timeout = (int) ini_get( 'max_execution_time' );

		return apply_filters(
			'kingmailer_apimailer_get_remote_post_params',
			array(
				'timeout'     => $timeout ? $timeout : 30,
				'httpversion' => '1.1',
				'blocking'    => true,
				'method'      => 'POST',
				'data_format' => 'body',
			)
		);
	}

	/**
	 * Send the email.
	 *
	 * @since 0.1
	 */
	public function send() {


		$params = APIMailer::array_merge_recursive(
			$this->get_remote_post_params(),
			array(
				'headers' => $this->get_api_headers(),
				'body'    => $this->get_body()
			)
		);

		//




		$response = wp_safe_remote_post( $this->url, $params );


		$this->process_response( $response );
	}

	/**
	 * We might need to do something after the email was sent to the API.
	 * In this method we preprocess the response from the API.
	 *
	 * @since 0.1
	 *
	 * @param mixed $response
	 */
	protected function process_response( $response ) {


		if ( is_wp_error( $response ) ) {

			// Save the error text.
			$errors = $response->get_error_messages();
			foreach ( $errors as $error ) {

			}

			return;
		}


		if ( isset( $response['body'] ) && APIMailer::is_json( $response['body'] ) ) {


			$response['body'] = json_decode( $response['body'] );
		}


		$this->response = $response;
	}

	/**
	 * Whether the email is sent or not.
	 * We basically check the response code from a request to provider.
	 * Might not be 100% correct, not guarantees that email is delivered.
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	public function is_email_sent() {

		$is_sent = false;


		if ( wp_remote_retrieve_response_code( $this->response ) === $this->email_sent_code 
			&& isset( $this->response['body'] )
			&& isset( $this->response['body']->status )
			&& $this->response['body']->status  === "success") {


			$is_sent = true;
		} else {
			// $error = $this->get_response_error();

		}


		return $is_sent;
	}


	// TODO Debugging and error information
	// Check Sendgrid mailer for 
	// get_response_error()
	// get_debug_error()
	// is_mailer_complete()


	/**
	 * Sanitize the value, similar to `sanitize_text_field()`, but a bit differently.
	 * It preserves `<` and `>` for non-HTML tags.
	 *
	 * @since 0.1
	 *
	 * @param string $value String we want to sanitize.
	 *
	 * @return string
	 */
	public static function sanitize_value( $value ) {

		// Remove HTML tags.
		$filtered = wp_strip_all_tags( $value, false );
		// Remove multi-lines/tabs.
		$filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
		// Remove whitespaces.
		$filtered = trim( $filtered );

		// Remove octets.
		$found = false;
		while ( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) ) {
			$filtered = str_replace( $match[0], '', $filtered );
			$found    = true;
		}

		if ( $found ) {
			// Strip out the whitespace that may now exist after removing the octets.
			$filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
		}

		return $filtered;
	}

	/**
	 * Merge recursively, including a proper substitution of values in sub-arrays when keys are the same.
	 * It's more like array_merge() and array_merge_recursive() combined.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public static function array_merge_recursive() {

		$arrays = func_get_args();

		if ( count( $arrays ) < 2 ) {
			return isset( $arrays[0] ) ? $arrays[0] : array();
		}

		$merged = array();

		while ( $arrays ) {
			$array = array_shift( $arrays );

			if ( ! is_array( $array ) ) {
				return array();
			}

			if ( empty( $array ) ) {
				continue;
			}

			foreach ( $array as $key => $value ) {
				if ( is_string( $key ) ) {
					if (
						is_array( $value ) &&
						array_key_exists( $key, $merged ) &&
						is_array( $merged[ $key ] )
					) {
						$merged[ $key ] = call_user_func( __METHOD__, $merged[ $key ], $value );
					} else {
						$merged[ $key ] = $value;
					}
				} else {
					$merged[] = $value;
				}
			}
		}

		return $merged;
	}

	/**
	 * Check whether the string is a JSON or not.
	 *
	 * @since 0.1
	 *
	 * @param string $string String we want to test if it's json.
	 *
	 * @return bool
	 */
	public static function is_json( $string ) {

		return is_string( $string ) && is_array( json_decode( $string, true ) ) && ( json_last_error() === JSON_ERROR_NONE ) ? true : false;
	}	
}
