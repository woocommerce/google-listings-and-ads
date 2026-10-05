/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import usePreference from '~/hooks/usePreference';
import AnalyticsOverviewPromo from './index';

jest.mock( '~/hooks/usePreference', () =>
	jest.fn().mockName( 'usePreference' )
);

jest.mock( './promo-card', () => ( { query } ) => (
	<div data-testid="promo-card">{ JSON.stringify( query ) }</div>
) );

describe( 'AnalyticsOverviewPromo', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'renders the promo card with the query when not dismissed', () => {
		usePreference.mockReturnValue( false );

		render( <AnalyticsOverviewPromo query={ { period: 'month' } } /> );

		expect( screen.getByTestId( 'promo-card' ) ).toHaveTextContent(
			'{"period":"month"}'
		);
	} );

	test( 'renders nothing once dismissed, so the promo card does not mount', () => {
		usePreference.mockReturnValue( true );

		const { container } = render( <AnalyticsOverviewPromo query={ {} } /> );

		expect( container ).toBeEmptyDOMElement();
	} );
} );
