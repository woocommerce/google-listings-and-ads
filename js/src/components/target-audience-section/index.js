/**
 * External dependencies
 */
import { pick, noop } from 'lodash';
import { useRef } from '@wordpress/element';

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
 * To be used in onboarding and settings page when the user is not connected to Google MC.
 * No save functionality, this is to be bound externally via `onTargetAudienceChange`.
 *
 * @param {Object} props
 * @param {TargetAudienceData} props.targetAudience Target audience value data to initialize the form, if not given AppSpinner will be rendered.
 * @param {(targetAudience: TargetAudienceData) => Array<CountryCode>} props.resolveFinalCountries Callback for this component to resolve the given `targetAudience` to the final list of countries.
 * @param {(targetAudience: TargetAudienceData) => void} [props.onTargetAudienceChange] Callback called with new data once target audience data is changed.
 */
const TargetAudienceSection = ( {
	targetAudience,
	resolveFinalCountries,
	onTargetAudienceChange = noop,
} ) => {
	const formRef = useRef();

	if ( ! targetAudience ) {
		return <AppSpinner />;
	}

	const handleChange = ( change, values ) => {
		if ( TARGET_AUDIENCE_FIELDS.includes( change.name ) ) {
			onTargetAudienceChange( pick( values, TARGET_AUDIENCE_FIELDS ) );
		}
	};

	const extendAdapter = ( formContext ) => {
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
		>
			<ChooseAudienceSection />
		</AdaptiveForm>
	);
};

export default TargetAudienceSection;
