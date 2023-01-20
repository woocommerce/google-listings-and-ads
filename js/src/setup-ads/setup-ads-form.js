/**
 * External dependencies
 */
import { isEqual } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import useAdminUrl from '.~/hooks/useAdminUrl';
import useNavigateAwayPromptEffect from '.~/hooks/useNavigateAwayPromptEffect';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import useAdsSetupCompleteCallback from '.~/hooks/useAdsSetupCompleteCallback';
import AdaptiveForm from '.~/components/adaptive-form';
import AdsStepper from './ads-stepper';
import SetupAdsTopBar from './top-bar';
import validateCampaign from '.~/components/paid-ads/validateCampaign';

/**
 * @fires gla_launch_paid_campaign_button_click on submit
 */
const SetupAdsForm = () => {
	const [ didFormChanged, setFormChanged ] = useState( false );
	const [ isSubmitted, setSubmitted ] = useState( false );
	const [ handleSetupComplete, isSubmitting ] = useAdsSetupCompleteCallback();
	const adminUrl = useAdminUrl();
	const { data: targetAudience } = useTargetAudienceFinalCountryCodes();

	const initialValues = {
		amount: 0,
		countryCodes: targetAudience,
	};

	useEffect( () => {
		if ( isSubmitted ) {
			// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
			const nextPath = getNewPath(
				{ guide: 'campaign-creation-success' },
				'/google/dashboard'
			);
			window.location.href = adminUrl + nextPath;
		}
	}, [ isSubmitted, adminUrl ] );

	const shouldPreventLeave = didFormChanged && ! isSubmitted;

	useNavigateAwayPromptEffect(
		__(
			'You have unsaved campaign data. Are you sure you want to leave?',
			'google-listings-and-ads'
		),
		shouldPreventLeave
	);

	const handleSubmit = ( values ) => {
		const { amount, countryCodes } = values;

		recordEvent( 'gla_launch_paid_campaign_button_click', {
			audiences: countryCodes.join( ',' ),
			budget: amount,
		} );

		handleSetupComplete( amount, countryCodes, () => {
			setSubmitted( true );
		} );
	};

	const handleChange = ( _, values ) => {
		const args = [ initialValues, values ].map(
			( { countryCodes, ...v } ) => {
				v.countrySet = new Set( countryCodes );
				return v;
			}
		);

		setFormChanged( ! isEqual( ...args ) );
	};

	if ( ! targetAudience ) {
		return null;
	}

	return (
		<AdaptiveForm
			initialValues={ initialValues }
			validate={ validateCampaign }
			onChange={ handleChange }
			onSubmit={ handleSubmit }
		>
			{ ( formProps ) => {
				const mixedFormProps = {
					...formProps,
					// TODO: maybe move all API calls in useSetupCompleteCallback to ~./data
					isSubmitting,
				};
				return (
					<>
						<SetupAdsTopBar />
						<AdsStepper formProps={ mixedFormProps } />
					</>
				);
			} }
		</AdaptiveForm>
	);
};

export default SetupAdsForm;
