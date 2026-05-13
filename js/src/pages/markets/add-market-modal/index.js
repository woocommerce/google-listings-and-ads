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
import useSettings from '~/hooks/useSettings';
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
 * @param {Array<ShippingRate>} props.shippingRates Shipping rates to pre-populate the form with.
 * @param {Array<ShippingTime>} props.shippingTimes Shipping times data, if not given AppSpinner will be rendered.
 * @param {TargetAudienceData} props.targetAudience Target audience value data to be initialed the form, if not given AppSpinner will be rendered.
 * @param {() => void} props.onRequestClose Called when the user closes the modal.
 */
const AddMarketModal = ( {
	shippingRates,
	shippingTimes,
	targetAudience = { countries: [] },
	onRequestClose,
} ) => {
	const { settings } = useSettings();
	const { code: currencyCode } = useStoreCurrency();

	let initialMarket = {
		countries: targetAudience.countries,
		country: null,
		shipping_country_rates: shippingRates,
		flat_shipping_rate: null,
		offer_free_shipping: false,
		free_shipping_threshold: null,
		flat_shipping_min_time: null,
		flat_shipping_max_time: null,
		shipping_country_times: shippingTimes,
		language: targetAudience.language,
		currency: currencyCode,
		shipping_rate: settings?.shipping_rate,
		shipping_time: settings?.shipping_time,
	};

	if ( settings.shipping_rate === SHIPPING_RATE_METHOD.MANUAL ) {
		if ( ! glaData.isMultiLingualStore ) {
			initialMarket = {};
		} else if ( glaData.isMultiLingualStore ) {
			initialMarket = {
				country: null,
				language: targetAudience.language,
				currency: currencyCode,
			};
		}
	}

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
				];

				if ( settings?.shipping_rate !== SHIPPING_RATE_METHOD.MANUAL ) {
					buttons = [
						...buttons,
						<AppButton
							key="add-market"
							variant="primary"
							onClick={ handleSubmitClick }
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
