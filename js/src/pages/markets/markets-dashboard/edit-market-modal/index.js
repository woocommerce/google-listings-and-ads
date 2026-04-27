/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';

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
	return (
		<AppModal
			className="gla-edit-market-modal"
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
		</AppModal>
	);
};

export default EditMarketModal;
