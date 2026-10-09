/**
 * Internal dependencies
 */
import { insertAlphabetically } from './utils';

const createContainer = ( html ) => {
	const container = document.createElement( 'div' );
	container.innerHTML = html;
	return container;
};

const createBox = ( service ) => {
	const box = document.createElement( 'div' );
	box.dataset.service = service;
	return box;
};

const getOrder = ( container ) =>
	Array.from( container.children ).map(
		( element ) => element.dataset.service || element.id
	);

describe( 'insertAlphabetically', () => {
	test( 'Appends the box when there are no other services', () => {
		const container = createContainer( '<div id="other"></div>' );

		insertAlphabetically( container, createBox( 'gamma' ) );

		expect( getOrder( container ) ).toEqual( [ 'other', 'gamma' ] );
	} );

	test( 'Inserts the box before the first service that sorts after it', () => {
		const container = createContainer(
			'<div data-service="alpha"></div><div data-service="gamma"></div>'
		);

		insertAlphabetically( container, createBox( 'beta' ) );

		expect( getOrder( container ) ).toEqual( [ 'alpha', 'beta', 'gamma' ] );
	} );

	test( 'Inserts the box right after the last service when it sorts last', () => {
		const container = createContainer(
			'<div data-service="alpha"></div><div id="other"></div>'
		);

		insertAlphabetically( container, createBox( 'gamma' ) );

		expect( getOrder( container ) ).toEqual( [
			'alpha',
			'gamma',
			'other',
		] );
	} );

	test( 'Moves an existing box that is out of place', () => {
		const container = createContainer(
			'<div data-service="gamma"></div><div data-service="alpha"></div>'
		);

		insertAlphabetically( container, container.lastElementChild );

		expect( getOrder( container ) ).toEqual( [ 'alpha', 'gamma' ] );
	} );

	test( 'Does not move an existing box that is already in place', () => {
		const container = createContainer(
			'<div data-service="alpha"></div><div data-service="gamma"></div>'
		);
		const insertBefore = jest.spyOn( container, 'insertBefore' );

		insertAlphabetically( container, container.firstElementChild );
		insertAlphabetically( container, container.lastElementChild );

		expect( insertBefore ).not.toHaveBeenCalled();
		expect( getOrder( container ) ).toEqual( [ 'alpha', 'gamma' ] );
	} );
} );
