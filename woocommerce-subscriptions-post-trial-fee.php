<?php
/**
 * Plugin Name:  WooCommerce Subscriptions Post Trial Fee
 * Plugin URI:   https://github.com/growdev/woocommerce-subscriptions-post-trial-fee
 * Description:  Add ability to charge fee after subscription free trial.
 * Version:      0.1
 * Author:       Grow Development
 * Author URI:   https://growdevelopment.com/
*/


add_action( 'init', 'gdcwc_init' );

/**
 * Place to add actions we care about.
 */
function gdcwc_init() {
	// Add post trial fee to products
	add_action( 'woocommerce_subscriptions_product_options_pricing', 'gdcwc_woocommerce_subscriptions_product_options_pricing', 10 );

	// Save post trial fee
	add_action( 'save_post', 'gdcwc_save_subscription_meta', 11 );
}

/**
 * Add Post Trial Fee field to Subscription Pricing
 *
 * @hook 'woocommerce_subscriptions_product_options_pricing'
 */
function gdcwc_woocommerce_subscriptions_product_options_pricing() {
	global $post;

	// Sign-up Fee
	woocommerce_wp_text_input(
		array(
			'id'                => '_subscription_post_trial_fee',
			// Keep wc_input_subscription_intial_price for backward compatibility.
			'class'             => 'wc_input_subscription_intial_price wc_input_subscription_initial_price wc_input_price  short',
			// translators: %s is a currency symbol / code
			'label'             => sprintf( __( 'Post trial fee (%s)', 'woocommerce-subscriptions' ), get_woocommerce_currency_symbol() ),
			'placeholder'       => _x( 'e.g. 9.90', 'example price', 'woocommerce-subscriptions' ),
			'description'       => __( 'Optionally include an amount to be charged after the subscription trial period.', 'woocommerce-subscriptions' ),
			'desc_tip'          => true,
			'type'              => 'text',
			'data_type'         => 'price',
			'custom_attributes' => array(
				'step' => 'any',
				'min'  => '0',
			),
		)
	);
}

/**
 * Save simple subscription schedule box meta
 *
 * @param $post_id
 */
function gdcwc_save_subscription_meta( $post_id ) {

	// TODO: add nonce
	//if ( empty( $_POST['_wcsnonce'] ) || ! wp_verify_nonce( $_POST['_wcsnonce'], 'wcs_subscription_meta' ) ) {
	//return;
	//}
	if ( isset( $_REQUEST['_subscription_post_trial_fee'] ) ) {
		update_post_meta( $post_id, '_subscription_post_trial_fee', $_REQUEST['_subscription_post_trial_fee'] );
	}
	//if ( isset( $_REQUEST['_variable_subscription_schedule_id'] ) ) {
	//	update_post_meta( $post_id, '_variable_subscription_schedule_id', $_REQUEST['_variable_subscription_schedule_id'] );
	//}
}

