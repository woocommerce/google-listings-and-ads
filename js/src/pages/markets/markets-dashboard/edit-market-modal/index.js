/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppDocumentationLink from '~/components/app-documentation-link';
import AppModal from '~/components/app-modal';
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import useSettings from '~/hooks/useSettings';
import { GOOGLE_MERCHANT_CENTER_URL } from '~/pages/markets/constants';
import './index.scss';

/**
 * Placeholder for the Edit Market modal.
 *
 * The follow-up task will replace this with a real form. For now, the modal
 * renders the selected market's name and a Close button so the open/close
 * wiring from `MarketDataViews` can be reviewed end-to-end.
 *
 * @param {Object} props
 * @param {{ id: string, label: string }} props.market The market being edited.
 * @param {() => void} props.onRequestClose Called when the user closes the modal.
 */
const EditMarketModal = ( { market, onRequestClose } ) => {
	const { settings } = useSettings();
	const showShippingNotice =
		! glaData.isMultiLingualStore &&
		settings?.shipping_rate === SHIPPING_RATE_METHOD.MANUAL;

	return (
		<AppModal
			title={ __( 'Edit market', 'google-listings-and-ads' ) }
			onRequestClose={ onRequestClose }
			buttons={ [
				<AppButton
					key="close"
					variant="tertiary"
					onClick={ onRequestClose }
				>
					{ __( 'Close', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		>
			<p>
				{ sprintf(
					// translators: %s is the name of the market being edited.
					__( 'Editing %s.', 'google-listings-and-ads' ),
					market.label
				) }
			</p>
			{ showShippingNotice && (
				<Notice
					className="gla-edit-market-modal__notice"
					status="info"
					isDismissible={ false }
				>
					{ createInterpolateElement(
						__(
							'Shipping is managed in Google Merchant Center. Configure shipping rates and times for each currency in your <link>Merchant Center account</link>.',
							'google-listings-and-ads'
						),
						{
							link: (
								<AppDocumentationLink
									context="edit-market-modal"
									linkId="shipping-notice-merchant-center"
									href={ GOOGLE_MERCHANT_CENTER_URL }
								/>
							),
						}
					) }
				</Notice>
			) }
		</AppModal>
	);
};

export default EditMarketModal;
