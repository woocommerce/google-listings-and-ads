/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ServiceBasedContent from './service-based-content';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );
jest.mock( './confirm-supported-products-modal', () => ( {
	__esModule: true,
	default: function MockConfirmSupportedProductsModal() {
		return <div>Confirmation modal</div>;
	},
} ) );

describe( 'ServiceBasedContent', () => {
	it( 'shows the written explanation and opens the confirmation modal', async () => {
		const user = userEvent.setup();
		render( <ServiceBasedContent /> );

		expect(
			screen.getAllByText(
				/The Google Merchant Center connection is not available/
			)
		).not.toHaveLength( 0 );
		expect(
			screen.getByRole( 'link', { name: /Read more/ } )
		).toHaveAttribute(
			'href',
			'https://support.google.com/merchants/answer/6150006'
		);

		await user.click(
			screen.getByRole( 'button', {
				name: 'Confirm that I sell supported products',
			} )
		);

		expect( screen.getByText( 'Confirmation modal' ) ).toBeInTheDocument();
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_supported_products_confirmation_button_click',
			{
				context: 'settings-merchant-center-supported-products',
			}
		);
	} );
} );
