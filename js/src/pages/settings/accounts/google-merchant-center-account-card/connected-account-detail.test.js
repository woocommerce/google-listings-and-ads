/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectedAccountDetail from './connected-account-detail';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

describe( 'ConnectedAccountDetail', () => {
	it( 'links the account ID to its Merchant Center overview and tracks the click', async () => {
		const user = userEvent.setup();
		const href = 'https://merchants.google.com/mc/overview?a=123456';

		render( <ConnectedAccountDetail id={ 123456 } /> );

		// The link opens in a new tab; jsdom doesn't implement navigation,
		// so prevent the click from following the href.
		const link = screen.getByRole( 'link', { name: /123456/ } );
		link.addEventListener( 'click', ( event ) => event.preventDefault() );

		expect( link ).toHaveAttribute( 'href', href );

		await user.click( link );

		expect( recordGlaEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_mc_link_click',
			{ context: 'settings-linked-accounts', href }
		);
	} );
} );
