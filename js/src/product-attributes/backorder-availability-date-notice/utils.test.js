/**
 * Internal dependencies
 */
import {
	getFieldValue,
	isBackorderSelected,
	isProductTypeApplicable,
} from './utils';

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

	function renderInventoryFields( {
		manageStock = false,
		stock = '',
		backorders = 'no',
		stockStatus = 'instock',
	} ) {
		document.body.innerHTML = `
			<div id="inventory_product_data">
				<input type="checkbox" name="_manage_stock" ${ manageStock ? 'checked' : '' } />
				<input type="number" name="_stock" value="${ stock }" />
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

	describe( 'without stock management', () => {
		it( 'returns true when stock status is "onbackorder"', () => {
			renderInventoryFields( { stockStatus: 'onbackorder' } );

			expect( isBackorderSelected() ).toBe( true );
		} );

		it( 'returns false when stock status is "instock"', () => {
			renderInventoryFields( { stockStatus: 'instock' } );

			expect( isBackorderSelected() ).toBe( false );
		} );

		it( 'ignores a stale backorders value', () => {
			renderInventoryFields( {
				backorders: 'yes',
				stockStatus: 'instock',
			} );

			expect( isBackorderSelected() ).toBe( false );
		} );
	} );

	describe( 'with stock management', () => {
		it( 'returns true when backorders is "yes" and stock is zero', () => {
			renderInventoryFields( {
				manageStock: true,
				stock: '0',
				backorders: 'yes',
			} );

			expect( isBackorderSelected() ).toBe( true );
		} );

		it( 'returns true when backorders is "notify" and stock is negative', () => {
			renderInventoryFields( {
				manageStock: true,
				stock: '-2',
				backorders: 'notify',
			} );

			expect( isBackorderSelected() ).toBe( true );
		} );

		it( 'returns false when backorders are allowed but stock is positive', () => {
			renderInventoryFields( {
				manageStock: true,
				stock: '5',
				backorders: 'yes',
			} );

			expect( isBackorderSelected() ).toBe( false );
		} );

		it( 'returns false when backorders is "no" and stock is zero', () => {
			renderInventoryFields( {
				manageStock: true,
				stock: '0',
				backorders: 'no',
			} );

			expect( isBackorderSelected() ).toBe( false );
		} );

		it( 'ignores a stale stock status value', () => {
			renderInventoryFields( {
				manageStock: true,
				stock: '5',
				backorders: 'no',
				stockStatus: 'onbackorder',
			} );

			expect( isBackorderSelected() ).toBe( false );
		} );
	} );
} );

describe( 'isProductTypeApplicable', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	function renderProductTypeSelect( productType ) {
		document.body.innerHTML = `
			<select id="product-type">
				<option value="simple" ${
					productType === 'simple' ? 'selected' : ''
				}>Simple</option>
				<option value="variable" ${
					productType === 'variable' ? 'selected' : ''
				}>Variable</option>
			</select>
		`;
	}

	it( 'returns true when the selected product type is applicable', () => {
		renderProductTypeSelect( 'simple' );

		expect( isProductTypeApplicable( [ 'simple' ] ) ).toBe( true );
	} );

	it( 'returns false when the selected product type is not applicable', () => {
		renderProductTypeSelect( 'variable' );

		expect( isProductTypeApplicable( [ 'simple' ] ) ).toBe( false );
	} );

	it( 'returns false when there is no product type select', () => {
		expect( isProductTypeApplicable( [ 'simple' ] ) ).toBe( false );
	} );
} );
