/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AppDataViews from '.';

describe( 'AppDataViews', () => {
	let DataViewsStub;

	beforeEach( () => {
		DataViewsStub = jest.fn( () => <div data-testid="dataviews" /> );
		window.wp = { dataviews: { DataViews: DataViewsStub } };
	} );

	afterEach( () => {
		delete window.wp;
	} );

	test( 'renders DataViews from the window.wp.dataviews global and passes props through', () => {
		const props = {
			data: [ { id: 1 } ],
			fields: [ { id: 'name' } ],
			view: { type: 'table' },
			onChangeView: jest.fn(),
			isLoading: false,
		};

		render( <AppDataViews { ...props } /> );

		expect( screen.getByTestId( 'dataviews' ) ).toBeInTheDocument();
		expect( DataViewsStub ).toHaveBeenCalledTimes( 1 );
		expect( DataViewsStub.mock.calls[ 0 ][ 0 ] ).toEqual( props );
	} );
} );
