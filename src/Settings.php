<?php

namespace Tribe\Extensions\External_Links_In_New_Tab;

use Tribe__Settings_Manager;

if ( ! class_exists( Settings::class ) ) {
	/**
	 * Do the Settings.
	 */
	class Settings {

		/**
		 * The Settings Helper class.
		 * 
		 * @since 1.0.0
		 *
		 * @var Settings_Helper
		 */
		protected $settings_helper;

		/**
		 * The prefix for our settings keys.
		 * 
		 * @since 1.0.0
		 *
		 * @see get_options_prefix() Use this method to get this property's value.
		 *
		 * @var string
		 */
		private $options_prefix = 'tribe_ext_external_links_in_new_tab';

		/**
		 * Settings constructor.
		 * 
		 * @since 1.0.0
		 *
		 * @param string $options_prefix Recommended: the plugin text domain, with hyphens converted to underscores.
		 */
		public function __construct( $options_prefix ) {
			$this->settings_helper = new Settings_Helper();

			$this->set_options_prefix( $options_prefix );

			// Add settings specific to OSM
			add_action( 'admin_init', [ $this, 'add_settings' ] );
		}

		/**
		 * Allow access to set the Settings Helper property.
		 * 
		 * @since 1.0.0
		 *
		 * @see get_settings_helper()
		 *
		 * @param Settings_Helper $helper The settings helper (class).
		 *
		 * @return Settings_Helper
		 */
		public function set_settings_helper( Settings_Helper $helper ) {
			$this->settings_helper = $helper;

			return $this->get_settings_helper();
		}

		/**
		 * Allow access to get the Settings Helper property.
		 * 
		 * @since 1.0.0
		 *
		 * @see set_settings_helper()
		 */
		public function get_settings_helper() {
			return $this->settings_helper;
		}

		/**
		 * Set the options prefix to be used for this extension's settings.
		 * 
		 * @since 1.0.0
		 *
		 * @see get_options_prefix()
		 *
		 * @param string $options_prefix
		 */
		private function set_options_prefix( $options_prefix ) {
			$options_prefix = $options_prefix . '_';

			$this->options_prefix = str_replace( '__', '_', $options_prefix );
		}

		/**
		 * Get this extension's options prefix.
		 * 
		 * @since 1.0.0
		 *
		 * @see set_options_prefix()
		 *
		 * @return string
		 */
		public function get_options_prefix() {
			return $this->options_prefix;
		}

		/**
		 * Given an option key, get this extension's option value.
		 *
		 * This automatically prepends this extension's option prefix so you can just do `$this->get_option( 'a_setting' )`.
		 * 
		 * @since 1.0.0
		 *
		 * @see tribe_get_option()
		 *
		 * @param string $key     The key of the option you are looking for.
		 * @param string $default The default value if the option isn't found.
		 *
		 * @return mixed
		 */
		public function get_option( $key = '', $default = '' ) {
			$key = $this->sanitize_option_key( $key );

			return tribe_get_option( $key, $default );
		}

		/**
		 * Given a link-type, get this extension's option value for it.
		 *
		 * This automatically prepends this extension's option prefix so you can just do `$this->get_option( 'a_setting' )`.
		 * 
		 * @since 1.0.0
		 *
		 * @see tribe_get_option()
		 *
		 * @param string $option The "key" for the option you are looking for.
		 *
		 * @return boolean
		 */
		public function get_type_option( $option = '' ) {
			$key = $this->sanitize_option_key( 'link_target_settings' );

			$options = tribe_get_option( $key, [] );
			
			return in_array( $option, $options );
		}

		/**
		 * Get an option key after ensuring it is appropriately prefixed.
		 * 
		 * @since 1.0.0
		 *
		 * @param string $key The key for the option you are looking for.
		 *
		 * @return string
		 */
		private function sanitize_option_key( $key = '' ) {
			$prefix = $this->get_options_prefix();

			if ( 0 === strpos( $key, $prefix ) ) {
				$prefix = '';
			}

			return $prefix . $key;
		}

		/**
		 * Get an array of all of this extension's options without array keys having the redundant prefix.
		 * 
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public function get_all_options() {
			$raw_options = $this->get_all_raw_options();

			$result = [];

			$prefix = $this->get_options_prefix();

			foreach ( $raw_options as $key => $value ) {
				$abbr_key            = str_replace( $prefix, '', $key );
				$result[ $abbr_key ] = $value;
			}

			return $result;
		}

		/**
		 * Get an array of all of this extension's raw options (i.e. the ones starting with its prefix).
		 * 
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public function get_all_raw_options() {
			$tribe_options = Tribe__Settings_Manager::get_options();

			if ( ! is_array( $tribe_options ) ) {
				return [];
			}

			$result = [];

			foreach ( $tribe_options as $key => $value ) {
				if ( 0 === strpos( $key, $this->get_options_prefix() ) ) {
					$result[ $key ] = $value;
				}
			}

			return $result;
		}

		/**
		 * Given an option key, delete this extension's option value.
		 *
		 * This automatically prepends this extension's option prefix so you can just do `$this->delete_option( 'a_setting' )`.
		 * 
		 * @since 1.0.0
		 *
		 * @param string $key The key for the option you wish to delete.
		 *
		 * @return mixed
		 */
		public function delete_option( $key = '' ) {
			$key = $this->sanitize_option_key( $key );

			$options = Tribe__Settings_Manager::get_options();

			unset( $options[ $key ] );

			return Tribe__Settings_Manager::set_options( $options );
		}

		/**
		 * Adds a checkbox for each event link type to the Events > Settings > Display tab,
		 * as the last before the "Advanced Template Settings" section.
		 * 
		 * @since 1.0.0
		 */
		public function add_settings() {
			$all_options = $this->get_available_options();
			$fields = [
				'link_target_settings' => [
					'default'         => array_keys( $all_options ),
					'label'           => esc_html__( 'Link Target Control', 'tribe-ext-external-links-in-new-tab' ),
					'options'         => $all_options,
					'tooltip'         => esc_html__( 'Select which link types you want to open in a new tab. Note: Unchecking all the boxes will not save. If you want all areas unchecked, just deactivate this extension.', 'tribe-ext-external-links-in-new-tab' ),
					'type'            => 'checkbox_list',
					'validation_type' => 'options_multi',
				],
			];

			$this->settings_helper->add_fields(
				$this->prefix_settings_field_keys( $fields ),
				'display',
				'tribeEventsAdvancedSettingsTitle',
				false
			);
		}

		/**
		 * Add the options prefix to each of the array keys.
		 * 
		 * @since 1.0.0
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		private function prefix_settings_field_keys( array $fields ) {
			$prefixed_fields = array_combine(
				array_map(
					function ( $key ) {
						return $this->get_options_prefix() . $key;
					}, array_keys( $fields )
				),
				$fields
			);

			return (array) $prefixed_fields;
		}

		/**
		 * Build options to present to user.
		 * 
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public function get_available_options() {
			$options = [
				'website'   => 'Event website links',
				'venue'     => 'Event venue website links',
				'organizer' => 'Event organizer links',
				'content'   => 'Event content links',
			];

			return $options;
		}

	} // class
}
