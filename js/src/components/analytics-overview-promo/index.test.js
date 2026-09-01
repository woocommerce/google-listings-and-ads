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
import usePreference from '~/hooks/usePreference';
import useProductRevenueMetricsDown from '~/hooks/useProductRevenueMetricsDown';
import AnalyticsOverviewPromo from './index';

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

jest.mock( '~/hooks/useProductRevenueMetricsDown', () => jest.fn() );

jest.mock( '~/components/app-button', () => ( { children, onClick } ) => (
	<button onClick={ onClick }>{ children }</button>
) );

describe( 'AnalyticsOverviewPromo', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useDispatch.mockReturnValue( { set: jest.fn() } );
	} );

	it( 'renders nothing while resolution is pending', () => {
		usePreference.mockReturnValue( false );
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: false,
			isDown: false,
			metricsCase: null,
		} );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders nothing once dismissed', () => {
		usePreference.mockReturnValue( true );
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: false,
			metricsCase: null,
		} );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container.firstChild ).toBeNull();
	} );

	it( 'reports which case is down with a Dismiss control once resolved', () => {
		usePreference.mockReturnValue( false );
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: true,
			metricsCase: 'revenue',
		} );

		render( <AnalyticsOverviewPromo query={ {} } /> );

		expect(
			screen.getByText( 'Metrics down: revenue' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Dismiss' } )
		).toBeInTheDocument();
	} );

	it( 'reports metrics not down once resolved', () => {
		usePreference.mockReturnValue( false );
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: false,
			metricsCase: null,
		} );

		render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( screen.getByText( 'Metrics not down' ) ).toBeInTheDocument();
	} );

	it( 'persists the dismissal to the preferences store on Dismiss click', () => {
		const setMock = jest.fn();
		useDispatch.mockReturnValue( { set: setMock } );
		usePreference.mockReturnValue( false );
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: false,
			metricsCase: null,
		} );

		render( <AnalyticsOverviewPromo query={ {} } /> );
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
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: true,
			metricsCase: 'revenue',
		} );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container.firstChild ).toBeNull();
		expect(
			screen.queryByText( 'Metrics down: revenue' )
		).not.toBeInTheDocument();
	} );
} );
