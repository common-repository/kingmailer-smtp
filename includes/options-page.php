<?php

	/*
	 * kingmailer-wordpress-plugin - Sending mail from Wordpress using Kingmailer
	 * Copyright (C) 2020 Krishna Moniz
	 * Copyright (C) 2016 Mailgun, et al.
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

?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br/></div>

	<h2><?php _e('Kingmailer', 'kingmailer-smtp'); ?></h2>

	<p>
		<?php
			$url = 'https://kingsmtp.com/';
			$link = sprintf(
				wp_kses(
					__('A <a href="%1$s" target="%2$s">Kingmailer</a> account is required to use this plugin and the Kingmailer service.', 'kingmailer-smtp'),
					array('a' => array(
							'href' => array(),
							'target' => array()
						)
					)
				), esc_url($url), '_blank'
			);
			echo $link;
		?>
	</p>

	<h3><?php _e('Configuration', 'kingmailer-smtp'); ?></h3>
	<form id="kingmailer-form" action="options.php" method="post">
		<?php settings_fields('kingmailer-smtp'); ?>

		<table class="form-table">
		<tr valign="top">
				<th scope="row">
					<?php _e('Your Domain', 'kingmailer-smtp'); ?>
				</th>
				<td>
					<input type="text" class="regular-text"
						   name="kingmailer-smtp[domain]"
						   value="<?php esc_attr_e($this->get_option('domain')); ?>"
						   placeholder="example.com"
					/>
					<p class="description">
						<?php
							$link = sprintf(
								wp_kses(
									__('The domain you have added in your Kingmailer-account. Visit <a href="%1$s" target="%2$s">kingsmtp.com</a> to sign up for an account.', 'kingmailer-smtp'),
									array('a' => array(
											'href' => array(),
											'target' => array()
										)
									)
								), esc_url($url), '_blank'
							);
							echo $link;
						?>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('From Name', 'kingmailer-smtp'); ?>
				</th>
				<td>
					<input type="text" class="regular-text"
						   name="kingmailer-smtp[from-name]"
						   value="<?php esc_attr_e($this->get_option('from-name')); ?>"
						   placeholder="Excited User"
					/>
					<p class="description">
						<?php
							_e('The "User Name" part of the sender information. This can be your domain name.', 'kingmailer-smtp');
						?>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('From Address', 'kingmailer-smtp'); ?>
				</th>
				<td>
					<input type="text"
						   class="regular-text"
						   name="kingmailer-smtp[from-address]"
						   value="<?php esc_attr_e($this->get_option('from-address')); ?>"
						   placeholder="info@example.com"
					/>
					<p class="description">
						<?php
							_e('The &lt;info@example.com&gt; part of the sender information. This address will appear as the `From` address on sent mail. <strong>It is recommended that the @mydomain portion matches your Kingmailer sending domain.</strong>', 'kingmailer-smtp');
						?>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Override "From" Details', 'kingmailer-smtp'); ?>
				</th>
				<td>
					<select name="kingmailer-smtp[override-from]">
						<option value="1"<?php selected('1', $this->get_option('override-from', null, '1')); ?>><?php _e('Yes', 'kingmailer-smtp'); ?></option>
						<option value="0"<?php selected('0', $this->get_option('override-from', null, '0')); ?>><?php _e('No', 'kingmailer-smtp'); ?></option>
					</select>
					<p class="description">
						<?php
							_e('If enabled, all emails will be sent with the above <code>From Name</code> and <code>From Address</code>, regardless of values set by other plugins. Useful for cases where other plugins don\'t play nice with our settings. Defaults to "Yes".', 'kingmailer-smtp');
						?>
					</p>
				</td>
			</tr>
			<!-- IMPORTANT remove this hidden field when reactivating SMTP -->
			<input type="hidden" id="kingmailer-api" name="kingmailer-smtp[use_api]" value="1">
			<!-- <tr valign="top">
				<th scope="row">
					<?php _e('Use HTTP API', 'kingmailer-smtp'); ?>
				</th>
				<td>
					<select id="kingmailer-api" name="kingmailer-smtp[use_api]">
						<option value="1"<?php selected('1', $this->get_option('use_api')); ?>><?php _e('Yes', 'kingmailer-smtp'); ?></option>
						<option value="0"<?php selected('0', $this->get_option('use_api')); ?>><?php _e('No', 'kingmailer-smtp'); ?></option>
					</select>
					<p class="description">
						<?php
							_e('Set this to "No" if your server cannot make outbound HTTP connections or if emails are not being delivered. "No" will cause this plugin to use SMTP. Defaults to "Yes".', 'kingmailer-smtp');
						?>
					</p>
				</td>
			</tr> -->
			<tr valign="top" class="kingmailer-api">
				<th scope="row">
					<?php _e('API-host', 'kingmailer-smtp'); ?>
				</th>
				<td>
					<input type="text" class="regular-text"
						   name="kingmailer-smtp[api_host]"
						   value="<?php esc_attr_e($this->get_option('api_host')); ?>"
						   placeholder="kingmailer.org"
						   readonly
					/>
					<p class="description">
						<?php _e('Your Kingmailer API-host. Fixed at "kingmailer.org"', 'kingmailer-smtp'); ?>
					</p>
				</td>
			</tr>
			<tr valign="top" class="kingmailer-api">
				<th scope="row">
					<?php _e('API Key', 'kingmailer-smtp'); ?>
				</th>
				<td>
					<input type="text" class="regular-text" name="kingmailer-smtp[api_key]"
						   value="<?php esc_attr_e($this->get_option('api_key')); ?>"
						   placeholder="ur7keAGRjYB3W5NfKQEm563Z"
					/>
					<p class="description">
						<?php _e('Your Kingmailer API key. Create an API by visiting the credentials section in your Kingmailer-account.', 'kingmailer-smtp'); ?>
					</p>
				</td>
			</tr>
			<tr valign="top" class="kingmailer-smtp">
				<th scope="row">
					<?php _e('SMTP-host', 'kingmailer-smtp'); ?>
				</th>
				<td>
					<input type="text" class="regular-text"
						   name="kingmailer-smtp[host]"
						   value="<?php esc_attr_e($this->get_option('host')); ?>"
						   placeholder="kingmailer.org"
						   readonly
					/>
					<p class="description">
						<?php _e('Your Kingmailer SMTP-host. Fixed at "kingmailer.org"', 'kingmailer-smtp'); ?>
						<br />
						<?php _e('Only valid for use with SMTP.', 'kingmailer-smtp'); ?>
					</p>
				</td>
			</tr>
			<!-- <tr valign="top" class="kingmailer-smtp">
				<th scope="row">
					<?php _e('Username', 'kingmailer-smtp'); ?>
				</th>
				<td>
					<input type="text" class="regular-text"
						   name="kingmailer-smtp[username]"
						   value="<?php esc_attr_e($this->get_option('username')); ?>"
						   placeholder="postmaster"
					/>
					<p class="description">
						<?php _e('Your Kingmailer SMTP username.', 'kingmailer-smtp'); ?>
						<br />
						<?php _e('Only valid for use with SMTP.', 'kingmailer-smtp'); ?>
					</p>
				</td>
			</tr>
			<tr valign="top" class="kingmailer-smtp">
				<th scope="row">
					<?php _e('Password', 'kingmailer-smtp'); ?>
				</th>
				<td>
					<input type="text" class="regular-text"
						   name="kingmailer-smtp[password]"
						   value="<?php esc_attr_e($this->get_option('password')); ?>"
						   placeholder="my-password"
					/>
					<p class="description">
						<?php _e('Your Kingmailer SMTP password that goes with the above username.', 'kingmailer-smtp'); ?>
						<br />
						<?php _e('Only valid for use with SMTP.', 'kingmailer-smtp'); ?>
					</p>
				</td>
			</tr>
			<tr valign="top" class="kingmailer-smtp">
				<th scope="row">
					<?php _e('Port number', 'kingmailer-smtp'); ?>
				</th>
				<td>
					<input type="text" class="regular-text"
						   name="kingmailer-smtp[smtp_port]"
						   value="<?php esc_attr_e($this->get_option('smtp_port')); ?>"
						   placeholder="587"
					/>
					<p class="description">
						<?php _e('Specify which port to use for SMTP mailing. Kingmailer only allows for standard SMTP ports (25, 465, and 587). Defaults to "587"', 'kingmailer-smtp'); ?>
						<br />
						<?php _e('Only valid for use with SMTP.', 'kingmailer-smtp'); ?>
					</p>
				</td>
			</tr> -->
			<!-- <tr valign="top" class="kingmailer-smtp">
				<th scope="row">
					<?php // _e('Use Secure SMTP', 'kingmailer-smtp'); ?>
				</th>
				<td>
					<select name="kingmailer-smtp[secure]">
						<option value="1"<?php // selected('1', $this->get_option('secure')); ?>><?php // _e('Yes', 'kingmailer-smtp'); ?></option>
						<option value="0"<?php // selected('0', $this->get_option('secure')); ?>><?php // _e('No', 'kingmailer-smtp'); ?></option>
					</select>
					<p class="description">
						<?php // _e('Set this to "Yes" to send your mails over a secure SSL/TLS connection. If you set this to "No" your password will be sent in plain text. Default is "Yes".', 'kingmailer-smtp'); ?>
						<br />
						<?php // _e('Only valid for use with SMTP.', 'kingmailer-smtp'); ?>
					</p>
				</td>
			</tr> -->
		</table>
		<p>
			<?php
				_e('Please click <code>Save Changes</code> before attempting to test the configuration.', 'kingmailer-smtp');
			?>
		</p>
		<p class="submit">
			<input type="submit"
				   class="button-primary"
				   value="<?php _e('Save Changes', 'kingmailer-smtp'); ?>"
			/>
			<input type="button"
				   id="kingmailer-test"
				   class="button-secondary"
				   value="<?php _e('Send Test Mail', 'kingmailer-smtp'); ?>"
			/>
		</p>
	</form>
</div>
