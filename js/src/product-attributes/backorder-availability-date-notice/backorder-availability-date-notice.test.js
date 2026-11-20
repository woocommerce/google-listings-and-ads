/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import BackorderAvailabilityDateNotice from './backorder-availability-date-notice';

describe( 'BackorderAvailabilityDateNotice', () => {
	let glaDateEl;
	let glaTimeEl;

	function renderInventoryFields( {
		backorders,
		stockStatus,
		productType = 'simple',
	} ) {
		// Setting `innerHTML` on an element re-creates all of its
		// descendants, so this must run before `glaDateEl`/`glaTimeEl`
		// are appended, otherwise their references would be detached.
		document.body.innerHTML = `
			<select id="product-type">
				<option value="simple" ${
					productType === 'simple' ? 'selected' : ''
				}>Simple</option>
				<option value="variable" ${
					productType === 'variable' ? 'selected' : ''
				}>Variable</option>
			</select>
			<div id="inventory_product_data">
				<select name="_backorders">
					<option value="no" ${ backorders === 'no' ? 'selected' : '' }>No</option>
					<option value="yes" ${ backorders === 'yes' ? 'selected' : '' }>Yes</option>
				</select>
				<select name="_stock_status">
					<option value="instock" ${
						stockStatus === 'instock' ? 'selected' : ''
					}>In stock</option>
					<option value="onbackorder" ${
						stockStatus === 'onbackorder' ? 'selected' : ''
					}>On backorder</option>
				</select>
			</div>
			<div class="product_data_tabs">
				<a href="#gla_attributes">Google for WooCommerce tab</a>
			</div>
		`;

		glaDateEl = document.createElement( 'input' );
		glaTimeEl = document.createElement( 'input' );
		document.body.appendChild( glaDateEl );
		document.body.appendChild( glaTimeEl );
	}

	afterEach( () => {
		document.body.innerHTML = '';
	} );

	it( 'renders the warning notice when backorder is selected and no availability date is set', () => {
		renderInventoryFields( { backorders: 'yes', stockStatus: 'instock' } );
		glaDateEl.value = '';

		render(
			<BackorderAvailabilityDateNotice
				productTypes={ [ 'simple' ] }
				glaDateEl={ glaDateEl }
				glaTimeEl={ glaTimeEl }
			/>
		);

		expect(
			screen.getByText(
				'Google requires an availability date for products on backorder',
				{ exact: false }
			)
		).toBeInTheDocument();
	} );

	it( 'renders nothing when backorder is not selected', () => {
		renderInventoryFields( { backorders: 'no', stockStatus: 'instock' } );
		glaDateEl.value = '';

		const { container } = render(
			<BackorderAvailabilityDateNotice
				productTypes={ [ 'simple' ] }
				glaDateEl={ glaDateEl }
				glaTimeEl={ glaTimeEl }
			/>
		);

		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders nothing when an availability date is already set', () => {
		renderInventoryFields( { backorders: 'yes', stockStatus: 'instock' } );
		glaDateEl.value = '2026-01-01';

		const { container } = render(
			<BackorderAvailabilityDateNotice
				productTypes={ [ 'simple' ] }
				glaDateEl={ glaDateEl }
				glaTimeEl={ glaTimeEl }
			/>
		);

		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders nothing when the product type does not support an availability date', () => {
		renderInventoryFields( {
			backorders: 'yes',
			stockStatus: 'onbackorder',
			productType: 'variable',
		} );
		glaDateEl.value = '';

		const { container } = render(
			<BackorderAvailabilityDateNotice
				productTypes={ [ 'simple' ] }
				glaDateEl={ glaDateEl }
				glaTimeEl={ glaTimeEl }
			/>
		);

		expect( container.firstChild ).toBeNull();
	} );

	it( 'shows the notice after the product type changes to a supported type', () => {
		renderInventoryFields( {
			backorders: 'yes',
			stockStatus: 'onbackorder',
			productType: 'variable',
		} );
		glaDateEl.value = '';

		render(
			<BackorderAvailabilityDateNotice
				productTypes={ [ 'simple' ] }
				glaDateEl={ glaDateEl }
				glaTimeEl={ glaTimeEl }
			/>
		);

		const productTypeSelect = document.querySelector( '#product-type' );
		productTypeSelect.value = 'simple';
		fireEvent.change( productTypeSelect );

		expect(
			screen.getByText(
				'Google requires an availability date for products on backorder',
				{ exact: false }
			)
		).toBeInTheDocument();
	} );

	it( 'shows the notice after backorder is selected via a change event', () => {
		renderInventoryFields( { backorders: 'no', stockStatus: 'instock' } );
		glaDateEl.value = '';

		render(
			<BackorderAvailabilityDateNotice
				productTypes={ [ 'simple' ] }
				glaDateEl={ glaDateEl }
				glaTimeEl={ glaTimeEl }
			/>
		);

		expect(
			screen.queryByText(
				'Google requires an availability date for products on backorder',
				{ exact: false }
			)
		).not.toBeInTheDocument();

		const backordersSelect = document.querySelector(
			'#inventory_product_data select[name="_backorders"]'
		);
		backordersSelect.value = 'yes';
		fireEvent.change( backordersSelect );

		expect(
			screen.getByText(
				'Google requires an availability date for products on backorder',
				{ exact: false }
			)
		).toBeInTheDocument();
	} );

	it( 'hides the notice once an availability date is entered', () => {
		renderInventoryFields( { backorders: 'yes', stockStatus: 'instock' } );
		glaDateEl.value = '';

		render(
			<BackorderAvailabilityDateNotice
				productTypes={ [ 'simple' ] }
				glaDateEl={ glaDateEl }
				glaTimeEl={ glaTimeEl }
			/>
		);

		expect(
			screen.getByText(
				'Google requires an availability date for products on backorder',
				{ exact: false }
			)
		).toBeInTheDocument();

		glaDateEl.value = '2026-01-01';
		fireEvent.change( glaDateEl );

		expect(
			screen.queryByText(
				'Google requires an availability date for products on backorder',
				{ exact: false }
			)
		).not.toBeInTheDocument();
	} );

	it( 'clicks the corresponding tab link when the notice link is clicked', () => {
		renderInventoryFields( { backorders: 'yes', stockStatus: 'instock' } );
		glaDateEl.value = '';

		render(
			<BackorderAvailabilityDateNotice
				productTypes={ [ 'simple' ] }
				glaDateEl={ glaDateEl }
				glaTimeEl={ glaTimeEl }
			/>
		);

		const tabLink = document.querySelector(
			'.product_data_tabs a[href="#gla_attributes"]'
		);
		const tabLinkClick = jest.fn();
		tabLink.addEventListener( 'click', tabLinkClick );

		const noticeLink = document.querySelector(
			'.gla-availability-date-tab-link'
		);
		fireEvent.click( noticeLink );

		expect( tabLinkClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'removes its event listeners on unmount', () => {
		renderInventoryFields( { backorders: 'no', stockStatus: 'instock' } );
		glaDateEl.value = '';

		const { unmount } = render(
			<BackorderAvailabilityDateNotice
				productTypes={ [ 'simple' ] }
				glaDateEl={ glaDateEl }
				glaTimeEl={ glaTimeEl }
			/>
		);

		unmount();

		const backordersSelect = document.querySelector(
			'#inventory_product_data select[name="_backorders"]'
		);
		backordersSelect.value = 'yes';

		expect( () => fireEvent.change( backordersSelect ) ).not.toThrow();
		expect(
			screen.queryByText(
				'Google requires an availability date for products on backorder',
				{ exact: false }
			)
		).not.toBeInTheDocument();
	} );
} );
