/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';
import MultiLingualPluginPrompt from './multilingual-plugin-prompt';
import MarketFields from '../market-fields';
import MarketForm from '../market-form';
import ModalFooter from './modal-footer';
import usePrimaryMarketDetails from '../../hooks/usePrimaryMarketDetails';

/**
 * @typedef {import('~/data/actions').TargetAudienceData } TargetAudienceData
 * @typedef {import('~/data/actions').ShippingRate} ShippingRate
 * @typedef {import('~/data/actions').ShippingTime} ShippingTime
 */

/**
 * Modal component for adding a new market.
 * This component is rendered when the user clicks the "Add market" button on the markets page,
 * and it contains a form for entering the details of the new market.
 *
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
	const { data } = usePrimaryMarketDetails();

	const initialMarket = {
		countries: targetAudience.countries,
		language: data?.language || [],
		currency: data?.currency || [],
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
				<ModalFooter
					onCancel={ onRequestClose }
					settings={ settings }
				/>
			</MarketForm>
		</AppModal>
	);
};

export default AddMarketModal;
