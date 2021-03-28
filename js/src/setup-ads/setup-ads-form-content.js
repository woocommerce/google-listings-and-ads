/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import useBeforeUnloadPromptEffect from '.~/hooks/useBeforeUnloadPromptEffect';
import AdsStepper from './ads-stepper';
import isFormDirty from './is-form-dirty';
import SetupAdsTopBar from './top-bar';

const SetupAdsFormContent = ( props ) => {
	const { formProps } = props;
	const shouldPreventClose = isFormDirty( formProps );

	useBeforeUnloadPromptEffect(
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
