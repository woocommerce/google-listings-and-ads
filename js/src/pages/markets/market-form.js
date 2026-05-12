/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { PRIMARY_MARKET_ID } from './constants';
import AdaptiveForm from '~/components/adaptive-form';
import ValidationErrors from '~/components/validation-errors';
import { checkErrors } from './utils/checkErrors';

/**
 * Form for creating/editing a market. This is a placeholder implementation to be used for testing the end-to-end flow of market creation/editing from the MarketDataViews component, and will be replaced with a real form in a follow-up task.
 *
 * @param {Object} props
 * @param {Object} props.initialMarket Initial values to populate the form with. Can be empty when creating a new market.
 * @param {Function} props.onSubmit Callback function to handle form submission.
 * @return {JSX.Element} The rendered form.
 */
const MarketForm = ( {
	initialMarket = {},
	onSubmit,
	...adaptiveFormProps
} ) => {
	const formRef = useRef();
	const { createMarket, updateMarket, invalidateResolution } =
		useAppDispatch();
	const [ isSaving, setIsSaving ] = useState( false );

	const extendAdapter = ( formContext ) => {
		return {
			isSaving,
			renderRequestedValidation( key ) {
				return (
					<ValidationErrors messages={ formContext.errors[ key ] } />
				);
			},
		};
	};

	const validate = ( values ) => {
		if ( values.id !== PRIMARY_MARKET_ID ) {
			return {};
		}
		return checkErrors( values );
	};

	const handleSubmit = async ( values ) => {
		const { id: marketId, offer_free_shipping, ...data } = values;
		if ( ! offer_free_shipping ) {
			data.free_shipping = null;
		}

		try {
			setIsSaving( true );

			if ( marketId ) {
				await updateMarket( marketId, data );
			} else {
				await createMarket( data );
			}

			invalidateResolution( 'getTargetAudience', [] );
			onSubmit();
		} catch ( error ) {
		} finally {
			setIsSaving( false );
		}
	};

	return (
		<AdaptiveForm
			ref={ formRef }
			initialValues={ initialMarket }
			extendAdapter={ extendAdapter }
			validate={ validate }
			onSubmit={ handleSubmit }
			{ ...adaptiveFormProps }
		/>
	);
};

export default MarketForm;
