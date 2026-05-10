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
import Inputs from './inputs';
import useSettings from '~/hooks/useSettings';

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
	console.log( 'Current settings:', settings );

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
				country: null,
				flat_shipping_rate: null,
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
						<Inputs />
						<MultiLingualPluginPrompt />
					</AppModal>
				);
			} }
		</AdaptiveForm>
	);
};

export default AddMarketModal;
