/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppDocumentationLink from '~/components/app-documentation-link';
import { recordGlaEvent } from '~/utils/tracks';
import ConfirmSupportedProductsModal from './confirm-supported-products-modal';
import {
	SUPPORTED_PRODUCTS_CONTEXT,
	SUPPORTED_PRODUCTS_POLICY_URL,
} from './constants';
import './service-based-content.scss';

/**
 * The "Confirm that I sell supported products" button is clicked from Settings > Accounts.
 *
 * @event gla_supported_products_confirmation_button_click
 * @property {string} context Page context. Always `settings-merchant-center-supported-products`.
 */

/**
 * Explains why the Merchant Center connection is unavailable for service-based
 * merchants and lets them confirm they sell supported products.
 *
 * @fires gla_supported_products_confirmation_button_click with `{ context: 'settings-merchant-center-supported-products' }`
 * @fires gla_documentation_link_click with `{ context: 'settings-merchant-center-supported-products', link_id: 'unsupported-product-types' }` and the URL.
 *
 * @return {JSX.Element} Service-based merchant explanation and confirmation action.
 */
export default function ServiceBasedContent() {
	const [ isModalOpen, setModalOpen ] = useState( false );

	const handleOpen = () => {
		recordGlaEvent( 'gla_supported_products_confirmation_button_click', {
			context: SUPPORTED_PRODUCTS_CONTEXT,
		} );
		setModalOpen( true );
	};
	const handleClose = () => {
		setModalOpen( false );
	};

	return (
		<div className="gla-service-based-content">
			{ isModalOpen && (
				<ConfirmSupportedProductsModal onRequestClose={ handleClose } />
			) }
			<Notice isDismissible={ false }>
				{ createInterpolateElement(
					__(
						'The Google Merchant Center connection is not available, as your store sells services or digital products that Google does not support. <link>Read more</link> about unsupported product types.',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context={ SUPPORTED_PRODUCTS_CONTEXT }
								linkId="unsupported-product-types"
								href={ SUPPORTED_PRODUCTS_POLICY_URL }
							/>
						),
					}
				) }
			</Notice>
			<p>
				{ __(
					'If the digital products you sell are supported by Google, confirm below to enable the Google Merchant Center connection.',
					'google-listings-and-ads'
				) }
			</p>
			<AppButton onClick={ handleOpen } isSecondary>
				{ __(
					'Confirm that I sell supported products',
					'google-listings-and-ads'
				) }
			</AppButton>
		</div>
	);
}
