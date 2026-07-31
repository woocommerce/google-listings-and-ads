/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppInputPriceControl from '~/components/app-input-price-control';
import useToggle from '~/hooks/useToggle';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import { EditMinimumOrderFormModal } from './minimum-order-form-modals';
import MinimumOrderInputControlLabelText from './minimum-order-input-control-label-text';
import './minimum-order-input-control.scss';

/**
 * @typedef { import("~/data/actions").CountryCode } CountryCode
 * @typedef { import("./typedefs").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * Input control to edit a minimum order group.
 *
 * The input control label contains a placeholder area for `button`, after the label text.
 * This is meant to display an "Edit" button.
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countryOptions Country options to be passed to EditMinimumOrderFormModal.
 * @param {MinimumOrderGroup} props.value Minimum order group value.
 * @param {(newGroup: MinimumOrderGroup) => void} props.onChange Called when minimum order group changes.
 * @param {() => void} props.onDelete Called when delete button in EditMinimumOrderFormModal is clicked.
 */
const MinimumOrderInputControl = ( props ) => {
	const { countryOptions, value, onChange, onDelete } = props;
	const { countries, threshold, currency } = value;
	const { values } = useAdaptiveFormContext();
	const [ isEditModalOpen, toggleEditModal ] = useToggle();

	const handleBlur = ( event, numberValue ) => {
		if ( numberValue === value.threshold ) {
			return;
		}

		onChange( {
			countries,
			threshold: numberValue > 0 ? numberValue : undefined,
			currency,
		} );
	};

	const shouldHideInput = ! values.offer_free_shipping;

	if ( shouldHideInput ) {
		return null;
	}

	return (
		<>
			<AppInputPriceControl
				className="gla-minimum-order-input-control"
				label={
					<div className="gla-minimum-order-input-control__label">
						<div className="gla-minimum-order-input-control__label_country">
							<MinimumOrderInputControlLabelText
								countries={ countries }
							/>
							<AppButton isTertiary onClick={ toggleEditModal }>
								{ __( 'Edit', 'google-listings-and-ads' ) }
							</AppButton>
						</div>
						<>{ `Cost (${ currency })` }</>
					</div>
				}
				value={ threshold }
				onBlur={ handleBlur }
			/>
			{ isEditModalOpen && (
				<EditMinimumOrderFormModal
					countryOptions={ countryOptions }
					initialValues={ value }
					onSubmit={ onChange }
					onDelete={ onDelete }
					onRequestClose={ toggleEditModal }
				/>
			) }
		</>
	);
};

export default MinimumOrderInputControl;
