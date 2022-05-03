/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

/**
 * Internal dependencies
 */
import CampaignConversionStatusNotice from '.~/components/campaign-conversion-notice/index';
import getConversionCampaignStatusNotice from '.~/utils/getConversionCampaignStatusNotice';

jest.mock( '.~/utils/getConversionCampaignStatusNotice', () => jest.fn() );

describe( 'Notice Campaign Migration', () => {
	it( 'Unconverted', () => {
		getConversionCampaignStatusNotice.mockReturnValueOnce(
			'BEFORE_CONVERSION'
		);

		const { getByTestId } = render( <CampaignConversionStatusNotice /> );

		const activeComponent = getByTestId(
			'gla-campaign-conversion-dashboard-notice'
		);
		expect( activeComponent ).toHaveTextContent(
			'Your Google Listings & Ads campaigns will soon be automatically upgraded'
		);
	} );
	it( 'Converted', () => {
		getConversionCampaignStatusNotice.mockReturnValueOnce(
			'AFTER_CONVERSION'
		);
		const { getByTestId } = render( <CampaignConversionStatusNotice /> );

		const activeComponent = getByTestId(
			'gla-campaign-conversion-dashboard-notice'
		);
		expect( activeComponent ).toHaveTextContent(
			'Your Google Listings & Ads campaigns have been automatically upgraded'
		);
	} );
	it( 'Dismissed Notice', () => {
		getConversionCampaignStatusNotice.mockReturnValueOnce(
			'AFTER_CONVERSION'
		);
		const { queryByTestId, getByRole } = render(
			<CampaignConversionStatusNotice />
		);

		const closeButton = getByRole( 'button' );

		fireEvent.click( closeButton );

		const notice = queryByTestId(
			'gla-campaign-conversion-dashboard-notice'
		);

		expect( notice ).toBeFalsy();
	} );
	it( 'No campaign status', () => {
		getConversionCampaignStatusNotice.mockReturnValueOnce( null );
		const { queryByTestId } = render( <CampaignConversionStatusNotice /> );

		const notice = queryByTestId(
			'gla-campaign-conversion-dashboard-notice'
		);

		expect( notice ).toBeFalsy();
	} );
} );
