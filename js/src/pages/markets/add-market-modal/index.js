/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SHIPPING_RATE_METHOD } from '~/constants';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import MultiLingualPluginPrompt from './multilingual-plugin-prompt';
import useSettings from '~/hooks/useSettings';
import MarketFields from '../market-fields';
import MarketForm from '../market-form';

/**
 * @typedef {import('~/data/actions').ShippingRate} ShippingRate
 * @typedef {import('~/data/actions').ShippingTime} ShippingTime
 */

/**
 * Placeholder for the Add Market modal.
 *
 * The follow-up task will replace this with a real form (country selector,
 * shipping configuration, validation, and a save handler that triggers a
 * markets refetch). For now, the modal renders a short placeholder body and a
 * Close button so the open / close wiring from `AddMarket` can be reviewed
 * end-to-end.
 *
 * @param {Object} props
 * @param {Array<ShippingRate>} props.shippingRates Shipping rates to pre-populate the form with.
 * @param {Array<ShippingTime>} props.shippingTimes Shipping times data, if not given AppSpinner will be rendered.
 * @param {() => void} props.onRequestClose Called when the user closes the modal.
 */
const AddMarketModal = ( { shippingRates, shippingTimes, onRequestClose } ) => {
	const { settings } = useSettings();

	return (
		<MarketForm
			initialMarket={ {
				offer_free_shipping:
					shippingRates?.[ 0 ]?.options?.free_shipping_threshold > 0,
				flat_shipping_rate: shippingRates?.[ 0 ]?.rate,
				shipping_country_rates: shippingRates, // for backwards compatibility with existing controls; to be removed once all controls are migrated to use flat_shipping_rate and offer_free_shipping directly.
				flat_shipping_min_time: shippingTimes?.[ 0 ]?.time ?? null,
				flat_shipping_max_time: shippingTimes?.[ 0 ]?.maxTime ?? null,
			} }
			onSubmit={ onRequestClose }
		>
			{ ( formContext ) => {
				const { adapter, isValidForm, handleSubmit } = formContext;
				const { isSaving } = adapter;

				let buttons = [
					<AppButton
						key="close"
						variant="tertiary"
						onClick={ onRequestClose }
						disabled={ isSaving }
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
							disabled={ ! isValidForm }
							onClick={ handleSubmit }
							loading={ isSaving }
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
