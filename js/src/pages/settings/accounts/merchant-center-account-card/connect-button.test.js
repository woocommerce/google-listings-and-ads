/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectButton from './connect-button';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

describe( 'ConnectButton', () => {
	it( 'tracks the "Connect" button click', async () => {
		const user = userEvent.setup();

		render( <ConnectButton /> );

		// The button renders as a real anchor; jsdom doesn't implement
		// navigation, so prevent the click from following the href.
		const link = screen.getByRole( 'link', {
			name: 'Connect',
		} );
		link.addEventListener( 'click', ( event ) => event.preventDefault() );

		await user.click( link );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_set_up_merchant_center_click',
			{ context: 'settings-linked-accounts' }
		);
	} );
} );
