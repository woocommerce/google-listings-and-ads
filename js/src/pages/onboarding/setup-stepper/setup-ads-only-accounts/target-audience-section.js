/**
 * External dependencies
 */
import { pick, noop } from 'lodash';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { targetAudienceFields } from '~/components/free-listings/choose-audience-section/constants';
import AppSpinner from '~/components/app-spinner';
import AdaptiveForm from '~/components/adaptive-form';
import ValidationErrors from '~/components/validation-errors';
import ChooseAudienceSection from '~/components/free-listings/choose-audience-section';

/**
 * @typedef {import('~/data/actions').TargetAudienceData } TargetAudienceData
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

/**
 * Renders the Target Audience section form for Ads only merchant setup flow.
 *
 * @param {Object} props
 * @param {TargetAudienceData} props.targetAudience Target audience value data to be initialed the form, if not given AppSpinner will be rendered.} param0
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
		if ( targetAudienceFields.includes( change.name ) ) {
			onTargetAudienceChange( pick( values, targetAudienceFields ) );
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
