/**
 * External dependencies
 */
import { isEqual } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Form } from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useAdminUrl from '.~/hooks/useAdminUrl';
import useNavigateAwayPromptEffect from '.~/hooks/useNavigateAwayPromptEffect';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import SetupAdsFormContent from './setup-ads-form-content';
import useSetupCompleteCallback from './useSetupCompleteCallback';
import validateForm from '.~/utils/paid-ads/validateForm';
import { recordLaunchPaidCampaignClickEvent } from '.~/utils/recordEvent';

/**
 * @fires gla_launch_paid_campaign_button_click on submit
 */
const SetupAdsForm = () => {
	const [ didFormChanged, setFormChanged ] = useState( false );
	const [ isSubmitted, setSubmitted ] = useState( false );
	const [ handleSetupComplete, isSubmitting ] = useSetupCompleteCallback();
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

		recordLaunchPaidCampaignClickEvent( amount, countryCodes );

		handleSetupComplete( amount, countryCodes, () => {
			setSubmitted( true );
		} );
	};

	const handleChange = ( _, values ) => {
		setFormChanged( ! isEqual( initialValues, values ) );
	};

	if ( ! targetAudience ) {
		return null;
	}

	return (
		<Form
			initialValues={ initialValues }
			validate={ validateForm }
			onChange={ handleChange }
			onSubmit={ handleSubmit }
		>
			{ ( formProps ) => {
				const mixedFormProps = {
					...formProps,
					// TODO: maybe move all API calls in useSetupCompleteCallback to ~./data
					isSubmitting,
				};
				return <SetupAdsFormContent formProps={ mixedFormProps } />;
			} }
		</Form>
	);
};

export default SetupAdsForm;
