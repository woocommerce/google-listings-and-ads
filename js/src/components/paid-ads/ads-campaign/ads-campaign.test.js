/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AdsCampaign from './ads-campaign';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn().mockReturnValue( {} ),
} ) );
jest.mock( '../budget-section', () => ( props ) => <>{ props.children }</> );
jest.mock( '~/components/paid-ads/billing-card', () => () => null );
jest.mock( '../campaign-preview', () => ( {
	CampaignPreviewCard: () => null,
} ) );
jest.mock( './faqs', () => () => null );
jest.mock( './paid-ads-features-section', () => () => null );
jest.mock( '../eu-regulations-section', () => () => null );
jest.mock( './cyo-incentive-picker', () => ( props ) => (
	<div>CYO incentive picker for { props.context }</div>
) );

describe( 'AdsCampaign', () => {
	it.each( [ 'setup-mc', 'setup-ads-only', 'setup-ads', 'create-ads' ] )(
		'renders the incentive picker for the %s context',
		( context ) => {
			render( <AdsCampaign headerTitle="Title" context={ context } /> );

			expect(
				screen.getByText( `CYO incentive picker for ${ context }` )
			).toBeInTheDocument();
		}
	);

	it( 'shows the Merchant Center setup text for the setup-mc context', () => {
		render( <AdsCampaign headerTitle="Title" context="setup-mc" /> );

		expect(
			screen.getByText(
				'You’re ready to set up a Performance Max campaign to drive more sales with ads. Your products will be included in the campaign after they’re approved. This won’t change your services campaign.'
			)
		).toBeInTheDocument();
	} );

	it( 'keeps the services text for the setup-ads-only context', () => {
		render( <AdsCampaign headerTitle="Title" context="setup-ads-only" /> );

		expect(
			screen.getByText(
				'You’re ready to set up a Performance Max campaign to drive more sales with ads. Your services will be included in the campaign after they’re approved.'
			)
		).toBeInTheDocument();
	} );

	it( 'does not render the incentive picker when editing a campaign', () => {
		render( <AdsCampaign headerTitle="Title" context="edit-ads" /> );

		expect(
			screen.queryByText( /CYO incentive picker/ )
		).not.toBeInTheDocument();
	} );
} );
