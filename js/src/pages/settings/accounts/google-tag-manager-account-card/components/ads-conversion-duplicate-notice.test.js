/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AdsConversionDuplicateNotice from './ads-conversion-duplicate-notice';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';

jest.mock( '~/hooks/useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' )
);

describe( 'AdsConversionDuplicateNotice', () => {
	it( 'renders the warning when a Google Ads account is connected', () => {
		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: true,
			hasFinishedResolution: true,
		} );

		render( <AdsConversionDuplicateNotice /> );

		expect(
			screen.getByText(
				( _, element ) =>
					element?.tagName === 'P' &&
					/already adds a Google Ads conversion tag/.test(
						element.textContent
					)
			)
		).toBeInTheDocument();
	} );

	it( 'renders nothing when no Google Ads account is connected', () => {
		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: false,
			hasFinishedResolution: true,
		} );

		const { container } = render( <AdsConversionDuplicateNotice /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders nothing before the Google Ads account has finished resolving, even if it will turn out to be connected', () => {
		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: true,
			hasFinishedResolution: false,
		} );

		const { container } = render( <AdsConversionDuplicateNotice /> );

		expect( container ).toBeEmptyDOMElement();
	} );
} );
