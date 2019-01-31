<?php
/**
 * Plugin Settings.
 *
 * @package cyr-to-lat
 */

/**
 * Class Cyr_To_Lat_Settings
 *
 * @class Cyr_To_Lat_Settings
 */
class Cyr_To_Lat_Settings {

	/**
	 * Admin screen id.
	 *
	 * @var string
	 */
	const SCREEN_ID = 'toplevel_page_cyr-to-lat';

	/**
	 * Option group.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'cyr_to_lat_group';

	/**
	 * Option page.
	 *
	 * @var string
	 */
	const PAGE = 'cyr-to-lat';

	/**
	 * Plugin options name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'cyr_to_lat_settings';

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Form fields.
	 *
	 * @var array
	 */
	public $form_fields;

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Cyr_To_Lat_Settings constructor.
	 */
	public function __construct() {
		$this->init_form_fields();
		$this->init_settings();
		$this->init_hooks();
	}

	/**
	 * Init class hooks.
	 */
	public function init_hooks() {
		add_filter(
			'plugin_action_links_' . plugin_basename( CYR_TO_LAT_FILE ),
			array(
				$this,
				'add_settings_link',
			),
			10,
			4
		);

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'current_screen', array( $this, 'setup_sections' ) );
		add_action( 'current_screen', array( $this, 'setup_fields' ) );

		add_filter( 'pre_update_option_' . self::OPTION_NAME, array( $this, 'pre_update_option_filter' ), 10, 3 );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Add link to plugin setting page on plugins page.
	 *
	 * @param array  $actions     An array of plugin action links. By default this can include 'activate',
	 *                            'deactivate', and 'delete'. With Multisite active this can also include
	 *                            'network_active' and 'network_only' items.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param string $context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                            'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 *
	 * @return array|mixed Plugin links
	 */
	public function add_settings_link( $actions, $plugin_file, $plugin_data, $context ) {
		$ctl_actions = array(
			'settings' =>
				'<a href="' . admin_url( 'options-general.php?page=' . self::PAGE ) .
				'" aria-label="' . esc_attr__( 'View Cyr To Lat settings', 'cyr-to-lat' ) . '">' .
				esc_html__( 'Settings', 'cyr-to-lat' ) . '</a>',
		);

		return array_merge( $ctl_actions, $actions );
	}

	/**
	 * Init options form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'convert_existing_slugs' => array(
				'label'        => __( 'Convert Existing Slugs', 'cyr-to-lat' ),
				'section'      => 'general_section',
				'type'         => 'checkbox',
				'placeholder'  => '',
				'helper'       => '',
				'supplemental' => '',
			),
			'iso9'                   => array(
				'label'        => __( 'ISO9 Table', 'cyr-to-lat' ),
				'section'      => 'iso9_section',
				'type'         => 'table',
				'placeholder'  => '',
				'helper'       => '',
				'supplemental' => '',
				'default'      => Cyr_To_Lat_Conversion_Tables::get(),
			),
			'bg_BG'                  => array(
				'label'        => __( 'bg_BG Table', 'cyr-to-lat' ),
				'section'      => 'bg_BG_section',
				'type'         => 'table',
				'placeholder'  => '',
				'helper'       => '',
				'supplemental' => '',
				'default'      => Cyr_To_Lat_Conversion_Tables::get( 'bg_BG' ),
			),
			'uk'                     => array(
				'label'        => __( 'uk Table', 'cyr-to-lat' ),
				'section'      => 'uk_section',
				'type'         => 'table',
				'placeholder'  => '',
				'helper'       => '',
				'supplemental' => '',
				'default'      => Cyr_To_Lat_Conversion_Tables::get( 'uk' ),
			),
			'ka_GE'                  => array(
				'label'        => __( 'ka_GE Table', 'cyr-to-lat' ),
				'section'      => 'ka_GE_section',
				'type'         => 'table',
				'placeholder'  => '',
				'helper'       => '',
				'supplemental' => '',
				'default'      => Cyr_To_Lat_Conversion_Tables::get( 'ka_GE' ),
			),
		);
	}

	/**
	 * Initialise Settings.
	 *
	 * Store all settings in a single database entry
	 * and make sure the $settings array is either the default
	 * or the settings stored in the database.
	 */
	public function init_settings() {
		$this->settings = get_option( self::OPTION_NAME, null );

		// If there are no settings defined, use defaults.
		if ( ! is_array( $this->settings ) ) {
			$form_fields    = $this->get_form_fields();
			$this->settings = array_merge( array_fill_keys( array_keys( $form_fields ), '' ), wp_list_pluck( $form_fields, 'default' ) );
		}
	}

