/**
 * External dependencies
 */
import { fireEvent, render, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

/**
 * Internal dependencies
 */
import NameCampaignCell from '.~/reports/programs/name-campaign-cell';
import isCampaignConverted from '.~/utils/isCampaignConverted';
import { CAMPAIGN_TYPE } from '.~/constants';

jest.mock( '.~/utils/isCampaignConverted', () => jest.fn() );

describe( 'Notice Campaign Migration', () => {
	it( 'Converted', async () => {
		isCampaignConverted.mockReturnValueOnce( true );

		const row = {
			type: CAMPAIGN_TYPE.SHOPPING,
			name: 'shopping campaign',
		};

		const { getByText, findByText } = render(
			<NameCampaignCell { ...row } />
		);

		const campaignName = getByText( 'shopping campaign' );

		expect( campaignName ).toBeTruthy();

		fireEvent.mouseOver( campaignName );

		const tooltip = await findByText(
			'This campaign has been upgraded to Performance Max'
		);

		expect( tooltip ).toBeTruthy();
	} );
	it( 'PMax should not have tooltip', async () => {
		isCampaignConverted.mockReturnValueOnce( false );

		const row = {
			type: CAMPAIGN_TYPE.PERFORMANCE_MAX,
			name: 'pmax campaign',
		};

		const { getByText, queryByText } = render(
			<NameCampaignCell { ...row } />
		);

		const campaignName = getByText( 'pmax campaign' );

		expect( campaignName ).toBeTruthy();

		fireEvent.mouseOver( campaignName );

		await waitFor( () => {
			expect(
				queryByText(
					'This campaign has been upgraded to Performance Max'
				)
			).toBeFalsy();
		} );
	} );
} );
