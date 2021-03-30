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
	const shouldPreventClose = isFormDirty( formProps );

	useNavigateAwayPromptEffect(
		shouldPreventClose,
		__(
			'You have unsaved campaign data. Are you sure you want to leave?',
			'google-listings-and-ads'
		)
	);

	return (
		<>
			<SetupAdsTopBar />
			<AdsStepper formProps={ formProps } />
		</>
	);
};

export default SetupAdsFormContent;
