<?php
/**
 * Class file for Fieldmanager_Context_Customizer.
 *
 * @package Fieldmanager_Context
 */

/**
 * Add Fieldmanager fields to the Customizer.
 */
class Fieldmanager_Context_Customizer extends Fieldmanager_Context {
	/**
	 * @var array $args {
	 *     Arguments to construct Customizer objects.
	 *
	 *     @type array|bool $section_args Arguments for constructing a {@see WP_Customize_Section},
	 *           or false to not create one for this context.
	 *     @type array $setting_args Arguments for constructing a {@see Fieldmanager_Customize_Setting}.
	 *     @type array $control_args Arguments for constructing a {@see Fieldmanager_Customize_Control}.
	 * }
	 */
	protected $args;

	/**
	 * Constructor.
	 *
	 * @param array $args Customizer object arguments. @see Fieldmanager_Context_Customizer::args.
	 * @param Fieldmanager_Field $fm Field object to add to the Customizer.
	 */
	public function __construct( $args, $fm ) {
		$this->args = wp_parse_args( $args, array(
			'section_args' => false,
			'setting_args' => array(),
			'control_args' => array(),
		) );

		$this->fm = $fm;

		add_action( 'customize_register', array( $this, 'customize_register' ), 100 );
	}

	/**
	 * Fires once WordPress has loaded in the Customizer.
	 *
	 * @param WP_Customize_Manager $manager WP_Customize_Manager instance.
	 */
	public function customize_register( $manager ) {
		$this->register( $manager );
	}

	/**
	 * Exposes Fieldmanager_Context::render_field() for the control to call.
	 *
	 * @param array $args Unused.
	 */
	public function render_field( $args = array() ) {
		return parent::render_field( $args );
	}

	/**
	 * Filter a Customize setting value in un-slashed form.
	 *
	 * @param mixed $value Setting value.
	 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
	 * @return mixed The sanitized setting value.
	 */
	public function sanitize_callback( $value, $setting ) {
		if ( is_string( $value ) && 0 === strpos( $value, $this->fm->name ) ) {
			// Parse the query-string version of our values into an array.
			parse_str( $value, $value );
		}

		if ( is_array( $value ) && array_key_exists( $this->fm->name, $value ) ) {
			// If the option name is the top-level array key, get just the value.
			$value = $value[ $this->fm->name ];
		}

		// Return the value after Fieldmanager takes a shot at it.
		return stripslashes_deep( $this->prepare_data( $setting->value(), $value ) );
	}

	/**
	 * Create a Customizer section, setting, and control for this field.
	 *
	 * @param WP_Customize_Manager $manager WP_Customize_Manager instance.
	 */
	protected function register( $manager ) {
		$this->register_section( $manager );
		$this->register_setting( $manager );
		$this->register_control( $manager );
	}

	/**
	 * Add a Customizer section for this field.
	 *
	 * @param WP_Customize_Manager $manager
	 * @return WP_Customize_Section|void Section object, where supported, if created.
	 */
	protected function register_section( $manager ) {
		if ( false === $this->args['section_args'] ) {
			return;
		}

		return $manager->add_section( $this->fm->name, $this->args['section_args'] );
	}

	/**
	 * Add a Customizer setting for this field.
	 *
	 * By default, Fieldmanager registers one setting for a group and sends all
	 * of the group values from the Customizer, rather than individual settings
	 * for its children, so sanitization and validation routines can access the
	 * full group data.
	 *
	 * @param WP_Customize_Manager $manager
	 * @return Fieldmanager_Customize_Setting Setting object, where supported.
	 */
	protected function register_setting( $manager ) {
		return $manager->add_setting(
			new Fieldmanager_Customize_Setting( $manager, $this->fm->name, wp_parse_args(
				$this->args['setting_args'],
				array(
					'context' => $this,
				)
			) )
		);
	}

	/**
	 * Add a Customizer control for this field.
	 *
	 * @param WP_Customize_Manager $manager
	 * @return Fieldmanager_Customize_Control Control object, where supported.
	 */
	protected function register_control( $manager ) {
		return $manager->add_control(
			new Fieldmanager_Customize_Control( $manager, $this->fm->name, wp_parse_args(
				$this->args['control_args'],
				array(
					'section' => $this->fm->name,
					'context' => $this,
				)
			) )
		);
	}
}
