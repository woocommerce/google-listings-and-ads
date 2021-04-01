/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import useNavigateAwayPromptEffect from '.~/hooks/useNavigateAwayPromptEffect';
import AdsStepper from './ads-stepper';
import isFormDirty from './is-form-dirty';
import SetupAdsTopBar from './top-bar';

const SetupAdsFormContent = ( props ) => {
	const { formProps } = props;
	// FIX: the form dirty checking should return `true` after successful creating the campaign,
	//      or it would be triggered when exiting the Google ads setup page
	const shouldPreventClose = isFormDirty( formProps );

	useNavigateAwayPromptEffect(
		__(
			'You have unsaved campaign data. Are you sure you want to leave?',
			'google-listings-and-ads'
		),
		shouldPreventClose
	);

	return (
		<>
			<SetupAdsTopBar />
			<AdsStepper formProps={ formProps } />
		</>
	);
};

export default SetupAdsFormContent;
