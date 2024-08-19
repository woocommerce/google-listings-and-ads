/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import CampaignNameCell from '.~/reports/programs/campaign-name-cell';

describe( 'Notice Campaign Migration', () => {
	it( 'Converted', async () => {
		const user = userEvent.setup();
		const row = {
			isConverted: true,
			name: 'shopping campaign',
		};

		const { getByText, findByText } = render(
			<CampaignNameCell { ...row } />
		);

		const campaignName = getByText( 'shopping campaign' );

		expect( campaignName ).toBeTruthy();

		await user.hover( campaignName );

		const tooltip = await findByText(
			'This campaign has been upgraded to Performance Max'
		);

		expect( tooltip ).toBeTruthy();
	} );
	it( 'PMax should not have tooltip', async () => {
		const user = userEvent.setup();
		const row = {
			isConverted: false,
			name: 'pmax campaign',
		};

		const { getByText, queryByText } = render(
			<CampaignNameCell { ...row } />
		);

		const campaignName = getByText( 'pmax campaign' );

		expect( campaignName ).toBeTruthy();

		await user.hover( campaignName );

		expect(
			queryByText( 'This campaign has been upgraded to Performance Max' )
		).toBeFalsy();
	} );
} );
