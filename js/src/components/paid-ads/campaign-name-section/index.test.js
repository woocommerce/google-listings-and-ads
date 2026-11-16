/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import AdaptiveForm from '~/components/adaptive-form';
import validateCampaign from '~/components/paid-ads/validateCampaign';
import CampaignNameSection from '.';

const validate = ( values ) =>
	validateCampaign( values, { formatAmount: jest.fn() } );

function renderCampaignNameSection( campaignName, onChange = jest.fn() ) {
	return render(
		<AdaptiveForm
			initialValues={ { campaignName, level: 'recommended' } }
			validate={ validate }
			onChange={ onChange }
		>
			<CampaignNameSection />
		</AdaptiveForm>
	);
}

describe( 'CampaignNameSection', () => {
	it( 'Should render the title and help text', () => {
		renderCampaignNameSection( 'Campaign 2026-11-05 14:30:00' );

		expect(
			screen.getByText( 'Campaign name', {
				selector: '.gla-section-card-title',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				"This name helps you identify this campaign. Customers won't see this."
			)
		).toBeInTheDocument();
	} );

	it( "Should show the form's campaign name and update it on typing", async () => {
		const onChange = jest.fn();
		renderCampaignNameSection( 'Campaign 2026-11-05 14:30:00', onChange );

		const input = screen.getByRole( 'textbox', { name: 'Campaign name' } );
		expect( input ).toHaveValue( 'Campaign 2026-11-05 14:30:00' );

		await userEvent.clear( input );
		await userEvent.type( input, 'Holiday sale' );

		expect( input ).toHaveValue( 'Holiday sale' );
		expect( onChange ).toHaveBeenLastCalledWith(
			expect.objectContaining( { name: 'campaignName' } ),
			expect.objectContaining( { campaignName: 'Holiday sale' } ),
			true
		);
	} );

	it( 'Should show the character counter', () => {
		renderCampaignNameSection( 'Holiday kitchen sale' );

		expect( screen.getByText( '20/50 characters' ) ).toBeInTheDocument();
	} );

	it( 'Should show an error when the name is over 50 characters', async () => {
		renderCampaignNameSection( '' );

		const input = screen.getByRole( 'textbox', { name: 'Campaign name' } );
		await userEvent.type( input, 'a'.repeat( 51 ) );
		await userEvent.tab();

		expect( screen.getByText( '51/50 characters' ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				'Please make sure the campaign name is 50 characters or fewer.'
			)
		).toBeInTheDocument();
	} );
} );