	/**
	 * Get the form fields after they are initialized.
	 *
	 * @return array of options
	 */
	public function get_form_fields() {
		if ( empty( $this->form_fields ) ) {
			$this->init_form_fields();
		}

		return array_map( array( $this, 'set_defaults' ), $this->form_fields );
	}

	/**
	 * Set default required properties for each field.
	 *
	 * @param array $field Settings field.
	 *
	 * @return array
	 */
	protected function set_defaults( $field ) {
		if ( ! isset( $field['default'] ) ) {
			$field['default'] = '';
		}

		return $field;
	}

	/**
	 * Add settings page to the menu.
	 */
	public function add_settings_page() {
		$page_title = __( 'Cyr To Lat', 'cyr-to-lat' );
		$menu_title = __( 'Cyr To Lat', 'cyr-to-lat' );
		$capability = 'manage_options';
		$slug       = 'cyr-to-lat';
		$callback   = array( $this, 'ctl_settings_page' );
		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" x="0" y="0" width="512" height="512" viewBox="0 0 512 512" enable-background="new 0 0 512 512" xml:space="preserve"><polygon fill="#EEEEEE" points="246.2 411.2 267.9 479.7 273.9 473.1 273.9 473.1 331 411.2 "/><path fill="#EEEEEE" d="M449.7 99.8H269.4l49.8 157.2 24.9-58.5h35.5l75 176.3h-40.2l-16-39.2h-54.3l23.1 72.8 -0.2 0h0.1l-71.7 77.9h154.3c23.4 0 42.4-19 42.4-42.4V142.3C492.1 118.8 473.2 99.8 449.7 99.8z"/><polygon fill="#EEEEEE" points="338.1 301.6 384.6 301.6 361.4 244.8 "/><path fill="#EEEEEE" d="M219.7 24.7H49c-16.6 0-30 13.5-30 30v301.6c0 16.6 13.5 30 30 30h285.3L219.7 24.7zM230.3 302.3c-30.5-8.3-59-21.9-82.9-39.4 -23.8 17.5-52.4 31.1-82.9 39.4l-3.4 0.9L52.7 273.2l3.5-0.9c24.7-6.6 47.2-16.8 67-30.3 -19.6-19.6-34.2-42.5-42.4-66.6H58v-27.2h75.9v-23.6h27.2v23.6h75.9v27.2h-22.7c-8.2 24.1-22.8 47-42.4 66.6 19.8 13.5 42.3 23.6 67 30.3l3.5 0.9 -8.5 30L230.3 302.3zM114 175.4c7.4 17 18.6 32.7 33.4 47 14.8-14.2 26-30 33.4-47H114z"/></svg>' );
		// phpcs:enable
		$position = null;
		add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon );
	}

	/**
	 * Settings page.
	 */
	public function ctl_settings_page() {
		if ( ! $this->is_ctl_options_screen() ) {
			return;
		}

		?>
		<div class="wrap">
			<h2 id="title">
				<?php
				// Admin panel title.
				echo( esc_html( __( 'Cyr To Lat Plugin Options', 'cyr-to-lat' ) ) );
				?>
			</h2>

			<form id="ctl-options" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="POST">
				<?php
				do_settings_sections( self::PAGE ); // Sections with options.
				settings_fields( self::OPTION_GROUP ); // Hidden protection fields.
				submit_button();
				?>
			</form>

			<div>
				<h2 id="donate">
					<?php echo esc_html( __( 'Donate', 'cyr-to-lat' ) ); ?>
				</h2>
				<p>
					<?php echo esc_html( __( 'Would you like to support the advancement of this plugin?', 'cyr-to-lat' ) ); ?>
				</p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="BENCPARA8S224">
					<input
							type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif"
							name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img
							alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1"
							height="1">
				</form>

				<h2 id="appreciation">
					<?php echo esc_html( __( 'Your appreciation', 'cyr-to-lat' ) ); ?>
				</h2>
				<a
						target="_blank"
						href="https://wordpress.org/support/view/plugin-reviews/cyr2lat?rate=5#postform">
					<?php echo esc_html( __( 'Leave a ★★★★★ plugin review on WordPress.org', 'cyr-to-lat' ) ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Setup settings sections.
	 */
	public function setup_sections() {
		add_settings_section(
			'general_section',
			__( 'General Options', 'cyr-to-lat' ),
			array( $this, 'cyr_to_lat_section' ),
			self::PAGE
		);
		add_settings_section(
			'iso9_section',
			__( 'ISO9 Table', 'cyr-to-lat' ),
			array( $this, 'cyr_to_lat_section' ),
			self::PAGE
		);
		add_settings_section(
			'bg_BG_section',
			__( 'bg_BG Table', 'cyr-to-lat' ),
			array( $this, 'cyr_to_lat_section' ),
			self::PAGE
		);
		add_settings_section(
			'uk_section',
			__( 'uk Table', 'cyr-to-lat' ),
			array( $this, 'cyr_to_lat_section' ),
			self::PAGE
		);
		add_settings_section(
			'ka_GE_section',
			__( 'ka_GE Table', 'cyr-to-lat' ),
			array( $this, 'cyr_to_lat_section' ),
			self::PAGE
		);
	}

	/**
	 * Section callback.
	 *
	 * @param array $arguments Section arguments.
	 */
	public function cyr_to_lat_section( $arguments ) {
	}


	/**
	 * Setup settings fields.
	 */
	public function setup_fields() {
		if ( ! $this->is_ctl_options_screen() ) {
			return;
		}

		register_setting( self::OPTION_GROUP, self::OPTION_NAME );

		// Get current settings.
		$this->options = get_option( self::OPTION_NAME );

		foreach ( $this->form_fields as $key => $field ) {
			$field['field_id'] = $key;

			add_settings_field(
				$key,
				$field['label'],
				array( $this, 'field_callback' ),
				self::PAGE,
				$field['section'],
				$field
			);
		}
	}

	/**
	 * Output settings field.
	 *
	 * @param array $arguments Field arguments.
	 */
	public function field_callback( $arguments ) {
		$value = $this->get_option( $arguments['field_id'] );

		// Check which type of field we want.
		switch ( $arguments['type'] ) {
			case 'text':
			case 'password':
			case 'number':
				printf(
					'<input name="%1$s[%2$s]" id="%2$s" type="%3$s" placeholder="%4$s" value="%5$s" class="regular-text" />',
					esc_html( self::OPTION_NAME ),
					esc_attr( $arguments['field_id'] ),
					esc_attr( $arguments['type'] ),
					esc_attr( $arguments['placeholder'] ),
					esc_html( $value )
				);
				break;
			case 'textarea':
				printf(
					'<textarea name="%1$s[%2$s]" id="%2$s" placeholder="%3$s" rows="5" cols="50">%4$s</textarea>',
					esc_html( self::OPTION_NAME ),
					esc_attr( $arguments['field_id'] ),
					esc_attr( $arguments['placeholder'] ),
					wp_kses_post( $value )
				);
				break;
			case 'checkbox':
			case 'radio':
				if ( 'checkbox' === $arguments['type'] ) {
					$arguments['options'] = array( 'yes' => '' );
				}

				if ( ! empty( $arguments['options'] ) && is_array( $arguments['options'] ) ) {
					$options_markup = '';
					$iterator       = 0;
					foreach ( $arguments['options'] as $key => $label ) {
						$iterator ++;
						$options_markup .= sprintf(
							'<label for="%2$s_%7$s"><input id="%2$s_%7$s" name="%1$s[%2$s]" type="%3$s" value="%4$s" %5$s /> %6$s</label><br/>',
							esc_html( self::OPTION_NAME ),
							$arguments['field_id'],
							$arguments['type'],
							$key,
							checked( $value, $key, false ),
							$label,
							$iterator
						);
					}
					printf(
						'<fieldset>%s</fieldset>',
						wp_kses(
							$options_markup,
							array(
								'label' => array(
									'for' => array(),
								),
								'input' => array(
									'id'      => array(),
									'name'    => array(),
									'type'    => array(),
									'value'   => array(),
									'checked' => array(),
								),
								'br'    => array(),
							)
						)
					);
				}
				break;
			case 'select': // If it is a select dropdown.
				if ( ! empty( $arguments['options'] ) && is_array( $arguments['options'] ) ) {
					$options_markup = '';
					foreach ( $arguments['options'] as $key => $label ) {
						$options_markup .= sprintf(
							'<option value="%s" %s>%s</option>',
							$key,
							selected( $value, $key, false ),
							$label
						);
					}
					printf(
						'<select name="%1$s[%2$s]">%3$s</select>',
						esc_html( self::OPTION_NAME ),
						esc_html( $arguments['field_id'] ),
						wp_kses(
							$options_markup,
							array(
								'option' => array(
									'value'    => array(),
									'selected' => array(),
								),
							)
						)
					);
				}
				break;
			case 'multiple': // If it is a multiple select dropdown.
				if ( ! empty( $arguments['options'] ) && is_array( $arguments['options'] ) ) {
					$options_markup = '';
					foreach ( $arguments['options'] as $key => $label ) {
						$selected = '';
						if ( is_array( $value ) ) {
							if ( in_array( $key, $value, true ) ) {
								$selected = selected( $key, $key, false );
							}
						}
						$options_markup .= sprintf(
							'<option value="%s" %s>%s</option>',
							$key,
							$selected,
							$label
						);
					}
					printf(
						'<select multiple="multiple" name="%1$s[%2$s][]">%3$s</select>',
						esc_html( self::OPTION_NAME ),
						esc_html( $arguments['field_id'] ),
						wp_kses(
							$options_markup,
							array(
								'option' => array(
									'value'    => array(),
									'selected' => array(),
								),
							)
						)
					);
				}
				break;
			case 'table':
				if ( is_array( $value ) ) {
					$iterator = 0;
					foreach ( $value as $key => $cell_value ) {
						$id = $arguments['field_id'] . '-' . $iterator;

						echo '<div class="ctl-table-cell">';
						printf(
							'<label for="%1$s">%2$s</label>',
							esc_html( $id ),
							esc_html( $key )
						);
						printf(
							'<input name="%1$s[%2$s][%3$s]" id="%4$s" type="%5$s" placeholder="%6$s" value="%7$s" class="regular-text" />',
							esc_html( self::OPTION_NAME ),
							esc_attr( $arguments['field_id'] ),
							esc_attr( $key ),
							esc_attr( $id ),
							'text',
							esc_attr( $arguments['placeholder'] ),
							esc_html( $cell_value )
						);
						echo '</div>';

						$iterator ++;
					}
				}
				break;
			default:
		}

		// If there is help text.
		$helper = $arguments['helper'];
		if ( $helper ) {
			printf( '<span class="helper"> %s</span>', esc_html( $helper ) );
		}

		// If there is supplemental text.
		$supplemental = $arguments['supplemental'];
		if ( $supplemental ) {
			printf( '<p class="description">%s</p>', esc_html( $supplemental ) );
		}
	}

	/**
	 * Get plugin option.
	 *
	 * @param string $key         Setting name.
	 * @param mixed  $empty_value Empty value for this setting.
	 *
	 * @return string The value specified for the option or a default value for the option.
	 */
	public function get_option( $key, $empty_value = null ) {
		if ( empty( $this->settings ) ) {
			$this->init_settings();
		}

		// Get option default if unset.
		if ( ! isset( $this->settings[ $key ] ) ) {
			$form_fields            = $this->get_form_fields();
			$this->settings[ $key ] = isset( $form_fields[ $key ] ) ? $this->get_field_default( $form_fields[ $key ] ) : '';
		}

		if ( ! is_null( $empty_value ) && '' === $this->settings[ $key ] ) {
			$this->settings[ $key ] = $empty_value;
		}

		return $this->settings[ $key ];
	}

	/**
	 * Get a field default value. Defaults to '' if not set.
	 *
	 * @param array $field Setting field default value.
	 *
	 * @return string
	 */
	protected function get_field_default( $field ) {
		return empty( $field['default'] ) ? '' : $field['default'];
	}

	/**
	 * Set plugin option.
	 *
	 * @param string $key   Setting name.
	 * @param mixed  $value Setting value.
	 */
	public function set_option( $key, $value ) {
		if ( empty( $this->settings ) ) {
			$this->init_settings();
		}

		$this->settings[ $key ] = $value;
		update_option( self::OPTION_NAME, $this->settings );
	}

	/**
	 * Filter plugin option update.
	 *
	 * @param mixed  $value     New option value.
	 * @param mixed  $old_value Old option value.
	 * @param string $option    Option name.
	 *
	 * @return mixed
	 */
	public function pre_update_option_filter( $value, $old_value, $option ) {
		if ( $value === $old_value ) {
			return $value;
		}

		$form_fields = $this->get_form_fields();
		foreach ( $form_fields as $key => $form_field ) {
			switch ( $form_field['type'] ) {
				case 'checkbox':
					$form_field_value = isset( $value[ $key ] ) ? $value[ $key ] : 'no';
					$form_field_value = '1' === $form_field_value || 'yes' === $form_field_value ? 'yes' : 'no';
					$value[ $key ]    = $form_field_value;
					break;
				default:
			}
		}

		return $value;
	}

	/**
	 * Enqueue class scripts.
	 */
	public function admin_enqueue_scripts() {
		if ( ! $this->is_ctl_options_screen() ) {
			return;
		}

		wp_enqueue_script(
			'cyr-to-lat-settings',
			CYR_TO_LAT_URL . '/dist/js/settings/app.js',
			array(),
			CYR_TO_LAT_VERSION,
			true
		);

		wp_enqueue_style(
			'cyr-to-lat-admin',
			CYR_TO_LAT_URL . '/css/cyr-to-lat-admin.css',
			array(),
			CYR_TO_LAT_VERSION
		);
	}

	/**
	 * Is current admin screen the plugin options screen.
	 *
	 * @return bool
	 */
	protected function is_ctl_options_screen() {
		$current_screen = get_current_screen();

		return $current_screen && ( 'options' === $current_screen->id || self::SCREEN_ID === $current_screen->id );
	}
}

// eof.
