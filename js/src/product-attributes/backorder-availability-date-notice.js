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
 * Shows/hides the notice based on backorder selection and GLA availability date field.
 * Uses glaProductData.glaTabTarget for the "Google for WooCommerce" tab link.
 */
export default function initBackorderAvailabilityDateNotice() {
	const notice = document.querySelector(
		'.gla-backorder-availability-date-notice'
	);

	if ( ! notice ) {
		return;
	}

	const glaDateEl = document.getElementById( GLA_DATE_INPUT_ID );
	const glaTimeEl = document.getElementById( GLA_TIME_INPUT_ID );

	if ( ! glaDateEl || ! glaTimeEl ) {
		return;
	}

	function updateNoticeVisibility() {
		const hasGlaAvailabilityDate = glaDateEl.value.trim() !== '';
		const shouldShowNotice =
			isBackorderSelected() && ! hasGlaAvailabilityDate;
		notice.style.display = shouldShowNotice ? '' : 'none';
	}

	const tabTarget = glaProductData.glaTabTarget || 'gla_attributes';

	// Clicking the link switches to the GLA tab.
	const noticeLink = document.querySelector(
		'.gla-availability-date-tab-link'
	);

	noticeLink?.addEventListener( 'click', ( event ) => {
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
