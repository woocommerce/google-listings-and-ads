/**
 * Internal dependencies
 */
import { generateKeyFromObject } from './generateKeyFromObject';

describe( 'generateKeyFromObject', () => {
	it( 'should generate a key for a flat object', () => {
		const obj = { a: 1, b: 'test', c: true };
		expect( generateKeyFromObject( obj ) ).toBe( 'a-1-b-test-c-true' );
	} );

	it( 'should generate a key for an object with arrays', () => {
		const obj = { page: 1, filters: [ 'red', 'blue' ] };
		expect( generateKeyFromObject( obj ) ).toBe(
			'filters-red-blue-page-1'
		);
	} );

	it( 'should generate a key for a nested object', () => {
		const obj = { options: { sort: 'asc', show: true } };
		expect( generateKeyFromObject( obj ) ).toBe(
			'options.show-true-options.sort-asc'
		);
	} );

	it( 'should generate a key for a complex object', () => {
		const obj = {
			page: 1,
			filters: [ 'red', 'blue' ],
			options: { sort: 'asc', show: true },
		};
		expect( generateKeyFromObject( obj ) ).toBe(
			'filters-red-blue-options.show-true-options.sort-asc-page-1'
		);
	} );

	it( 'should handle empty objects', () => {
		expect( generateKeyFromObject( {} ) ).toBe( 'empty' );
	} );

	it( 'should handle arrays with empty strings', () => {
		const obj = { arr: [ '', 'foo', '', 'bar' ] };
		expect( generateKeyFromObject( obj ) ).toBe( 'arr-foo-bar' );
	} );

	it( 'should handle nested arrays and objects', () => {
		const obj = {
			a: [ 1, 2, 3 ],
			b: { c: [ 4, 5 ], d: { e: 6 } },
		};
		expect( generateKeyFromObject( obj ) ).toBe(
			'a-1-2-3-b.c-4-5-b.d.e-6'
		);
	} );

	it( 'should convert non-string primitives to strings', () => {
		const obj = { a: false, b: null, c: undefined, d: 0 };
		expect( generateKeyFromObject( obj ) ).toBe(
			'a-false-b-null-c-undefined-d-0'
		);
	} );

	it( 'should generate stable keys regardless of property order', () => {
		// Different property order
		const obj1 = { c: true, a: 1, b: 'test' };
		const obj2 = { a: 1, b: 'test', c: true };

		expect( generateKeyFromObject( obj1 ) ).toBe(
			generateKeyFromObject( obj2 )
		);
		expect( generateKeyFromObject( obj1 ) ).toBe( 'a-1-b-test-c-true' );

		// Different order with nested objects
		const nested1 = {
			options: { show: true, sort: 'asc' },
			filters: [ 'red', 'blue' ],
			page: 1,
		};

		const nested2 = {
			page: 1,
			filters: [ 'red', 'blue' ],
			options: { sort: 'asc', show: true },
		};

		expect( generateKeyFromObject( nested1 ) ).toBe(
			generateKeyFromObject( nested2 )
		);
		expect( generateKeyFromObject( nested1 ) ).toBe(
			'filters-red-blue-options.show-true-options.sort-asc-page-1'
		);
	} );
} );
