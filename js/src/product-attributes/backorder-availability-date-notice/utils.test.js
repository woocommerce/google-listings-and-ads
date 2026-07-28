/**
 * Internal dependencies
 */
import { getFieldValue, isBackorderSelected } from './utils';

describe( 'getFieldValue', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	it( 'returns the value of a select field with the given name', () => {
		document.body.innerHTML = `
			<div id="inventory_product_data">
				<select name="_backorders">
					<option value="no">No</option>
					<option value="yes" selected>Yes</option>
				</select>
			</div>
		`;

		expect( getFieldValue( '_backorders' ) ).toBe( 'yes' );
	} );

	it( 'returns the value of the checked radio input with the given name', () => {
		document.body.innerHTML = `
			<div id="inventory_product_data">
				<input type="radio" name="_stock_status" value="instock" />
				<input type="radio" name="_stock_status" value="onbackorder" checked />
			</div>
		`;

		expect( getFieldValue( '_stock_status' ) ).toBe( 'onbackorder' );
	} );

	it( 'prefers a select field over a checked input with the same name', () => {
		document.body.innerHTML = `
			<div id="inventory_product_data">
				<select name="_backorders">
					<option value="no" selected>No</option>
				</select>
				<input type="radio" name="_backorders" value="yes" checked />
			</div>
		`;

		expect( getFieldValue( '_backorders' ) ).toBe( 'no' );
	} );

	it( 'returns an empty string when the field does not exist', () => {
		document.body.innerHTML = `<div id="inventory_product_data"></div>`;

		expect( getFieldValue( '_backorders' ) ).toBe( '' );
	} );

	it( 'returns an empty string when no radio input is checked', () => {
		document.body.innerHTML = `
			<div id="inventory_product_data">
				<input type="radio" name="_stock_status" value="instock" />
				<input type="radio" name="_stock_status" value="onbackorder" />
			</div>
		`;

		expect( getFieldValue( '_stock_status' ) ).toBe( '' );
	} );

	it( 'ignores fields outside of the inventory product data container', () => {
		document.body.innerHTML = `
			<div id="inventory_product_data"></div>
			<select name="_backorders">
				<option value="yes" selected>Yes</option>
			</select>
		`;

		expect( getFieldValue( '_backorders' ) ).toBe( '' );
	} );
} );

describe( 'isBackorderSelected', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	function renderInventoryFields( { backorders, stockStatus } ) {
		document.body.innerHTML = `
			<div id="inventory_product_data">
				<select name="_backorders">
					<option value="no" ${ backorders === 'no' ? 'selected' : '' }>No</option>
					<option value="yes" ${ backorders === 'yes' ? 'selected' : '' }>Yes</option>
					<option value="notify" ${
						backorders === 'notify' ? 'selected' : ''
					}>Notify</option>
				</select>
				<input type="radio" name="_stock_status" value="instock" ${
					stockStatus === 'instock' ? 'checked' : ''
				} />
				<input type="radio" name="_stock_status" value="outofstock" ${
					stockStatus === 'outofstock' ? 'checked' : ''
				} />
				<input type="radio" name="_stock_status" value="onbackorder" ${
					stockStatus === 'onbackorder' ? 'checked' : ''
				} />
			</div>
		`;
	}

	it( 'returns true when backorders is "yes"', () => {
		renderInventoryFields( { backorders: 'yes', stockStatus: 'instock' } );

		expect( isBackorderSelected() ).toBe( true );
	} );

	it( 'returns true when backorders is "notify"', () => {
		renderInventoryFields( {
			backorders: 'notify',
			stockStatus: 'instock',
		} );

		expect( isBackorderSelected() ).toBe( true );
	} );

	it( 'returns true when stock status is "onbackorder"', () => {
		renderInventoryFields( {
			backorders: 'no',
			stockStatus: 'onbackorder',
		} );

		expect( isBackorderSelected() ).toBe( true );
	} );

	it( 'returns false when backorders is "no" and stock status is "instock"', () => {
		renderInventoryFields( { backorders: 'no', stockStatus: 'instock' } );

		expect( isBackorderSelected() ).toBe( false );
	} );

	it( 'returns false when backorders is "no" and stock status is "outofstock"', () => {
		renderInventoryFields( {
			backorders: 'no',
			stockStatus: 'outofstock',
		} );

		expect( isBackorderSelected() ).toBe( false );
	} );
} );
