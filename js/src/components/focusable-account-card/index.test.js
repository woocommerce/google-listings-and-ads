/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import { getQuery, getNewPath, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import FocusableAccountCard from './index';
import useScrollIntoView from '~/hooks/useScrollIntoView';

jest.mock( '~/hooks/useScrollIntoView', () =>
	jest.fn().mockName( 'useScrollIntoView' )
);
jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getQuery: jest.fn().mockName( 'getQuery' ),
	getNewPath: jest.fn().mockName( 'getNewPath' ),
	getHistory: jest.fn().mockName( 'getHistory' ),
} ) );

describe( 'FocusableAccountCard', () => {
	let scrollIntoView;
	let replace;

	beforeEach( () => {
		jest.clearAllMocks();

		scrollIntoView = jest.fn().mockName( 'scrollIntoView' );
		useScrollIntoView.mockReturnValue( {
			containerRef: { current: null },
			scrollIntoView,
		} );

		replace = jest.fn().mockName( 'replace' );
		getHistory.mockReturnValue( { replace } );
		getNewPath.mockReturnValue( 'cleaned-path' );
		getQuery.mockReturnValue( {} );
	} );

	it( 'renders the wrapped card', () => {
		render(
			<FocusableAccountCard id="search-console">
				<div>Account card</div>
			</FocusableAccountCard>
		);

		expect( screen.getByText( 'Account card' ) ).toBeInTheDocument();
	} );

	it( 'scrolls into view and removes the query arg when it matches the id', () => {
		getQuery.mockReturnValue( { 'focus-account-card': 'search-console' } );

		render(
			<FocusableAccountCard id="search-console">
				<div>Account card</div>
			</FocusableAccountCard>
		);

		expect( scrollIntoView ).toHaveBeenCalledTimes( 1 );
		expect( getNewPath ).toHaveBeenCalledWith( {
			'focus-account-card': undefined,
		} );
		expect( replace ).toHaveBeenCalledWith( 'cleaned-path' );
	} );

	it( 'does nothing when the query arg names a different card', () => {
		getQuery.mockReturnValue( { 'focus-account-card': 'tag-manager' } );

		render(
			<FocusableAccountCard id="search-console">
				<div>Account card</div>
			</FocusableAccountCard>
		);

		expect( scrollIntoView ).not.toHaveBeenCalled();
		expect( replace ).not.toHaveBeenCalled();
	} );

	it( 'does nothing when the query arg is absent', () => {
		render(
			<FocusableAccountCard id="search-console">
				<div>Account card</div>
			</FocusableAccountCard>
		);

		expect( scrollIntoView ).not.toHaveBeenCalled();
		expect( replace ).not.toHaveBeenCalled();
	} );
} );
