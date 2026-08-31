/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { useDispatch } from '@wordpress/data';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import { ANALYTICS_OVERVIEW_PROMO_KEY } from './constants';
import AnalyticsOverviewPromo from './index';
import usePreference from '~/hooks/usePreference';

jest.mock( '@wordpress/components', () => ( {
	Flex: ( { children } ) => <div>{ children }</div>,
	FlexBlock: ( { children } ) => <div>{ children }</div>,
	FlexItem: ( { children } ) => <div>{ children }</div>,
} ) );

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: jest.fn(),
} ) );

jest.mock( '@wordpress/preferences', () => ( {
	__esModule: true,
	store: 'preferences',
} ) );

jest.mock( '~/hooks/usePreference', () =>
	jest.fn().mockName( 'usePreference' )
);

jest.mock( '~/components/app-button', () => ( { children, onClick } ) => (
	<button onClick={ onClick }>{ children }</button>
) );

describe( 'AnalyticsOverviewPromo', () => {
	beforeEach( () => {
		useDispatch.mockReturnValue( { set: jest.fn() } );
	} );

	it( 'renders the promo with a Dismiss control when not dismissed', () => {
		usePreference.mockReturnValue( false );

		render( <AnalyticsOverviewPromo /> );

		expect( screen.getByText( 'placeholder' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Dismiss' } )
		).toBeInTheDocument();
	} );

	it( 'renders nothing once dismissed', () => {
		usePreference.mockReturnValue( true );

		const { container } = render( <AnalyticsOverviewPromo /> );

		expect( container.firstChild ).toBeNull();
	} );

	it( 'persists the dismissal to the preferences store on Dismiss click', () => {
		const setMock = jest.fn();
		useDispatch.mockReturnValue( { set: setMock } );
		usePreference.mockReturnValue( false );

		render( <AnalyticsOverviewPromo /> );
		fireEvent.click( screen.getByRole( 'button', { name: 'Dismiss' } ) );

		expect( setMock ).toHaveBeenCalledWith(
			PREFERENCES_STORE_NAMESPACE,
			ANALYTICS_OVERVIEW_PROMO_KEY,
			true
		);
	} );

	it( 'stays hidden after a reload when the persisted preference is set', () => {
		// Simulate a fresh page load hydrating the persisted preference as dismissed.
		usePreference.mockReturnValue( true );

		const { container } = render( <AnalyticsOverviewPromo /> );

		expect( container.firstChild ).toBeNull();
		expect( screen.queryByText( 'placeholder' ) ).not.toBeInTheDocument();
	} );
} );
