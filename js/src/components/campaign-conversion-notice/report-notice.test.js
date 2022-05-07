/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

/**
 * Internal dependencies
 */
import CampaignConversionReportsNotice from '.~/components/campaign-conversion-notice/reports-notice';
import getConversionCampaignStatusNotice from '.~/utils/getConversionCampaignStatusNotice';

jest.mock( '.~/utils/getConversionCampaignStatusNotice', () => jest.fn() );

describe( 'Notice Reports Campaign Migration', () => {
	it( 'Unconverted', () => {
		getConversionCampaignStatusNotice.mockReturnValueOnce(
			'BEFORE_CONVERSION'
		);

		const { queryByTestId } = render( <CampaignConversionReportsNotice /> );

		const notice = queryByTestId(
			'gla-campaign-conversion-reports-notice'
		);

		expect( notice ).toBeFalsy();
	} );
	it( 'Converted', () => {
		getConversionCampaignStatusNotice.mockReturnValueOnce(
			'AFTER_CONVERSION'
		);
		const { getByTestId } = render( <CampaignConversionReportsNotice /> );

		const notice = getByTestId( 'gla-campaign-conversion-reports-notice' );
		expect( notice ).toHaveTextContent(
			'Your existing campaigns have been upgraded to Performance Max'
		);
	} );
	it( 'Dismissed Notice', () => {
		getConversionCampaignStatusNotice.mockReturnValueOnce(
			'AFTER_CONVERSION'
		);
		const { queryByTestId, getByRole } = render(
			<CampaignConversionReportsNotice />
		);

		const closeButton = getByRole( 'button' );

		fireEvent.click( closeButton );

		const notice = queryByTestId(
			'gla-campaign-conversion-reports-notice'
		);

		expect( notice ).toBeFalsy();
	} );
	it( 'No campaign status', () => {
		getConversionCampaignStatusNotice.mockReturnValueOnce( null );
		const { queryByTestId } = render( <CampaignConversionReportsNotice /> );

		const notice = queryByTestId(
			'gla-campaign-conversion-reports-notice'
		);

		expect( notice ).toBeFalsy();
	} );
} );
