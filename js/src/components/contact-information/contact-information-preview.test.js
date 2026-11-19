/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ContactInformationPreview from './contact-information-preview';
import useStoreAddress from '~/hooks/useStoreAddress';

jest.mock( '~/hooks/useStoreAddress', () => jest.fn() );

describe( 'ContactInformationPreview', () => {
	it( 'should render the missing store address notice as one sentence followed by a "Learn more" link', () => {
		useStoreAddress.mockReturnValue( {
			loaded: true,
			data: {
				isAddressFilled: false,
				isMCAddressDifferent: false,
			},
		} );

		render( <ContactInformationPreview /> );

		const link = screen.getByRole( 'link', { name: 'Learn more' } );

		expect( link ).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information'
		);
		expect( link.parentElement ).toHaveTextContent(
			'Google requires the store address for all stores using Google Merchant Center. Learn more',
			{ normalizeWhitespace: false }
		);
	} );
} );
