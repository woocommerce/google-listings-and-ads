/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { recordGlaEvent } from '~/utils/tracks';
import GetStartedCTA from './get-started-cta';

jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn(),
} ) );

jest.mock( '~/utils/urls', () => ( {
	...jest.requireActual( '~/utils/urls' ),
	getOnboardingUrl: jest.fn( () => '/onboarding' ),
} ) );

describe( 'GetStartedCTA Component', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'Renders a "Get started" link', () => {
		render( <GetStartedCTA /> );

		expect(
			screen.getByRole( 'link', { name: 'Get started' } )
		).toBeInTheDocument();
	} );

	test( 'href includes referrer_type and referrer_id for the channel visibility placement', () => {
		render( <GetStartedCTA /> );

		expect(
			screen.getByRole( 'link', { name: 'Get started' } )
		).toHaveAttribute(
			'href',
			'/onboarding?referrer_type=in_product_placements&referrer_id=channel-visibility-meta-box'
		);
	} );

	test( 'Fires gla_google_ads_promo_get_started_click event with the referrer-tagged href when clicked', () => {
		render( <GetStartedCTA /> );

		fireEvent.click( screen.getByRole( 'link', { name: 'Get started' } ) );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_google_ads_promo_get_started_click',
			{
				context: 'channel-visibility-meta-box',
				href: '/onboarding?referrer_type=in_product_placements&referrer_id=channel-visibility-meta-box',
			}
		);
	} );
} );
