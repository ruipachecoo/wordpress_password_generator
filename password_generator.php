<?php
/*
Plugin Name: Password Generator
Description: A secure password generator.
Version: 1.1
Author: Rui Pacheco
Text Domain: password-generator
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Password_Generator_Plugin {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_shortcode( 'password_generator', array( $this, 'password_generator_shortcode' ) );
        add_action( 'wp_ajax_pg_generate_password', array( $this, 'handle_ajax_request' ) );
        add_action( 'wp_ajax_nopriv_pg_generate_password', array( $this, 'handle_ajax_request' ) );
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'pg-style', plugin_dir_url( __FILE__ ) . 'style.css', array(), '1.1' );

        wp_enqueue_script( 'pg-script', plugin_dir_url( __FILE__ ) . 'script.js', array( 'jquery' ), '1.1', true );

        wp_localize_script( 'pg-script', 'pg_ajax_object', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'pg_nonce' ),
        ) );
    }

    public function password_generator_shortcode() {
        ob_start();
        ?>
        <div id="password-generator">
            <h2><?php esc_html_e( 'Password Generator', 'password-generator' ); ?></h2>
            <label for="pg-length"><?php esc_html_e( 'Length:', 'password-generator' ); ?></label>
            <input type="number" id="pg-length" name="length" min="8" max="128" value="12" aria-label="<?php esc_attr_e( 'Password Length', 'password-generator' ); ?>">

            <label>
                <input type="checkbox" id="pg-uppercase" checked aria-label="<?php esc_attr_e( 'Include Uppercase Letters', 'password-generator' ); ?>">
                <?php esc_html_e( 'Include Uppercase Letters', 'password-generator' ); ?>
            </label>
            <label>
                <input type="checkbox" id="pg-lowercase" checked aria-label="<?php esc_attr_e( 'Include Lowercase Letters', 'password-generator' ); ?>">
                <?php esc_html_e( 'Include Lowercase Letters', 'password-generator' ); ?>
            </label>
            <label>
                <input type="checkbox" id="pg-numbers" checked aria-label="<?php esc_attr_e( 'Include Numbers', 'password-generator' ); ?>">
                <?php esc_html_e( 'Include Numbers', 'password-generator' ); ?>
            </label>
            <label>
                <input type="checkbox" id="pg-symbols" checked aria-label="<?php esc_attr_e( 'Include Symbols', 'password-generator' ); ?>">
                <?php esc_html_e( 'Include Symbols', 'password-generator' ); ?>
            </label>

            <div id="pg-loading-spinner" style="display:none;">
                <p><?php esc_html_e( 'Generating password...', 'password-generator' ); ?></p>
            </div>

            <div class="pg-button-group">
                <button id="pg-generate"><?php esc_html_e( 'Generate Password', 'password-generator' ); ?></button>
                <button id="pg-copy" disabled><?php esc_html_e( 'Copy To Clipboard', 'password-generator' ); ?></button>
            </div>

            <input type="text" id="pg-password" readonly aria-label="<?php esc_attr_e( 'Generated Password', 'password-generator' ); ?>">
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_ajax_request() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'pg_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'password-generator' ) ) );
            wp_die();
        }

        $length    = isset( $_POST['length'] ) ? intval( $_POST['length'] ) : 12;
        $uppercase = isset( $_POST['uppercase'] ) ? filter_var( $_POST['uppercase'], FILTER_VALIDATE_BOOLEAN ) : true;
        $lowercase = isset( $_POST['lowercase'] ) ? filter_var( $_POST['lowercase'], FILTER_VALIDATE_BOOLEAN ) : true;
        $numbers   = isset( $_POST['numbers'] ) ? filter_var( $_POST['numbers'], FILTER_VALIDATE_BOOLEAN ) : true;
        $symbols   = isset( $_POST['symbols'] ) ? filter_var( $_POST['symbols'], FILTER_VALIDATE_BOOLEAN ) : true;

        if ( $length < 8 || $length > 128 ) {
            wp_send_json_error( array( 'message' => __( 'Password length must be between 8 and 128.', 'password-generator' ) ) );
            wp_die();
        }

        if ( ! $uppercase && ! $lowercase && ! $numbers && ! $symbols ) {
            wp_send_json_error( array( 'message' => __( 'Please select at least one character type.', 'password-generator' ) ) );
            wp_die();
        }

        $password = $this->generate_password( $length, $uppercase, $lowercase, $numbers, $symbols );

        if ( empty( $password ) ) {
            wp_send_json_error( array( 'message' => __( 'Failed to generate password.', 'password-generator' ) ) );
            wp_die();
        }

        wp_send_json_success( array( 'password' => $password ) );
        wp_die();
    }

    private function generate_password( $length, $uppercase, $lowercase, $numbers, $symbols ) {
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $nums  = '0123456789';
        $syms  = '!@#$%^&*()-_=+[]{}|;:,.<>?';

        $characters = '';
        if ( $uppercase ) {
            $characters .= $upper;
        }
        if ( $lowercase ) {
            $characters .= $lower;
        }
        if ( $numbers ) {
            $characters .= $nums;
        }
        if ( $symbols ) {
            $characters .= $syms;
        }

        if ( empty( $characters ) ) {
            return '';
        }

        $password = '';
        $maxIndex = strlen( $characters ) - 1;

        for ( $i = 0; $i < $length; $i++ ) {
            try {
                $password .= $characters[ random_int( 0, $maxIndex ) ];
            } catch ( Exception $e ) {
                error_log( 'Password Generator Error: ' . $e->getMessage() );
                return '';
            }
        }

        return $password;
    }
}

new Password_Generator_Plugin();
