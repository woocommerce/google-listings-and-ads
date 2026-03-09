/**
 * Internal dependencies
 */
import { glaProductData } from '~/constants';

const GLA_DATE_INPUT_ID = 'gla_attributes_availabilityDate_date';
const GLA_TIME_INPUT_ID = 'gla_attributes_availabilityDate_time';
const INVENTORY_PRODUCT_DATA = '#inventory_product_data';

function getBackorderValue() {
	const backordersSelect = document.querySelector(
		`${ INVENTORY_PRODUCT_DATA } select[name="_backorders"]`
	);
	if ( backordersSelect ) {
		return backordersSelect.value;
	}
	const checked = document.querySelector(
		`${ INVENTORY_PRODUCT_DATA } input[name="_backorders"]:checked`
	);
	if ( checked ) {
		return checked.value;
	}
	return '';
}

function getStockStatusValue() {
	const stockSelect = document.querySelector(
		`${ INVENTORY_PRODUCT_DATA } select[name="_stock_status"]`
	);
	if ( stockSelect ) {
		return stockSelect.value;
	}
	const checked = document.querySelector(
		`${ INVENTORY_PRODUCT_DATA } input[name="_stock_status"]:checked`
	);
	if ( checked ) {
		return checked.value;
	}
	return '';
}

function isBackorderSelected() {
	const backorders = getBackorderValue();
	const stockStatus = getStockStatusValue();
	return (
		[ 'yes', 'notify' ].includes( backorders ) ||
		stockStatus === 'onbackorder'
	);
}

function hasGlaAvailabilityDate() {
	const dateInput = document.getElementById( GLA_DATE_INPUT_ID );
	return dateInput && dateInput.value.trim() !== '';
}

function updateNoticeVisibility() {
	const notice = document.querySelector(
		'.gla-backorder-availability-date-notice'
	);
	if ( ! notice ) {
		return;
	}
	const show = isBackorderSelected() && ! hasGlaAvailabilityDate();
	notice.style.display = show ? '' : 'none';
}

/**
 * Backorder availability date notice on the product Inventory tab (classic editor).
 * Shows/hides the notice based on backorder selection and GLA availability date field.
 * Uses glaProductData.glaTabTarget for the "Google for WooCommerce" tab link.
 */
function initBackorderAvailabilityDateNotice() {
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

	const tabTarget = glaProductData.glaTabTarget || 'gla_attributes';

	// Clicking the link switches to the GLA tab.
	document
		.querySelectorAll( '.gla-availability-date-tab-link' )
		.forEach( ( link ) => {
			link.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				const tabLink = document.querySelector(
					`.product_data_tabs a[href="#${ tabTarget }"]`
				);
				if ( tabLink ) {
					tabLink.click();
				}
			} );
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

export function init() {
	if ( document.readyState === 'loading' ) {
		document.addEventListener(
			'DOMContentLoaded',
			initBackorderAvailabilityDateNotice
		);
	} else {
		initBackorderAvailabilityDateNotice();
	}
}
