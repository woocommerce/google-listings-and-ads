/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { namespace, actionPrefix } from './constants';
import { getItemObject, trackEvent } from './utils';

addAction(
	`${ actionPrefix }-cart-add-item`,
	namespace,
	( { product, quantity = 1 } ) => {
		trackEvent( 'add_to_cart', {
			ecomm_pagetype: 'cart',
			event_category: 'ecommerce',
			event_label: __( 'Add to Cart', 'google-listings-and-ads' ),
			items: [ getItemObject( product, quantity ) ],
		} );
	}
);
