/**
 * External dependencies
 */
import { pick } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { TARGET_AUDIENCE_FIELDS } from '~/components/free-listings/choose-audience-section/constants';
import AdaptiveForm from '~/components/adaptive-form';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import ValidationErrors from '~/components/validation-errors';
import EditPrimaryFeed from './edit-primary-feed';
import AppSpinner from '~/components/app-spinner';

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
	const formRef = useRef();
	const { targetAudience, getFinalCountries, loaded } =
		useTargetAudienceFinalCountryCodes();

	const extendAdapter = ( formContext ) => {
		return {
			audienceCountries: getFinalCountries( formContext.values ),
			renderRequestedValidation( key ) {
				if ( formContext.adapter.requestedShowValidation ) {
					return (
						<ValidationErrors
							messages={ formContext.errors[ key ] }
						/>
					);
				}
				return null;
			},
		};
	};

	const handleChange = ( change, values ) => {
		if ( TARGET_AUDIENCE_FIELDS.includes( change.name ) ) {
			console.log( pick( values, TARGET_AUDIENCE_FIELDS ) );
		}
	};

	return (
		<AppModal
			title={ __( 'Edit market', 'google-listings-and-ads' ) }
			onRequestClose={ onRequestClose }
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
					onClick={ onRequestClose }
				>
					{ __( 'Save', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
		>
			{ loaded && (
				<AdaptiveForm
					ref={ formRef }
					initialValues={ {
						countries: targetAudience.countries || [],
					} }
					extendAdapter={ extendAdapter }
					onChange={ handleChange }
				>
					<EditPrimaryFeed />
				</AdaptiveForm>
			) }

			{ ! loaded && <AppSpinner /> }
		</AppModal>
	);
};

export default EditMarketModal;
