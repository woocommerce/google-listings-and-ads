/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PRIMARY_MARKET_ID } from '../../constants';
import { useAppDispatch } from '~/data';
import { checkErrors } from '../../utils';
import AdaptiveForm from '~/components/adaptive-form';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import ValidationErrors from '~/components/validation-errors';
import EditPrimaryAudience from './edit-primary-audience';
import ShippingNotice from './shipping-notice';

/**
 * @typedef {import('~/data/actions').TargetAudienceData } TargetAudienceData
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

/**
 * Placeholder for the Edit Market modal.
 *
 * The follow-up task will replace this with a real form. For now, the modal
 * renders the selected market's name and a Close button so the open/close
 * wiring from `MarketDataViews` can be reviewed end-to-end.
 *
 * @param {Object} props
 * @param {{ id: string, label: string }} props.market The market being edited.
 * @param {TargetAudienceData} props.targetAudience Target audience value data to initialize the form with.
 * @param {() => void} props.onRequestClose Called when the user closes the modal.
 */
const EditMarketModal = ( { market, targetAudience, onRequestClose } ) => {
	const { updateMarket, invalidateResolution } = useAppDispatch();
	const { id } = market;
	const [ saving, setSaving ] = useState( false );
	const isPrimaryMarket = id === PRIMARY_MARKET_ID;
	const formRef = useRef();

	const handleSubmit = async ( values ) => {
		const { id: marketId, ...data } = values;

		setSaving( true );

		try {
			await updateMarket( marketId, data );
			invalidateResolution( 'getTargetAudience', [] );
			onRequestClose();
		} catch ( error ) {}

		setSaving( false );
	};

	const extendAdapter = ( formContext ) => {
		return {
			renderRequestedValidation( key ) {
				return (
					<ValidationErrors messages={ formContext.errors[ key ] } />
				);
			},
		};
	};

	const appModalTitle = isPrimaryMarket
		? __( 'Edit primary market', 'google-listings-and-ads' )
		: __( 'Edit market', 'google-listings-and-ads' );

	return (
		<AdaptiveForm
			ref={ formRef }
			initialValues={ {
				id,
				countries: targetAudience.countries || [],
			} }
			extendAdapter={ extendAdapter }
			validate={ checkErrors }
			onSubmit={ handleSubmit }
		>
			{ ( formContext ) => {
				const {
					isValidForm,
					handleSubmit: handleSave,
					isDirty,
				} = formContext;

				return (
					<AppModal
						title={ appModalTitle }
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
								onClick={ handleSave }
								disabled={ ! isValidForm || ! isDirty }
								loading={ saving }
							>
								{ __( 'Save', 'google-listings-and-ads' ) }
							</AppButton>,
						] }
					>
						{ isPrimaryMarket && <EditPrimaryAudience /> }

						<ShippingNotice />
					</AppModal>
				);
			} }
		</AdaptiveForm>
	);
};

export default EditMarketModal;
