<?php
/**
 * Plugin Name: KitMage FluentCRM Tab Manager
 * Plugin URI:  https://kitmage.com
 * Description: Adds signed-document links and account-management widgets to FluentCRM contact profiles.
 * Version:     0.1.0
 * Author:      Mike@KitMage
 * Author URI:  https://kitmage.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kitmage-fluentcrm-tab-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds KitMage's contact tools to FluentCRM.
 */
final class KitMage_FluentCRM_Tab_Manager {

	/**
	 * Register WordPress and FluentCRM hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'fluent_crm/after_init', array( __CLASS__, 'register_profile_section' ) );
		add_filter( 'fluent_crm/subscriber_top_widgets', array( __CLASS__, 'add_top_widgets' ), 10, 2 );
	}

	/**
	 * Register the signed-documents profile section.
	 *
	 * @return void
	 */
	public static function register_profile_section() {
		if ( ! function_exists( 'FluentCrmApi' ) ) {
			return;
		}

		FluentCrmApi( 'extender' )->addProfileSection(
			'aspen_signatures',
			__( 'Signatures', 'kitmage-fluentcrm-tab-manager' ),
			array( __CLASS__, 'render_signatures_section' ),
			3
		);
	}

	/**
	 * Build the contents of the signed-documents profile section.
	 *
	 * @param array  $content     Existing section content.
	 * @param object $subscriber FluentCRM subscriber.
	 * @return array
	 */
	public static function render_signatures_section( $content, $subscriber ) {
		$first_name = isset( $subscriber->first_name ) ? (string) $subscriber->first_name : '';
		$last_name  = isset( $subscriber->last_name ) ? (string) $subscriber->last_name : '';

		if ( '' === $first_name && '' === $last_name && ! empty( $subscriber->full_name ) ) {
			$name_parts = preg_split( '/\s+/', trim( (string) $subscriber->full_name ) );
			$first_name = isset( $name_parts[0] ) ? $name_parts[0] : '';
			$last_name  = count( $name_parts ) > 1 ? $name_parts[ count( $name_parts ) - 1 ] : '';
		}

		$search_name = trim( $first_name . ' ' . $last_name );
		$url         = add_query_arg(
			array(
				'esig_all_sender'      => 'All Sender',
				'document_status'      => 'signed',
				'page'                 => 'esign-docs',
				'esig_document_search' => $search_name,
				'esig_search'          => 'Search',
			),
			admin_url( 'admin.php' )
		);

		$content['heading']      = __( 'Signed Documents', 'kitmage-fluentcrm-tab-manager' );
		$content['content_html'] = sprintf(
			'<p><a class="button button-primary" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></p><p class="description">%3$s</p>',
			esc_url( $url ),
			esc_html(
				sprintf(
					/* translators: %s: contact name. */
					__( 'View signed docs for %s', 'kitmage-fluentcrm-tab-manager' ),
					$search_name ? $search_name : __( 'this contact', 'kitmage-fluentcrm-tab-manager' )
				)
			),
			esc_html__( 'Opens WP E-Sign → Docs (filtered to Signed, searched by the contact’s first and last name).', 'kitmage-fluentcrm-tab-manager' )
		);

		return $content;
	}

	/**
	 * Add wallet and user-switching widgets to a contact profile.
	 *
	 * @param array  $widgets    Existing top widgets.
	 * @param object $subscriber FluentCRM subscriber.
	 * @return array
	 */
	public static function add_top_widgets( $widgets, $subscriber ) {
		$wp_user_id = isset( $subscriber->user_id ) ? absint( $subscriber->user_id ) : 0;
		$email      = isset( $subscriber->email ) ? (string) $subscriber->email : '';

		$wallet_url = add_query_arg(
			array(
				'page'    => 'aspen-wallet-users',
				'user_id' => $wp_user_id,
			),
			admin_url( 'admin.php' )
		);

		$widgets[] = array(
			'title'   => '💰 ' . __( 'Wallet', 'kitmage-fluentcrm-tab-manager' ),
			'content' => sprintf(
				'<p><a href="%1$s">%2$s</a></p>',
				esc_url( $wallet_url ),
				esc_html(
					sprintf(
						/* translators: %s: contact email address. */
						__( 'Open the Aspen Credits wallet for %s', 'kitmage-fluentcrm-tab-manager' ),
						$email
					)
				)
			),
		);

		$user = $wp_user_id ? get_userdata( $wp_user_id ) : false;

		if ( $user && class_exists( 'user_switching' ) && current_user_can( 'switch_to_user', $wp_user_id ) ) {
			$switch_url = user_switching::switch_to_url( $user );

			$widgets[] = array(
				'title'   => '🥸 ' . sprintf(
					/* translators: %s: WordPress user's display name. */
					__( 'Login As %s', 'kitmage-fluentcrm-tab-manager' ),
					esc_html( $user->display_name )
				),
				'content' => sprintf(
					'<p><a href="%1$s">%2$s</a></p>',
					esc_url( $switch_url ),
					esc_html__( 'Click here to log in as this user and take actions on their behalf.', 'kitmage-fluentcrm-tab-manager' )
				),
			);
		}

		return $widgets;
	}
}

KitMage_FluentCRM_Tab_Manager::init();
