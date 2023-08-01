/**
 * Internal dependencies
 */
import mapStoreAddressErrors from './mapStoreAddressErrors';

describe( 'mapStoreAddressErrors', () => {
	let storeAddress;

	beforeEach( () => {
		storeAddress = {
			isAddressFilled: true,
			missingRequiredFields: [],
		};
	} );

	it( 'When all fields are valid, should return an empty array.', () => {
		expect( mapStoreAddressErrors( storeAddress ) ).toEqual( [] );
	} );

	it( 'When required store address fields are missing, should map them to corresponding error messages', () => {
		storeAddress.isAddressFilled = false;
		storeAddress.missingRequiredFields = [
			'address_1',
			'city',
			'country',
			'postcode',
		];
		const errors = mapStoreAddressErrors( storeAddress );

		expect( errors.length ).toBe( 4 );
		expect( errors ).toMatchSnapshot();
	} );

	it( 'When newly added required fields are missing in store address, should map them to fallback error messages', () => {
		storeAddress.isAddressFilled = false;
		storeAddress.missingRequiredFields = [
			// Known field
			'postcode',
			// Newly added fields
			'other newly added field',
			'another newly added field',
		];
		const errors = mapStoreAddressErrors( storeAddress );

		expect( errors.length ).toBe( 3 );
		expect( errors ).toMatchSnapshot();
	} );
} );
