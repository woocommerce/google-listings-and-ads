/**
 * External dependencies
 */
import { pick, noop } from 'lodash';
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TARGET_AUDIENCE_FIELDS } from '~/components/free-listings/choose-audience-section/constants';
import AppSpinner from '~/components/app-spinner';
import AdaptiveForm from '~/components/adaptive-form';
import ValidationErrors from '~/components/validation-errors';
import ChooseAudienceSection from '~/components/free-listings/choose-audience-section';

/**
 * @typedef {import('~/data/actions').TargetAudienceData } TargetAudienceData
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

/**
 * Renders the Target Audience section form.
 *
 * To be used in onboarding and further editing.
 * Does not provide any save strategy, this is to be bound externally via `onTargetAudienceChange`.
 *
 * @param {Object} props
 * @param {TargetAudienceData} props.targetAudience Target audience value data to initialize the form, if not given AppSpinner will be rendered.
 * @param {(targetAudience: TargetAudienceData) => Array<CountryCode>} props.resolveFinalCountries Callback for this component to resolve the given `targetAudience` to the final list of countries.
 * @param {(targetAudience: TargetAudienceData) => void} [props.onTargetAudienceChange] Callback called with new data once target audience data is changed.
 * @param {boolean} [props.showValidation=false] When true, show validation errors when the form becomes invalid (e.g. no countries selected). Default hides validation unless explicitly triggered.
 */
const TargetAudienceSection = ( {
	targetAudience,
	resolveFinalCountries,
	onTargetAudienceChange = noop,
	showValidation = false,
} ) => {
	const formRef = useRef();
	const adapterRef = useRef();

	if ( ! targetAudience ) {
		return <AppSpinner />;
	}

	const validate = ( values ) => {
		const errors = {};
		if (
			values.location === 'selected' &&
			( ! values.countries || values.countries.length === 0 )
		) {
			errors.countries = __(
				'Please select at least one country.',
				'google-listings-and-ads'
			);
		}
		return errors;
	};

	const handleChange = ( change, values ) => {
		if ( ! TARGET_AUDIENCE_FIELDS.includes( change.name ) ) {
			return;
		}

		if (
			showValidation &&
			values.location === 'selected' &&
			( ! values.countries || values.countries.length === 0 )
		) {
			adapterRef.current?.showValidation?.();
		}
		onTargetAudienceChange( pick( values, TARGET_AUDIENCE_FIELDS ) );
	};

	const extendAdapter = ( formContext ) => {
		adapterRef.current = formContext.adapter;
		return {
			audienceCountries: resolveFinalCountries( formContext.values ),
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

	return (
		<AdaptiveForm
			ref={ formRef }
			initialValues={ {
				locale: targetAudience.locale,
				language: targetAudience.language,
				location: targetAudience.location,
				countries: targetAudience.countries || [],
			} }
			extendAdapter={ extendAdapter }
			onChange={ handleChange }
			validate={ validate }
		>
			<ChooseAudienceSection />
		</AdaptiveForm>
	);
};

export default TargetAudienceSection;
