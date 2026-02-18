/**
 * Backorder availability date notice on the product Inventory tab (classic editor).
 * Shows/hides the notice based on backorder selection and GLA availability date field.
 * Uses glaProductData.glaTabTarget for the "Google for WooCommerce" tab link.
 */

import { glaProductData } from '~/constants';

const GLA_DATE_INPUT_ID = 'gla_attributes_availabilityDate_date';
const GLA_TIME_INPUT_ID = 'gla_attributes_availabilityDate_time';

function getBackorderValue() {
	const backordersSelect = document.querySelector(
		'#inventory_product_data select[name="_backorders"]'
	);
	const checked = document.querySelector(
		'#inventory_product_data input[name="_backorders"]:checked'
	);
	return backordersSelect
		? backordersSelect.value
		: checked
			? checked.value
			: '';
}

function getStockStatusValue() {
	const stockSelect = document.querySelector(
		'#inventory_product_data select[name="_stock_status"]'
	);
	const checked = document.querySelector(
		'#inventory_product_data input[name="_stock_status"]:checked'
	);
	return stockSelect
		? stockSelect.value
		: checked
			? checked.value
			: '';
}

function isBackorderSelected() {
	const backorders = getBackorderValue();
	const stockStatus = getStockStatusValue();
	return (
		( backorders === 'yes' || backorders === 'notify' ) ||
		stockStatus === 'onbackorder'
	);
}

function hasGlaAvailabilityDate() {
	const dateInput = document.getElementById( GLA_DATE_INPUT_ID );
	return dateInput && dateInput.value.trim() !== '';
}

function updateNoticeVisibility( notice ) {
	const show = isBackorderSelected() && ! hasGlaAvailabilityDate();
	notice.style.display = show ? '' : 'none';
}

function initBackorderAvailabilityDateNotice() {
	const notice = document.querySelector(
		'.gla-backorder-availability-date-notice'
	);
	if ( ! notice ) {
		return;
	}

	const tabTarget =
		( glaProductData && glaProductData.glaTabTarget ) || 'gla_attributes';

	// Clicking the link switches to the GLA tab.
	document
		.querySelectorAll( '.gla-availability-date-tab-link' )
		.forEach( ( link ) => {
			link.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				const tabLink = document.querySelector(
					`.product_data_tabs a[href="#${ tabTarget }"]`
				);
				if ( tabLink ) {
					tabLink.click();
				}
			} );
		} );

	[ 'change', 'input' ].forEach( ( ev ) => {
		document
			.querySelectorAll(
				'#inventory_product_data [name="_backorders"], #inventory_product_data [name="_stock_status"]'
			)
			.forEach( ( el ) => {
				el.addEventListener( ev, () =>
					updateNoticeVisibility( notice )
				);
			} );
		const glaDateEl = document.getElementById( GLA_DATE_INPUT_ID );
		const glaTimeEl = document.getElementById( GLA_TIME_INPUT_ID );
		if ( glaDateEl ) {
			glaDateEl.addEventListener( ev, () =>
				updateNoticeVisibility( notice )
			);
		}
		if ( glaTimeEl ) {
			glaTimeEl.addEventListener( ev, () =>
				updateNoticeVisibility( notice )
			);
		}
	} );

	updateNoticeVisibility( notice );
}

export function init() {
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', () =>
			initBackorderAvailabilityDateNotice()
		);
	} else {
		initBackorderAvailabilityDateNotice();
	}
}
