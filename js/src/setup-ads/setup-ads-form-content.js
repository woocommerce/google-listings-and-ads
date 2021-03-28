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

	useBeforeUnloadPromptEffect( shouldPreventClose );

	return (
		<>
			<SetupAdsTopBar formProps={ formProps } />
			<AdsStepper formProps={ formProps } />
		</>
	);
};

export default SetupAdsFormContent;
