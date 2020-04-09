<?php
/**
 * Plugin Name:       The Events Calendar Extension: Open external links in a new tab.
 * Plugin URI:        https://theeventscalendar.com/extensions/---the-extension-article-url---/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-external-links-in-new-tab
 * Description:       Sets some of the event links, such as venue, to open in a new tab.
 * Version:           1.0.0
 * Extension Class:   Tribe\Extensions\External_Links_In_New_Tab\Main
 * Author:            Modern Tribe, Inc.
 * Author URI:        http://m.tri.be/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tribe-ext-external-links-in-new-tab
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

namespace Tribe\Extensions\External_Links_In_New_Tab;

use Tribe__Autoloader;
use Tribe__Dependency;
use Tribe__Extension;

// Do not load unless Tribe Common is fully loaded and our class does not yet exist.
if (
	class_exists( 'Tribe__Extension' )
	&& ! class_exists( Main::class )
) {
	/**
	 * Extension main class, class begins loading on init() function.
	 */
	class Main extends Tribe__Extension {

		/**
		 * @var Tribe__Autoloader
		 * 
		 * @since 1.0.0
		 */
		private $class_loader;

		/**
		 * Is Events Calendar PRO active. If yes, we will add some extra functionality.
		 * 
		 * @since 1.0.0
		 *
		 * @return bool
		 */
		public $ecp_active = false;

		/**
		 * Setup the Extension's properties.
		 * 
		 * @since 1.0.0
		 *
		 * This always executes even if the required plugins are not present.
		 */
		public function construct() {
			// Dependency requirements and class properties can be defined here.

			// Conditionally-require Events Calendar PRO. If it is active, run an extra bit of code.
			add_action( 'tribe_plugins_loaded', [ $this, 'detect_tec_pro' ], 0 );
		}

		/**
		 * Check required plugins after all Tribe plugins have loaded.
		 * 
		 * @since 1.0.0
		 *
		 * Useful for conditionally-requiring a Tribe plugin, whether to add extra functionality
		 * or require a certain version but only if it is active.
		 */
		public function detect_tec_pro() {
			/** @var Tribe__Dependency $dep */
			$dep = tribe( Tribe__Dependency::class );

			if ( $dep->is_plugin_active( 'Tribe__Events__Pro__Main' ) ) {
				$this->add_required_plugin( 'Tribe__Events__Pro__Main' );
				$this->ecp_active = true;
			}
		}

		/**
		 * Get this plugin's options prefix.
		 * 
		 * @since 1.0.0
		 *
		 * Settings_Helper will append a trailing underscore before each option.
		 *
		 * @see \Tribe\Extensions\Settings::set_options_prefix()
		 *
		 * @return string
		 */
		private function get_options_prefix() {
			return (string) str_replace( '-', '_', 'tribe-ext-external-links-in-new-tab' );
		}

		/**
		 * Get Settings instance.
		 * 
		 * @since 1.0.0
		 *
		 * @return Settings
		 */
		private function get_settings() {
			if ( empty( $this->settings ) ) {
				$this->settings = new Settings( $this->get_options_prefix() );
			}

			return $this->settings;
		}

		/**
		 * Extension initialization and hooks.
		 * 
		 * @since 1.0.0
		 */
		public function init() {
			// Load plugin textdomain
			// Don't forget to generate the 'languages/tribe-ext-external-links-in-new-tab.pot' file
			load_plugin_textdomain( 'tribe-ext-external-links-in-new-tab', false, basename( dirname( __FILE__ ) ) . '/languages/' );


			if ( ! $this->php_version_check() ) {
				return;
			}

			$this->class_loader();

			$this->get_settings();

			// Insert filter and action hooks here
			if ( ! empty( $this->get_type_option( 'venue' ) ) ) {
				add_filter( 'tribe_get_venue_website_link_target', [ $this, 'return_blank_link_target' ] );
			}
			
			if ( ! empty( $this->get_type_option( 'website' ) ) ) {
				add_filter( 'tribe_get_event_website_link_target', [ $this, 'return_blank_link_target' ] );
			}

			if ( ! empty( $this->get_type_option( 'organizer' ) ) ) {
				add_filter( 'tribe_get_event_organizer_link_target', [ $this, 'return_blank_link_target' ] );
			}

			if ( ! empty( $this->get_type_option( 'content' ) ) ) {
				add_filter('the_content', [ $this, 'open_content_links_in_new_tab' ], 999);
			}
		}

		/**
		 * Check if we have a sufficient version of PHP. Admin notice if we don't and user should see it.
		 * 
		 * @since 1.0.0
		 *
		 * @link https://theeventscalendar.com/knowledgebase/php-version-requirement-changes/ All extensions require PHP 5.6+.
		 *
		 * Delete this paragraph and the non-applicable comments below.
		 * Make sure to match the readme.txt header.
		 *
		 * Note that older version syntax errors may still throw fatals even
		 * if you implement this PHP version checking so QA it at least once.
		 *
		 * @link https://secure.php.net/manual/en/migration56.new-features.php
		 * 5.6: Variadic Functions, Argument Unpacking, and Constant Expressions
		 *
		 * @link https://secure.php.net/manual/en/migration70.new-features.php
		 * 7.0: Return Types, Scalar Type Hints, Spaceship Operator, Constant Arrays Using define(), Anonymous Classes, intdiv(), and preg_replace_callback_array()
		 *
		 * @link https://secure.php.net/manual/en/migration71.new-features.php
		 * 7.1: Class Constant Visibility, Nullable Types, Multiple Exceptions per Catch Block, `iterable` Pseudo-Type, and Negative String Offsets
		 *
		 * @link https://secure.php.net/manual/en/migration72.new-features.php
		 * 7.2: `object` Parameter and Covariant Return Typing, Abstract Function Override, and Allow Trailing Comma for Grouped Namespaces
		 *
		 * @return bool
		 */
		private function php_version_check() {
			$php_required_version = '5.6';

			if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) {
				if (
					is_admin()
					&& current_user_can( 'activate_plugins' )
				) {
					$message = '<p>';

					$message .= sprintf( __( '%s requires PHP version %s or newer to work. Please contact your website host and inquire about updating PHP.', 'tribe-ext-external-links-in-new-tab' ), $this->get_name(), $php_required_version );

					$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );

					$message .= '</p>';

					tribe_notice( 'tribe-ext-external-links-in-new-tab' . '-php-version', $message, [ 'type' => 'error' ] );
				}

				return false;
			}

			return true;
		}

		/**
		 * Use Tribe Autoloader for all class files within this namespace in the 'src' directory.
		 * 
		 * @since 1.0.0
		 *
		 * @return Tribe__Autoloader
		 */
		public function class_loader() {
			if ( empty( $this->class_loader ) ) {
				$this->class_loader = new Tribe__Autoloader;
				$this->class_loader->set_dir_separator( '\\' );
				$this->class_loader->register_prefix(
					__NAMESPACE__ . '\\',
					__DIR__ . DIRECTORY_SEPARATOR . 'src'
				);
			}

			$this->class_loader->register_autoloader();

			return $this->class_loader;
		}

		/**
		 * Get all of this extension's options.
		 * 
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public function get_all_options() {
			$settings = $this->get_settings();

			return $settings->get_all_options();
		}

		/**
		 * Get a single link type option.
		 * 
		 * @since 1.0.0
		 *
		 * @return mixed
		 */
		public function get_type_option( $key = '' ) {
			if ( empty( $key ) ) {
				return false;
			}

			$settings = $this->get_settings();

			return $settings->get_type_option( $key );
		}

		/**
		 * Adds target="_blank" and rel="noopener noreferrer" to links in the content.
		 * Skips anchor links and ones that already have a target set.
		 * 
		 * @since 1.0.0
		 *
		 * @param string $content the post content.
		 * @return string the modified post content.
		 */
		function open_content_links_in_new_tab( $content ) {
			$pattern = '/<a(.*?)?href=[\'"]?[\'"]?(.*?)?>/i';
		
			$content = preg_replace_callback( 
				$pattern, 
				function( $matches ) {
					$tpl = array_shift( $matches ) ;
					$href = isset( $matches[1] ) ? $matches[1] : null;
			
					// Ignore anchor links.
					if ( trim( $href ) && 0 === strpos( $href, '#' ) ) {
						return $tpl; 
					}
			
					// Ignore links that already have a target set.
					if ( preg_match( '/target=[\'"]?(.*?)[\'"]?/i', $tpl ) ) {
						return $tpl;
					}
			
					return preg_replace_callback( 
						'/href=[\'"]+(.*?)[\'"]+/i', 
						function( $matches2 ) {
							return sprintf('%s target="_blank" rel="noopener noreferrer"', array_shift( $matches2 ) );
						}, 
						$tpl 
					);
				}, 
				$content 
			);
		
			return $content;
		}

		/**
		 * Returns '_blank' for a link target.
		 * 
		 * @since 1.0.0
		 *
		 * @param string $target the original target.
		 * @return string new target.
		 */
		public function return_blank_link_target( $target ) {
			return '_blank';
		}

	} // end class
} // end if class_exists check
