/**
 * External dependencies
 */
import { fireEvent, render, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

/**
 * Internal dependencies
 */
import CampaignNameCell from '.~/reports/programs/campaign-name-cell';

describe( 'Notice Campaign Migration', () => {
	it( 'Converted', async () => {
		const row = {
			isConverted: true,
			name: 'shopping campaign',
		};

		const { getByText, findByText } = render(
			<CampaignNameCell { ...row } />
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
		const row = {
			isConverted: false,
			name: 'pmax campaign',
		};

		const { getByText, queryByText } = render(
			<CampaignNameCell { ...row } />
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
