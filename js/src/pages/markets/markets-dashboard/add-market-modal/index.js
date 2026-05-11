/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { checkErrors } from '../../utils';
import { SHIPPING_RATE_METHOD } from '~/constants';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import AdaptiveForm from '~/components/adaptive-form';
import MultiLingualPluginPrompt from './multilingual-plugin-prompt';
import ValidationErrors from '~/components/validation-errors';
import useSettings from '~/hooks/useSettings';
import useShippingRates from '~/hooks/useShippingRates';
import MarketFields from './market-fields';

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
 * @param {() => void} props.onRequestClose Called when the user closes the modal.
 */
const AddMarketModal = ( { onRequestClose } ) => {
	const formRef = useRef();
	const { settings } = useSettings();
	const { data: shippingRates } = useShippingRates();

	const extendAdapter = ( formContext ) => {
		return {
			renderRequestedValidation( key ) {
				return (
					<ValidationErrors messages={ formContext.errors[ key ] } />
				);
			},
		};
	};

	const handleSubmit = async ( values ) => {
		console.log( 'Submitting form with values:', values );
	};

	return (
		<AdaptiveForm
			ref={ formRef }
			initialValues={ {
				countries: [], // @TODO: to remove since checkErrors depends on it for now.
				offer_free_shipping:
					shippingRates?.[ 0 ]?.options?.free_shipping_threshold > 0,
				flat_shipping_rate: shippingRates?.[ 0 ]?.rate,
				shipping_country_rates: shippingRates, // for backwards compatibility with existing controls; to be removed once all controls are migrated to use flat_shipping_rate and offer_free_shipping directly.
			} }
			extendAdapter={ extendAdapter }
			validate={ checkErrors }
			onSubmit={ handleSubmit }
		>
			{ ( formContext ) => {
				let buttons = [
					<AppButton
						key="close"
						variant="tertiary"
						onClick={ onRequestClose }
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
							onClick={ formContext.handleSubmit }
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
		</AdaptiveForm>
	);
};

export default AddMarketModal;
