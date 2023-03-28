<?php

namespace Classifai\Admin;

use Classifai\Plugin;
use Classifai\Services\ServicesManager;

class Onboarding {
	/**
	 * Register the actions needed.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_setup_page' ] );
		add_action( 'admin_init', [ $this, 'handle_step_one_submission' ] );
		add_action( 'admin_init', [ $this, 'handle_step_two_submission' ] );
		add_action( 'admin_init', [ $this, 'handle_step_three_submission' ] );
	}

	/**
	 * Registers a hidden sub menu page for the onboarding wizard.
	 */
	public function register_setup_page() {
		add_submenu_page(
			null,
			esc_attr__( 'ClassifAI Setup', 'classifai' ),
			'',
			'manage_options',
			'classifai_setup',
			[ $this, 'render_setup_page' ]
		);
	}

	/**
	 * Renders the ClassifAI setup page.
	 */
	public function render_setup_page() {
		$current_step     = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$onboarding_steps = array(
			'1' => array(
				'step'  => __( '1', 'classifai' ),
				'title' => __( 'Enable Features', 'classifai' ),
			),
			'2' => array(
				'step'  => __( '2', 'classifai' ),
				'title' => __( 'Register ClassifAI', 'classifai' ),
			),
			'3' => array(
				'step'  => __( '3', 'classifai' ),
				'title' => __( 'Access AI', 'classifai' ),
			),
		);
		?>
		<div class="classifai-content classifai-setup-page">
			<?php
			include_once 'templates/classifai-header.php';
			?>
			<div class="classifai-setup">
				<div class="classifai-setup__header">
					<div class="classifai-setup__step-wrapper">
						<div class="classifai-setup__steps">
							<?php
							foreach ( $onboarding_steps as $key => $step ) {
								?>
								<div class="classifai-setup__step <?php echo ( $current_step === (string) $key ) ? 'is-active' : ''; ?>">
									<div class="classifai-setup__step__label">
										<span class="step-count"><?php echo esc_html( $step['step'] ); ?></span>
										<span class="step-title">
											<?php echo esc_html( $step['title'] ); ?>
										</span>
									</div>
								</div>
								<?php
								if ( array_key_last( $onboarding_steps ) !== $key ) {
									?>
									<div class="classifai-setup__step-divider"></div>
									<?php
								}
							}
							?>
						</div>
					</div>
				</div>
				<div class="wrap classifai-setup__wrapper">
					<div class="classifai-setup__content">
						<h1 class="classifai-setup-heading">
							<?php esc_html_e( 'Welcome to ClassifAI', 'classifai' ); ?>
						</h1>
						<?php
						// Load the appropriate step.
						switch ( $current_step ) {
							case '1':
								require_once 'templates/onboarding-step-one.php';
								break;

							case '2':
								require_once 'templates/onboarding-step-two.php';
								break;

							case '3':
								require_once 'templates/onboarding-step-three.php';
								break;

							default:
								break;
						}
						?>
					</div>
				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * Handle the submission of the first step of the onboarding wizard.
	 *
	 * @return void
	 */
	public function handle_step_one_submission() {
		if ( ! isset( $_POST['classifai-setup-step-one-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai-setup-step-one-nonce'] ) ), 'classifai-setup-step-one-action' ) ) {
			return;
		}

		$enabled_features = isset( $_POST['classifai-features'] ) ? $this->classifai_sanitize( $_POST['classifai-features'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$onboarding_options = get_option( 'classifai_onboarding_options', array() );

		$onboarding_options['enabled_features'] = $enabled_features;

		// Save the options to use it later steps.
		update_option( 'classifai_onboarding_options', $onboarding_options );

		// Redirect to next setup step.
		wp_safe_redirect( admin_url( 'admin.php?page=classifai_setup&step=2' ) );
		exit();
	}

	/**
	 * Handle the submission of the Register ClassifAI step of the onboarding wizard.
	 *
	 * @return void
	 */
	public function handle_step_two_submission() {
		if ( ! isset( $_POST['classifai-setup-step-two-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai-setup-step-two-nonce'] ) ), 'classifai-setup-step-two-action' ) ) {
			return;
		}

