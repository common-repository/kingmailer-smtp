<?php

	/**
	 * Plugin Name:  Kingmailer SMTP
	 * Plugin URI:   https://wordpress.org/plugins/kingmailer-smtp/
	 * Description:  Buy SMTP with Bitcoin. Build for WooCommerce and WordPress. Try for free.
	 * Version:      0.3.14
	 * Requires at least: 3.3
	 * Author:       Kingmailer
	 * Author URI:   https://kingsmtp.com
	 * License:      GPLv2 or later
	 * Text Domain:  kingmailer-smtp
	 * Domain Path:  /languages/
	 */

	/*
	 * wordpress-smtp - Sending mail from Wordpress using Kingmailer
	 * Copyright (C) 2020 Krishna Moniz
	 * Copyright (C) 2016 Mailgun, et al.
	 * Copyright (C) 2007 WPForms
	 * 
	 * This is a test version of the software. The plugin includes code that is 
	 * commented out, because some parts need additional testing and will be roled out in future versions.
	 *
	 * This program is free software; you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation; either version 2 of the License, or
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

	/**
	 * Entrypoint for the Kingmailer plugin. Sets up the mailing "strategy" -
	 * either API or SMTP.
	 *
	 * Registers handlers for later actions and sets up config variables with
	 * Wordpress.
	 */
	class Kingmailer
	{
		/**
		 * Setup shared functionality for Admin and Front End.
		 *
		 * @since    0.1
		 */
		public function __construct()
		{
			$this->options = get_option('kingmailer-smtp');
			$this->plugin_file = __FILE__;
			$this->plugin_basename = plugin_basename($this->plugin_file);


			// Redefine PHPMailer.
			add_action( 'plugins_loaded', [ $this, 'replace_phpmailer' ] );
			add_action( 'phpmailer_init', [ $this, 'phpmailer_init' ]  );

		}

		/**
		 * Init the \PHPMailer replacement.
		 *
		 * @since 1.0.0
		 *
		 * @return MailCatcherInterface
		 */
		public function replace_phpmailer() {

			global $phpmailer;

			return $this->replace_w_fake_phpmailer( $phpmailer );
		}

		/**
		 * Overwrite default PhpMailer with our MailCatcher.
		 *
		 * @since 1.0.0
		 * @since 1.5.0 Throw external PhpMailer exceptions, inherits default WP behavior.
		 *
		 * @param null $obj PhpMailer object to override with own implementation.
		 *
		 * @return MailCatcherInterface
		 */
		protected function replace_w_fake_phpmailer( &$obj = null ) {

			$obj = $this->generate_mail_catcher( true );

			return $obj;
		}

		/**
		 * Generate the correct MailCatcher object based on the PHPMailer version used in WP.
		 *
		 * Also conditionally require the needed class files.
		 *
		 * @see   https://make.wordpress.org/core/2020/07/01/external-library-updates-in-wordpress-5-5-call-for-testing/
		 *
		 * @since 2.2.0
		 *
		 * @param bool $exceptions True if external exceptions should be thrown.
		 *
		 * @return MailCatcherInterface
		 */
		public function generate_mail_catcher( $exceptions = null ) {

			$mail_catcher = null;
			if ( version_compare( get_bloginfo( 'version' ), '5.5-alpha', '<' ) ) {
				if ( ! class_exists( '\PHPMailer', false ) ) {
					require_once ABSPATH . WPINC . '/class-phpmailer.php';
				}

				if ( ! class_exists( '\Kingmailer\MailCatcher', false ) ) {
					require_once dirname(__FILE__) . '/includes/MailCatcher.php';
				}

				$mail_catcher = new  \Kingmailer\MailCatcher( $exceptions );
			} else {
				if ( ! class_exists( '\PHPMailer\PHPMailer\PHPMailer', false ) ) {
					require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
				}

				if ( ! class_exists( '\PHPMailer\PHPMailer\Exception', false ) ) {
					require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
				}

				if ( ! class_exists( '\PHPMailer\PHPMailer\SMTP', false ) ) {
					require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
				}

				if ( ! class_exists( '\Kingmailer\MailCatcherV6', false ) ) {
					require_once dirname(__FILE__) . '/includes/MailCatcherV6.php';
				}

				$mail_catcher = new \Kingmailer\MailCatcherV6( $exceptions );
			}

			return $mail_catcher;
		}
				
		/**
		 * Get specific option from the options table.
		 *
		 * @param    string $option  Name of option to be used as array key for retrieving the specific value
		 * @param    array  $options Array to iterate over for specific values
		 * @param    bool   $default False if no options are set
		 *
		 * @return    mixed
		 *
		 * @since    0.1
		 */
		public function get_option($option, $options = null, $default = false)
		{
			if (is_null($options)):
				$options = &$this->options;
			endif;
			if (isset($options[ $option ])):
				return $options[ $option ];
			else:
				return $default;
			endif;
		}

		/**
		 * Hook into phpmailer to override SMTP based configurations
		 * to use the Kingmailer SMTP server.
		 *
		 * @param    object $phpmailer The PHPMailer object to modify by reference
		 *
		 * @return    void
		 *
		 * @since    0.1
		 */
		public function phpmailer_init($phpmailer)
		{
			$options = get_option('kingmailer-smtp');

			$from = $options['from-address'];
			$fromName = $options['from-name'];

			// Set the sender name and e-mail
			if(!empty($from) && (bool) $options['override-from']){
				$phpmailer->SetFrom($from, $fromName);
			}

			$host = (defined('KINGMAILER_HOST') && KINGMAILER_HOST) ? KINGMAILER_HOST : $options['host'];
			$secure = (defined('KINGMAILER_SECURE') && KINGMAILER_SECURE) ? KINGMAILER_SECURE : $options['secure'];
			$username = (defined('KINGMAILER_USERNAME') && KINGMAILER_USERNAME) ? KINGMAILER_USERNAME : $options['username'];
			$password = (defined('KINGMAILER_PASSWORD') && KINGMAILER_PASSWORD) ? KINGMAILER_PASSWORD : $options['password'];
			$smtp_port = $options['smtp_port'];
	
			if( ! (bool) $options['use_api'])
			{

				$phpmailer->isSMTP();     
				$phpmailer->Host = (bool) $host ? $host : 'kingmailer.org';
				$phpmailer->Port = $smtp_port ;
				$phpmailer->Username = $username;
				$phpmailer->Password = $password;

				// Authentication required for kingmailer (we ignore the secure flag and always set it to TLS)
				$phpmailer->SMTPAuth = true; // Ask it to use authenticate using the Username and Password properties
				$phpmailer->SMTPSecure = 'tls';
			}
		}

		/**
		 * Deactivate this plugin and die.
		 * Deactivate the plugin when files critical to it's operation cannot be loaded
		 *
		 * @param    $file    Files critical to plugin functionality
		 *
		 * @return    void
		 *
		 * @since    0.1
		 */
		public function deactivate_and_die($file)
		{
			load_plugin_textdomain('kingmailer-smtp', false, 'kingmailer/languages');
			$message = sprintf(__('Kingmailer has been automatically deactivated because the file <strong>%s</strong> is missing. Please reinstall the plugin and reactivate.'),
				$file);
			if (!function_exists('deactivate_plugins')):
				include ABSPATH . 'wp-admin/includes/plugin.php';
			endif;
			deactivate_plugins(__FILE__);
			wp_die($message);
		}

		/**
		 * Make a Kingmailer api call.
		 *
		 * @param    string $uri    The endpoint for the Kingmailer API
		 * @param    array  $params Array of parameters passed to the API
		 * @param    string $method The form request type
		 *
		 * @return    array
		 *
		 * @since    0.1
		 */
		public function api_call($uri, $params = array(), $method = 'POST')
		{

			$options = get_option('kingmailer-smtp');
			$api_key = (defined('KINGMAILER_APIKEY') && KINGMAILER_APIKEY) ? KINGMAILER_APIKEY : $options[ 'api_key' ];
			$domain = (defined('KINGMAILER_DOMAIN') && KINGMAILER_DOMAIN) ? KINGMAILER_DOMAIN : $options[ 'domain' ];

			$this->api_endpoint = 'https://kingmailer.org/api/v1/send/message';

			$time = time();
			$url = $this->api_endpoint . $uri;
			$headers = array(
				'X-Server-API-Key' => $api_key
			);

			switch ($method) {
				case 'GET':
					$params[ 'sess' ] = '';
					$querystring = http_build_query($params);
					$url = $url . '?' . $querystring;
					$params = '';
					break;
				case 'POST':
				case 'PUT':
				case 'DELETE':
					$params[ 'sess' ] = '';
					$params[ 'time' ] = $time;
					$params[ 'hash' ] = sha1(date('U'));
					break;
			}

			// make the request
			$args = array(
				'method' => $method,
				'body' => $params,
				'headers' => $headers,
				'sslverify' => true,
			);

			// make the remote request
			$result = wp_remote_request($url, $args);
			if (!is_wp_error($result)) {
				return $result[ 'body' ];
			} 



			return $result->get_error_message();
		}

	}


if ( ! defined( 'KINGMAILERCO_SMTP_PLUGIN_VER' ) ) {
	define( 'KINGMAILERCO_SMTP_PLUGIN_VER', '0.2' );
}

$kingmailer = new Kingmailer();


if (is_admin()){
	if (@include dirname(__FILE__) . '/includes/admin.php'){
		$kingmailerAdmin = new KingmailerAdmin();
	} else {
		Kingmailer::deactivate_and_die(dirname(__FILE__) . '/includes/admin.php');
	}
}


/**
 * 
 * Temporary function to for error checking
 */
function km_error_log($string, $source = "Unknown source")
{
	$object_output = "";

	if(is_object($string) || is_array($string)) {
		foreach($string as $key => $value) {
			$object_output .= "[" . $key . "] => " . $value . " | ";
		}
		error_log( "\n" . $source . ": " . $object_output , 3, "/var/www/html/wp-content/plugins/kingmailer-smtp/php_errors.log");
	} else {
		error_log( "\n" . $source . ": " . $string, 3, "/var/www/html/wp-content/plugins/kingmailer-smtp/php_errors.log");
	}

}

