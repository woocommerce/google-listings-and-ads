/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PRIMARY_MARKET_ID } from '../../constants';
import AdaptiveForm from '~/components/adaptive-form';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import ValidationErrors from '~/components/validation-errors';
import EditPrimaryFeed from './edit-primary-feed';
import AppSpinner from '~/components/app-spinner';

const checkErrors = ( values ) => {
	const errors = {};

	if ( values.countries.length === 0 ) {
		errors.countries = __(
			'Please select at least one country.',
			'google-listings-and-ads'
		);
	}

	return errors;
};

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
	const { id } = market;
	const isPrimaryMarket = id === PRIMARY_MARKET_ID;
	const formRef = useRef();
	const { targetAudience, getFinalCountries, loaded } =
		useTargetAudienceFinalCountryCodes();

	const handleValidate = ( values ) => {
		return checkErrors( values );
	};

	const extendAdapter = ( formContext ) => {
		return {
			audienceCountries: getFinalCountries( formContext.values ),
			renderRequestedValidation( key ) {
				return (
					<ValidationErrors messages={ formContext.errors[ key ] } />
				);
			},
		};
	};

	if ( ! loaded ) {
		return <AppSpinner />;
	}

	return (
		<AdaptiveForm
			ref={ formRef }
			initialValues={ {
				countries: targetAudience.countries || [],
			} }
			extendAdapter={ extendAdapter }
			validate={ handleValidate }
		>
			{ ( formContext ) => {
				const { isValidForm, handleSubmit, isDirty } = formContext;
				console.log( 'Form values:', formContext );

				return (
					<AppModal
						title={ __( 'Edit market', 'google-listings-and-ads' ) }
						onRequestClose={ onRequestClose }
						overflow="visible"
						buttons={ [
							<AppButton
								key="close"
								variant="tertiary"
								onClick={ onRequestClose }
							>
								{ __( 'Cancel', 'google-listings-and-ads' ) }
							</AppButton>,
							<AppButton
								key="save"
								variant="primary"
								onClick={ handleSubmit }
								disabled={ ! isValidForm || ! isDirty }
							>
								{ __( 'Save', 'google-listings-and-ads' ) }
							</AppButton>,
						] }
					>
						{ loaded && isPrimaryMarket && <EditPrimaryFeed /> }

						{ ! loaded && <AppSpinner /> }
					</AppModal>
				);
			} }
		</AdaptiveForm>
	);
};

export default EditMarketModal;
