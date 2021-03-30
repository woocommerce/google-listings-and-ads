/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useNavigateAwayPromptEffect from '.~/hooks/useNavigateAwayPromptEffect';
import AdsStepper from './ads-stepper';
import isFormDirty from './is-form-dirty';
import SetupAdsTopBar from './top-bar';

const SetupAdsFormContent = ( props ) => {
	const { formProps } = props;

	const promptFn = useCallback( () => {
		return isFormDirty( formProps );
	}, [ formProps ] );

	useNavigateAwayPromptEffect(
		__(
			'You have unsaved campaign data. Are you sure you want to leave?',
			'google-listings-and-ads'
		),
		promptFn
	);

	return (
		<>
			<SetupAdsTopBar />
			<AdsStepper formProps={ formProps } />
		</>
	);
};

export default SetupAdsFormContent;