		$classifai_settings = isset( $_POST['classifai_settings'] ) ? $this->classifai_sanitize( $_POST['classifai_settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Save the ClassifAI settings.
		update_option( 'classifai_settings', $classifai_settings );

		$setting_errors = get_settings_errors( 'registration' );
		if ( ! empty( $setting_errors ) ) {
			// Stay on same setup step and display error.
			return;
		}

		// Redirect to next setup step.
		wp_safe_redirect( admin_url( 'admin.php?page=classifai_setup&step=3' ) );
		exit();
	}

	/**
	 * Handle the submission of ClassifAI set up AI services.
	 *
	 * @return void
	 */
	public function handle_step_three_submission() {
		if ( ! isset( $_POST['classifai-setup-step-three-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['classifai-setup-step-three-nonce'] ) ), 'classifai-setup-step-three-action' ) ) {
			return;
		}

		// Bail if no provider
		if ( empty( $_POST['classifai-setup-provider'] ) ) {
			return;
		}

		$provider_option = sanitize_text_field( wp_unslash( $_POST['classifai-setup-provider'] ) );
		$option_name     = 'classifai_' . $provider_option;

		$form_data = isset( $_POST[ $option_name ] ) ? $this->classifai_sanitize( $_POST[ $option_name ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$settings = $this->get_provider_settings( $provider_option );

		// Update settings.
		switch ( $provider_option ) {
			case 'watson_nlu':
				$settings['credentials'] = $form_data['credentials'];
				break;

			default:
				break;
		}

		// Save the ClassifAI settings.
		update_option( $option_name, $settings );

		$setting_errors = get_settings_errors();
		if ( ! empty( $setting_errors ) ) {
			// Stay on same setup step and display error.
			return;
		}

		// Redirect to next setup step. TODO: Manage move to next provider here.
		wp_safe_redirect( admin_url( 'admin.php?page=classifai_setup&step=3' ) );
		exit();
	}

	/**
	 * Sanitize variables using sanitize_text_field and wp_unslash. Arrays are cleaned recursively.
	 * Non-scalar values are ignored.
	 *
	 * @param string|array $var Data to sanitize.
	 * @return string|array
	 */
	public function classifai_sanitize( $var ) {
		if ( is_array( $var ) ) {
			return array_map( array( $this, 'classifai_sanitize' ), $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( wp_unslash( $var ) ) : $var;
		}
	}

	/**
	 * Render classifai setup settings with the given fields.
	 *
	 * @param string   $setting_name The name of the setting.
	 * @param string[] $fields       The fields to render.
	 * @return void
	 */
	public static function render_classifai_setup_settings( $setting_name, $fields ) {
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_fields[ $setting_name ][ $setting_name ] ) ) {
			return;
		}

		// Render the section.
		if ( isset( $wp_settings_sections[ $setting_name ] ) && ! empty( $wp_settings_sections[ $setting_name ][ $setting_name ] ) ) {
			$section = $wp_settings_sections[ $setting_name ][ $setting_name ];
			if ( '' !== $section['before_section'] ) {
				if ( '' !== $section['section_class'] ) {
					echo wp_kses_post( sprintf( $section['before_section'], esc_attr( $section['section_class'] ) ) );
				} else {
					echo wp_kses_post( $section['before_section'] );
				}
			}

			if ( $section['title'] ) {
				?>
				<h2><?php echo esc_html( $section['title'] ); ?></h2>
				<?php
			}

			if ( $section['callback'] ) {
				call_user_func( $section['callback'], $section );
			}
		}

		// Render the fields.
		$setting_fields = $wp_settings_fields[ $setting_name ][ $setting_name ];
		foreach ( $fields as $field_name ) {
			if ( ! isset( $setting_fields[ $field_name ] ) ) {
				continue;
			}

			$field = $setting_fields[ $field_name ];
			if ( ! isset( $field['callback'] ) || ! is_callable( $field['callback'] ) ) {
				continue;
			}

			if ( 'toggle' === $field_name ) {
				call_user_func( $field['callback'], $field['args'] );
				continue;
			}
			?>
			<div class="classifai-setup-form-field">
				<label for="<?php echo esc_attr( $field['args']['label_for'] ); ?>">
					<?php echo esc_html( $field['title'] ); ?>
				</label>
				<?php
				call_user_func( $field['callback'], $field['args'] );
				?>
			</div>
			<?php
		}
	}

	/**
	 * Get list of providers enabled for setup.
	 *
	 * @return array Array of providers.
	 */
	public static function get_setup_providers() {
		return array(
			'watson_nlu'      => array(
				'title'    => __( 'IBM Watson NLU', 'classifai' ),
				'fields'   => array( 'url', 'username', 'password', 'toggle' ),
				'service'  => 'language_processing',
				'provider' => 'Natural Language Understanding',
			),
			'openai_chatgpt'  => array(
				'title'    => __( 'OpenAI ChatGPT', 'classifai' ),
				'fields'   => array( 'api_key' ),
				'service'  => 'language_processing',
				'provider' => 'ChatGPT',
			),
			'computer_vision' => array(
				'title'    => __( 'Microsoft Azure Computer Vision', 'classifai' ),
				'fields'   => array(),
				'service'  => 'image_processing',
				'provider' => 'Computer Vision',
			),
			'personalizer' => array(
				'title'    => __( 'Microsoft Azure Personalizer', 'classifai' ),
				'fields'   => array(),
				'service'  => 'personalizer',
				'provider' => 'Personalizer',
			),
		);
	}

	/**
	 * Returns the ClassifAI plugin's stored settings by provider option name.
	 *
	 * @param string $option_name The provider option name to get settings from.
	 *
	 * @return array The array of ClassifAi settings.
	 */
	public function get_provider_settings( $option_name ) {
		$services = Plugin::$instance->services;
		if ( empty( $services ) || empty( $services['service_manager'] ) || ! $services['service_manager'] instanceof ServicesManager ) {
			return [];
		}

		/** @var ServicesManager $service_manager Instance of the services manager class. */
		$service_manager = $services['service_manager'];

		foreach ( $service_manager->service_classes as $service ) {
			foreach ( $service->provider_classes as $provider_class ) {
				if ( $provider_class->option_name === $option_name ) {
					return $provider_class->get_settings();
				}
			}
		}

		return [];
	}
}
