<?php
/**
 * Plugin Name:  WooCommerce Subscriptions Post Trial Fee
 * Plugin URI:   https://github.com/growdev/woocommerce-subscriptions-post-trial-fee
 * Description:  Add ability to charge fee after subscription free trial.
 * Version:      0.1
 * Author:       Grow Development
 * Author URI:   https://growdevelopment.com/
 * Textdomain:   'wc-subs-post-trial-fee'
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

	// Hook into order creation and maybe add post trial fee meta
	add_action( 'woocommerce_checkout_subscription_created', 'gdcwc_woocommerce_checkout_subscription_created', 10, 3 );

	// Hook into renewal order creation and maybe add fee
	add_action( 'wcs_new_order_created', 'gdcwc_maybe_add_fee', 10, 3 );
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

	// TODO: Validate this is a float
	if ( isset( $_REQUEST['_subscription_post_trial_fee'] ) ) {
		update_post_meta( $post_id, '_subscription_post_trial_fee', $_REQUEST['_subscription_post_trial_fee'] );
	}
	//if ( isset( $_REQUEST['_variable_subscription_schedule_id'] ) ) {
	//	update_post_meta( $post_id, '_variable_subscription_schedule_id', $_REQUEST['_variable_subscription_schedule_id'] );
	//}
}

/**
 *  Maybe add Post Trial Fee meta to the subscription.
 *
 * @param $subscription    WC_Subscription
 * @param $order           WC_Order
 * @param $recurring_cart  array
 */
function gdcwc_woocommerce_checkout_subscription_created( $subscription, $order, $recurring_cart ) {
	// check order products (line items) for meta
	$items = $order->get_items();

	// TODO: figure out if multiple products have post trial fee.
	foreach ( $items as $item ) {
		$product_id     = $item->get_product_id();
		$post_trial_fee = get_post_meta( $product_id, '_subscription_post_trial_fee', true );
		if ( '' !== $post_trial_fee ) {
			// add to subscription
			update_post_meta( $subscription->get_id(), '_subscription_post_trial_fee', $post_trial_fee );
		}
	}
}

/**
 * Check line items for post trial fee and add if found.
 *
 * @param $new_order        WC_Order
 * @param $subscription     WC_Subscription
 * @param $type             string
 *
 * @return WC_Order
 */
function gdcwc_maybe_add_fee( $new_order, $subscription, $type ) {

	if ( 'renewal_order' !== $type ) {
		return $new_order;
	}

	// Check line items for post trial fee (WC_Order_Item)
	$items = $subscription->get_items();

	foreach ( $items as $item ) {
		// Check subscription for meta
		$post_trial_fee = get_post_meta( $subscription->get_id(), '_subscription_post_trial_fee', true );

		if ( '' !== $post_trial_fee ) {
			// Create a Fee Object
			$fee           = new StdClass();
			$fee->name     = __( 'Post Trial Fee', 'wc-subs-post-trial-fee' );
			$fee->amount   = $post_trial_fee;
			$fee->taxable  = false;
			$fee->tax      = '';
			$fee->tax_data = '';

			// add fee to order and recalculate
			try {
				$new_order->add_fee( $fee ); // TODO remove deprecated method
				$new_order->save();
				$new_order->calculate_totals();
			} catch ( Exception $e ) {
				// TODO do something
				$new_order->add_order_note( 'Failed to add Post Trial Fee' );
			}
			// remove meta from subscription
			delete_post_meta( $subscription->get_id(), '_subscription_post_trial_fee' );
		}
	}
	return $new_order;
}
