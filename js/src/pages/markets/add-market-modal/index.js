/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useStoreCurrency from '~/hooks/useStoreCurrency';
import AppModal from '~/components/app-modal';
import MultiLingualPluginPrompt from './multilingual-plugin-prompt';
import MarketFields from '../market-fields';
import MarketForm from '../market-form';
import AddMarketButtons from './add-market-buttons';

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

	return (
		<AppModal
			title={ __( 'Add market', 'google-listings-and-ads' ) }
			onRequestClose={ onRequestClose }
		>
			<MarketForm
				initialMarket={ initialMarket }
				onSubmit={ onRequestClose }
			>
				<MarketFields />
				<MultiLingualPluginPrompt />
				<AddMarketButtons
					onRequestClose={ onRequestClose }
					settings={ settings }
				/>
			</MarketForm>
		</AppModal>
	);
};

export default AddMarketModal;
