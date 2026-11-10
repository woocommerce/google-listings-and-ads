/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getHistory, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import SubmissionSuccessGuide from './';

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getQuery: jest.fn(),
	getHistory: jest.fn(),
} ) );
jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn(),
} ) );
jest.mock( '~/utils/localStorage', () => ( {
	get: jest.fn(),
	set: jest.fn(),
} ) );

describe( 'SubmissionSuccessGuide', () => {
	let replace;

	beforeEach( () => {
		jest.clearAllMocks();

		replace = jest.fn();
		getHistory.mockReturnValue( { replace } );
	} );

	const closeGuide = async () => {
		render( <SubmissionSuccessGuide /> );
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Close' } )
		);
	};

	it( 'keeps the Ads credit offer query arg when the guide is closed', async () => {
		getQuery.mockReturnValue( {
			guide: 'submission-success',
			incentive: 'applied',
		} );

		await closeGuide();

		expect( replace ).toHaveBeenCalledTimes( 1 );
		expect( replace.mock.calls[ 0 ][ 0 ] ).toContain( 'incentive=applied' );
		expect( replace.mock.calls[ 0 ][ 0 ] ).not.toContain( 'guide=' );
	} );

	it( 'does not add the query arg when it is absent', async () => {
		getQuery.mockReturnValue( { guide: 'submission-success' } );

		await closeGuide();

		expect( replace ).toHaveBeenCalledTimes( 1 );
		expect( replace.mock.calls[ 0 ][ 0 ] ).not.toContain( 'incentive' );
	} );
} );
