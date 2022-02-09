/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppInputPriceControl from '.~/components/app-input-price-control';
import AppButtonModalTrigger from '../app-button-modal-trigger';
import MinimumOrderInputLabelText from './minimum-order-input-label-text';
import EditMinimumOrderModal from './edit-minimum-order-modal';
import './minimum-order-input-control.scss';

const MinimumOrderInputControl = ( props ) => {
	const { value, onChange } = props;
	const { countries, threshold, currency } = value;

	const handleBlur = ( event, numberValue ) => {
		if ( numberValue === value.threshold ) {
			return;
		}

		onChange( {
			countries,
			threshold: numberValue,
			currency,
		} );
	};

	return (
		<AppInputPriceControl
			className="gla-minimum-order-input-control"
			label={
				<div className="gla-minimum-order-input-control__label">
					<MinimumOrderInputLabelText countries={ countries } />
					<AppButtonModalTrigger
						button={
							<Button
								className="gla-minimum-order-input-control__edit-button"
								isTertiary
							>
								{ __( 'Edit', 'google-listings-and-ads' ) }
							</Button>
						}
						modal={ <EditMinimumOrderModal /> }
					/>
				</div>
			}
			suffix={ currency }
			value={ threshold }
			onBlur={ handleBlur }
		/>
	);
};

export default MinimumOrderInputControl;
