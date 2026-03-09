/**
 * Internal dependencies
 */
import { init } from './backorder-availability-date-notice';

jest.mock( '~/constants', () => ( {
	glaProductData: {
		glaTabTarget: 'gla_attributes',
	},
} ) );

const NOTICE_CLASS = 'gla-backorder-availability-date-notice';
const DATE_ID = 'gla_attributes_availabilityDate_date';
const TIME_ID = 'gla_attributes_availabilityDate_time';

function buildDOM( {
	withNotice = true,
	withDateEl = true,
	withTimeEl = true,
	backordersValue = '',
	stockStatusValue = '',
	dateValue = '',
	asRadio = true,
} = {} ) {
	const noticeHtml = withNotice
		? `<div class="${ NOTICE_CLASS }"></div>`
		: '';

	const backordersInput = asRadio
		? `
		<ul class="wc-radios" id="inventory_product_data">
			<input type="radio" name="_backorders" value="no" ${
				backordersValue === 'no' || backordersValue === ''
					? 'checked'
					: ''
			} />
			<input type="radio" name="_backorders" value="notify" ${
				backordersValue === 'notify' ? 'checked' : ''
			} />
			<input type="radio" name="_backorders" value="yes" ${
				backordersValue === 'yes' ? 'checked' : ''
			} />
		</ul>`
		: `<select name="_backorders"><option value="${ backordersValue }" selected>${ backordersValue }</option></select>`;

	const stockStatusInput = asRadio
		? `
		<input type="radio" name="_stock_status" value="instock" ${
			stockStatusValue === 'instock' || stockStatusValue === ''
				? 'checked'
				: ''
		} />
		<input type="radio" name="_stock_status" value="outofstock" ${
			stockStatusValue === 'outofstock' ? 'checked' : ''
		} />
		<input type="radio" name="_stock_status" value="onbackorder" ${
			stockStatusValue === 'onbackorder' ? 'checked' : ''
		} />`
		: `<select name="_stock_status"><option value="${ stockStatusValue }" selected>${ stockStatusValue }</option></select>`;

	const dateInput = withDateEl
		? `<input type="date" id="${ DATE_ID }" value="${ dateValue }" />`
		: '';
	const timeInput = withTimeEl
		? `<input type="time" id="${ TIME_ID }" />`
		: '';

	const tabLink = `
		<ul class="product_data_tabs">
			<li><a href="#gla_attributes">GLA Tab</a></li>
		</ul>
		<span class="gla-availability-date-tab-link">Go to GLA Tab</span>
	`;

	document.body.innerHTML = `
		${ noticeHtml }
		<div id="inventory_product_data">
			${ backordersInput }
			${ stockStatusInput }
			${ dateInput }
			${ timeInput }
		</div>
		${ tabLink }
	`;
}

describe( 'backorder-availability-date-notice', () => {
	describe( 'init()', () => {
		it( 'registers a DOMContentLoaded listener when readyState is loading', () => {
			Object.defineProperty( document, 'readyState', {
				get: () => 'loading',
				configurable: true,
			} );
			const addEventListenerSpy = jest.spyOn(
				document,
				'addEventListener'
			);

			init();

			expect( addEventListenerSpy ).toHaveBeenCalledWith(
				'DOMContentLoaded',
				expect.any( Function )
			);

			addEventListenerSpy.mockRestore();
		} );

		it( 'calls the notice initialiser directly when the DOM is already ready', () => {
			Object.defineProperty( document, 'readyState', {
				get: () => 'complete',
				configurable: true,
			} );

			buildDOM();
			init();

			const notice = document.querySelector( `.${ NOTICE_CLASS }` );
			// When no backorder is selected, notice should be hidden.
			expect( notice.style.display ).toBe( 'none' );
		} );
	} );

	describe( 'initBackorderAvailabilityDateNotice()', () => {
		beforeEach( () => {
			Object.defineProperty( document, 'readyState', {
				get: () => 'complete',
				configurable: true,
			} );
		} );

		it( 'bails out when .gla-backorder-availability-date-notice is absent', () => {
			buildDOM( { withNotice: false } );
			expect( () => init() ).not.toThrow();
		} );

		it( 'bails out when glaDateEl is absent', () => {
			buildDOM( { withDateEl: false } );
			expect( () => init() ).not.toThrow();
			// No error means bail-out worked; notice stays unmodified.
			const notice = document.querySelector( `.${ NOTICE_CLASS }` );
			expect( notice.style.display ).toBe( '' );
		} );

		it( 'bails out when glaTimeEl is absent', () => {
			buildDOM( { withTimeEl: false } );
			expect( () => init() ).not.toThrow();
			const notice = document.querySelector( `.${ NOTICE_CLASS }` );
			expect( notice.style.display ).toBe( '' );
		} );

		it( 'hides the notice when no backorder is selected', () => {
			buildDOM( { backordersValue: 'no', stockStatusValue: 'instock' } );
			init();

			const notice = document.querySelector( `.${ NOTICE_CLASS }` );
			expect( notice.style.display ).toBe( 'none' );
		} );

		it( 'shows the notice when backorder "yes" is selected and no date is set', () => {
			buildDOM( { backordersValue: 'yes', stockStatusValue: 'instock' } );
			init();

			const notice = document.querySelector( `.${ NOTICE_CLASS }` );
			expect( notice.style.display ).toBe( '' );
		} );

		it( 'shows the notice when backorder "notify" is selected and no date is set', () => {
			buildDOM( {
				backordersValue: 'notify',
				stockStatusValue: 'instock',
			} );
			init();

			const notice = document.querySelector( `.${ NOTICE_CLASS }` );
			expect( notice.style.display ).toBe( '' );
		} );

		it( 'shows the notice when stock status is "onbackorder" and no date is set', () => {
			buildDOM( {
				backordersValue: 'no',
				stockStatusValue: 'onbackorder',
			} );
			init();

			const notice = document.querySelector( `.${ NOTICE_CLASS }` );
			expect( notice.style.display ).toBe( '' );
		} );

		it( 'hides the notice when a backorder is selected but a date value is present', () => {
			buildDOM( {
				backordersValue: 'yes',
				stockStatusValue: 'instock',
				dateValue: '2026-06-01',
			} );
			init();

			const notice = document.querySelector( `.${ NOTICE_CLASS }` );
			expect( notice.style.display ).toBe( 'none' );
		} );

		it( 'triggers the GLA tab link click when the availability date tab link is clicked', () => {
			buildDOM( { backordersValue: 'no', stockStatusValue: 'instock' } );
			init();

			const tabLink = document.querySelector(
				'.product_data_tabs a[href="#gla_attributes"]'
			);
			const tabClickSpy = jest.fn();
			tabLink.click = tabClickSpy;

			const noticeLink = document.querySelector(
				'.gla-availability-date-tab-link'
			);
			noticeLink.dispatchEvent(
				new MouseEvent( 'click', { bubbles: true } )
			);

			expect( tabClickSpy ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'updates notice visibility on change event from a backorder field', () => {
			buildDOM( { backordersValue: 'no', stockStatusValue: 'instock' } );
			init();

			const notice = document.querySelector( `.${ NOTICE_CLASS }` );
			expect( notice.style.display ).toBe( 'none' );

			// Simulate selecting "yes" via a change event.
			const yesRadio = document.querySelector(
				'#inventory_product_data input[name="_backorders"][value="yes"]'
			);
			yesRadio.checked = true;
			yesRadio.dispatchEvent( new Event( 'change', { bubbles: true } ) );

			expect( notice.style.display ).toBe( '' );
		} );
	} );
} );
