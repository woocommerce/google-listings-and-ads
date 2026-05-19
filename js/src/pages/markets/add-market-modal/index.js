/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import MultiLingualPluginPrompt from './multilingual-plugin-prompt';
import MarketFields from '../market-fields';
import MarketForm from '../market-form';

const CONTEXT = 'add_market_modal';

/**
 * @typedef {import('~/data/actions').TargetAudienceData } TargetAudienceData
 * @typedef {import('~/data/actions').ShippingRate} ShippingRate
 * @typedef {import('~/data/actions').ShippingTime} ShippingTime
 */

/**
 * Event fired when the "Cancel" button in the AddMarketModal is clicked.
 * @event gla_cancel_button_clicked
 * @property {string} context The context in which the cancel button click happened, e.g. "add_market_modal".
 */

/**
 * Event fired when the "Add market" button in the AddMarketModal is clicked.
 * @event gla_add_new_market_button_clicked
 * @property {string} context The context in which the add market button click happened, e.g. "add_market_modal".
 */

/**
 * Modal component for adding a new market.
 * This component is rendered when the user clicks the "Add market" button on the markets page,
 * and it contains a form for entering the details of the new market.
 *
 * @fires gla_cancel_button_clicked when the cancel button is clicked with context of "add_market_modal"
 * @fires gla_add_new_market_button_clicked when the add market button is clicked with context of "add_market_modal"
 *
 * @param {Object} props
 * @param {Object} props.settings The settings object containing shipping_rate and other configurations.
 * @param {TargetAudienceData} props.targetAudience Target audience value data to be initialed the form, if not given AppSpinner will be rendered.
 * @param {() => void} props.onRequestClose Called when the user closes the modal.
 */
const AddMarketModal = ( {
	settings,
	targetAudience = { countries: [] },
	onRequestClose,
} ) => {
	const { code: currencyCode } = useStoreCurrency();

	const initialMarket = {
		countries: targetAudience.countries,
		language: targetAudience.language,
		currency: currencyCode,
	};

	// Non-multilingual store with manual shipping has no form fields to fill in —
	// it only shows the multilingual plugin prompt.
	const isManualNonMultilingual =
		! glaData.isMultiLingualStore &&
		settings?.shipping_rate === SHIPPING_RATE_METHOD.MANUAL;

	// Non-multilingual store with automatic shipping uses a simpler form where
	// the "Add market" button is disabled until the form is valid.
	const isAutomaticNonMultilingual =
		! glaData.isMultiLingualStore &&
		settings?.shipping_rate === SHIPPING_RATE_METHOD.AUTOMATIC;

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

				let buttons = [
					<AppButton
						key="close"
						variant={
							! isManualNonMultilingual ? 'tertiary' : 'primary'
						}
						onClick={ onRequestClose }
						disabled={ isSaving }
						eventName="gla_cancel_button_clicked"
						eventProps={ {
							context: CONTEXT,
						} }
					>
						{ __( 'Cancel', 'google-listings-and-ads' ) }
					</AppButton>,
				];

				if ( ! isManualNonMultilingual ) {
					buttons = [
						...buttons,
						<AppButton
							key="add-market"
							variant="primary"
							onClick={
								isAutomaticNonMultilingual
									? handleSubmit
									: handleSubmitClick
							}
							disabled={
								isAutomaticNonMultilingual && ! isValidForm
							}
							loading={ isSaving }
							eventName="gla_add_new_market_button_clicked"
							eventProps={ {
								context: CONTEXT,
							} }
						>
							{ __( 'Add market', 'google-listings-and-ads' ) }
						</AppButton>,
					];
				}

				return (
					<AppModal
						title={ __( 'Add market', 'google-listings-and-ads' ) }
						onRequestClose={ onRequestClose }
						buttons={ buttons }
					>
						<MarketFields />
						<MultiLingualPluginPrompt />
					</AppModal>
				);
			} }
		</MarketForm>
	);
};

export default AddMarketModal;
