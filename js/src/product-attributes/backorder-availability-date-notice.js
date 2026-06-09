/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import {
	createElement,
	createInterpolateElement,
	createRoot,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaProductData } from '~/constants';

const GLA_DATE_INPUT_ID = 'gla_attributes_availabilityDate_date';
const GLA_TIME_INPUT_ID = 'gla_attributes_availabilityDate_time';
const INVENTORY_PRODUCT_DATA = '#inventory_product_data';

/**
 * Get the value of a field from the inventory product data.
 *
 * @param {string} name - The name of the field to get the value of.
 * @return {string} The value of the field.
 */
function getFieldValue( name ) {
	const select = document.querySelector(
		`${ INVENTORY_PRODUCT_DATA } select[name="${ name }"]`
	);

	if ( select ) {
		return select.value;
	}

	const checked = document.querySelector(
		`${ INVENTORY_PRODUCT_DATA } input[name="${ name }"]:checked`
	);

	if ( checked ) {
		return checked.value;
	}

	return '';
}

/**
 * Check if the backorder is selected.
 *
 * @return {boolean} True if the backorder is selected, false otherwise.
 */
function isBackorderSelected() {
	const backorders = getFieldValue( '_backorders' );
	const stockStatus = getFieldValue( '_stock_status' );

	return (
		[ 'yes', 'notify' ].includes( backorders ) ||
		stockStatus === 'onbackorder'
	);
}

/**
 * Backorder availability date notice on the product Inventory tab (classic editor).
 * Mounts a WordPress Notice component and shows/hides it based on backorder selection
 * and GLA availability date field.
 */
export default function initBackorderAvailabilityDateNotice() {
	const container = document.querySelector(
		'.gla-backorder-availability-date-notice'
	);

	if ( ! container ) {
		return;
	}

	const glaDateEl = document.getElementById( GLA_DATE_INPUT_ID );
	const glaTimeEl = document.getElementById( GLA_TIME_INPUT_ID );

	if ( ! glaDateEl || ! glaTimeEl ) {
		return;
	}

	const tabTarget = glaProductData.glaTabTarget || 'gla_attributes';

	const message = createInterpolateElement(
		__(
			'Google requires an availability date for products on backorder. Set the Availability date in the <link>Google for WooCommerce tab</link> so your product can be submitted correctly.',
			'google-listings-and-ads'
		),
		{
			link: createElement( 'a', {
				href: `#${ tabTarget }`,
				className: 'gla-availability-date-tab-link',
			} ),
		}
	);

	createRoot( container ).render(
		createElement(
			Notice,
			{ status: 'warning', isDismissible: false },
			message
		)
	);

	function updateNoticeVisibility() {
		const hasGlaAvailabilityDate = glaDateEl.value.trim() !== '';
		const shouldShowNotice =
			isBackorderSelected() && ! hasGlaAvailabilityDate;
		container.style.display = shouldShowNotice ? '' : 'none';
	}

	// Use event delegation since React renders the link asynchronously.
	container.addEventListener( 'click', ( event ) => {
		const link = event.target.closest( '.gla-availability-date-tab-link' );

		if ( ! link ) {
			return;
		}

		event.preventDefault();

		const tabLink = document.querySelector(
			`.product_data_tabs a[href="#${ tabTarget }"]`
		);
		tabLink?.click();
	} );

	document
		.querySelectorAll(
			`${ INVENTORY_PRODUCT_DATA } [name="_backorders"], ${ INVENTORY_PRODUCT_DATA } [name="_stock_status"]`
		)
		.forEach( ( element ) => {
			element.addEventListener( 'change', updateNoticeVisibility );
		} );

	glaDateEl.addEventListener( 'change', updateNoticeVisibility );
	glaTimeEl.addEventListener( 'change', updateNoticeVisibility );

	updateNoticeVisibility();
}
