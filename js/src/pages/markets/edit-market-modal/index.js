/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PRIMARY_MARKET_ID } from '../constants';
import AppModal from '~/components/app-modal';
import MarketForm from '../market-form';
import MarketFields from '../market-fields';
import EditMarketButtons from './edit-market-buttons';

/**
 * @typedef {import('~/data/actions').TargetAudienceData } TargetAudienceData
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

/**
 * Event fired when the "Cancel" button in the EditMarketModal is clicked.
 * @event gla_cancel_button_clicked
 * @property {string} context The context in which the cancel button click happened, e.g. "edit_market_modal".
 */

/**
 * Event fired when the "Save" button in the EditMarketModal is clicked.
 * @event gla_save_button_clicked
 * @property {string} context The context in which the save button click happened, e.g. "edit_market_modal".
 */

/**
 * Modal component for editing an existing market.
 *
 * @fires gla_cancel_button_clicked when the cancel button is clicked with context of "edit_market_modal"
 * @fires gla_save_button_clicked when the save button is clicked with context of "edit_market_modal"
 *
 * @param {Object} props
 * @param {{ id: string, label: string }} props.market The market being edited.
 * @param {TargetAudienceData} props.targetAudience Target audience value data to initialize the form with.
 * @param {() => void} props.onRequestClose Called when the user closes the modal.
 */
const EditMarketModal = ( { market, targetAudience, onRequestClose } ) => {
	const isPrimaryMarket = market.id === PRIMARY_MARKET_ID;

	const appModalTitle = isPrimaryMarket
		? __( 'Edit primary market', 'google-listings-and-ads' )
		: sprintf(
				/* translators: %s is the name of the market being edited, e.g. "Europe". */
				__( 'Edit %s', 'google-listings-and-ads' ),
				market.label
		  );

	return (
		<AppModal title={ appModalTitle } onRequestClose={ onRequestClose }>
			<MarketForm
				initialMarket={ {
					...market,
					countries: targetAudience.countries,
				} }
				onSubmit={ onRequestClose }
			>
				<MarketFields />
				<EditMarketButtons onRequestClose={ onRequestClose } />
			</MarketForm>
		</AppModal>
	);
};

export default EditMarketModal;
