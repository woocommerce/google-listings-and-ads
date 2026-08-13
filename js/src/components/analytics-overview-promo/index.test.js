/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AnalyticsOverviewPromo from './index';
import useProductRevenueMetricsDown from '~/hooks/useProductRevenueMetricsDown';

jest.mock( '~/hooks/useProductRevenueMetricsDown', () => jest.fn() );

describe( 'AnalyticsOverviewPromo', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders nothing while resolution is pending', () => {
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: false,
			isDown: false,
			metricsCase: null,
		} );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'reports which case is down once resolved', () => {
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: true,
			metricsCase: 'revenue',
		} );

		render( <AnalyticsOverviewPromo query={ {} } /> );

		expect(
			screen.getByText( 'Metrics down: revenue' )
		).toBeInTheDocument();
	} );

	it( 'reports metrics not down once resolved', () => {
		useProductRevenueMetricsDown.mockReturnValue( {
			hasFinishedResolution: true,
			isDown: false,
			metricsCase: null,
		} );

		render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( screen.getByText( 'Metrics not down' ) ).toBeInTheDocument();
	} );
} );
