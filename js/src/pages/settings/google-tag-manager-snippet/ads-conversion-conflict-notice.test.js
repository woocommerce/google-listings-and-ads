/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AdsConversionConflictNotice from './ads-conversion-conflict-notice';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';

jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);

/**
 * Mocks `useGoogleTagManagerAccount`.
 *
 * @param {Object|null} account The account payload to mock.
 */
function mockAccount( account ) {
	useGoogleTagManagerAccount.mockReturnValue( {
		account,
		hasFinishedResolution: true,
	} );
}

describe( 'AdsConversionConflictNotice', () => {
	it( 'warns about the conflict, naming the connected container', () => {
		mockAccount( {
			status: GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED,
			containerPublicId: 'GTM-ABC1234',
			adsConversionConflict: true,
		} );

		render( <AdsConversionConflictNotice /> );

		expect(
			screen.getByText(
				'The connected Google Tag Manager container (GTM-ABC1234) contains a Google Ads Conversion script. Google Ads events are already captured natively by the plugin. Enabling this tag can lead to duplicate events registration.',
				// The notice also announces its text in a screen-reader live region.
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
	} );

	it( 'cannot be dismissed', () => {
		mockAccount( {
			status: GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED,
			containerPublicId: 'GTM-ABC1234',
			adsConversionConflict: true,
		} );

		render( <AdsConversionConflictNotice /> );

		expect( screen.queryByRole( 'button' ) ).not.toBeInTheDocument();
	} );

	it.each( [
		[
			'no conflict was detected',
			{
				status: GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED,
				adsConversionConflict: false,
			},
		],
		[
			'no container is connected',
			{ status: GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.DISCONNECTED },
		],
		[ 'the connection has not loaded', null ],
	] )( 'renders nothing when %s', ( _, account ) => {
		mockAccount( account );

		const { container } = render( <AdsConversionConflictNotice /> );

		expect( container ).toBeEmptyDOMElement();
	} );
} );
