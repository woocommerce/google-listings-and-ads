/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PRIMARY_MARKET_ID } from '../constants';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import MarketForm from '../market-form';
import MarketFields from '../market-fields';

const CONTEXT = 'edit_market_modal';

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

	// `targetAudience.countries` is the authoritative list for the primary
	// market and may have been refreshed since `market` was read. Only override
	// for the primary — secondary markets carry their own single-country
	// `countries` array that must not be replaced with the primary's audience.
	const initialMarket = isPrimaryMarket
		? { ...market, countries: targetAudience.countries }
		: market;

	return (
		<MarketForm initialMarket={ initialMarket } onSubmit={ onRequestClose }>
			{ ( formContext ) => {
				const { adapter, isValidForm, handleSubmit } = formContext;
				const { isSaving } = adapter;

				const handleSubmitClick = ( event ) => {
					if ( isValidForm ) {
						return handleSubmit( event );
					}
					adapter.showValidation();
				};

				return (
					<AppModal
						title={ appModalTitle }
						onRequestClose={ onRequestClose }
						buttons={ [
							<AppButton
								key="close"
								variant="tertiary"
								onClick={ onRequestClose }
								disabled={ isSaving }
								eventName="gla_cancel_button_clicked"
								eventProps={ {
									context: CONTEXT,
								} }
							>
								{ __( 'Cancel', 'google-listings-and-ads' ) }
							</AppButton>,
							<AppButton
								key="save"
								variant="primary"
								onClick={ handleSubmitClick }
								loading={ isSaving }
								eventName="gla_save_button_clicked"
								eventProps={ {
									context: CONTEXT,
								} }
							>
								{ __( 'Save', 'google-listings-and-ads' ) }
							</AppButton>,
						] }
					>
						<MarketFields />
					</AppModal>
				);
			} }
		</MarketForm>
	);
};

export default EditMarketModal;
