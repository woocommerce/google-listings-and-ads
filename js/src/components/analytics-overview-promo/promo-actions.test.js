/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import PromoActions from './promo-actions';

jest.mock( '@wordpress/components', () => ( {
	Flex: ( { children } ) => <div>{ children }</div>,
	FlexItem: ( { children } ) => <div>{ children }</div>,
} ) );

jest.mock(
	'~/components/app-button',
	() =>
		( { children, href, onClick } ) =>
			href ? (
				<a href={ href } onClick={ onClick }>
					{ children }
				</a>
			) : (
				<button onClick={ onClick }>{ children }</button>
			)
);

jest.mock( '~/utils/urls', () => ( {
	getCreateCampaignUrl: jest.fn( () => '/create-campaign' ),
	getSetupAdsUrl: jest.fn( () => '/setup-ads' ),
} ) );

describe( 'PromoActions', () => {
	test( 'renders the not-ready CTA', () => {
		render(
			<PromoActions isGoogleAdsReady={ false } onDismiss={ jest.fn() } />
		);

		expect(
			screen.getByRole( 'link', { name: 'Get started' } )
		).toHaveAttribute( 'href', '/setup-ads' );
	} );

	test( 'renders the ready CTA', () => {
		render(
			<PromoActions isGoogleAdsReady={ true } onDismiss={ jest.fn() } />
		);

		expect(
			screen.getByRole( 'link', { name: 'Launch a campaign' } )
		).toHaveAttribute( 'href', '/create-campaign' );
	} );

	test( 'calls onDismiss when the Dismiss button is clicked', () => {
		const onDismiss = jest.fn();
		render(
			<PromoActions isGoogleAdsReady={ false } onDismiss={ onDismiss } />
		);

		fireEvent.click( screen.getByRole( 'button', { name: 'Dismiss' } ) );

		expect( onDismiss ).toHaveBeenCalled();
	} );
} );
