/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AdsStepper from './ads-stepper';
import isFormTouched from './is-form-touched';
import SetupAdsTopBar from './top-bar';

const SetupAdsFormContent = ( props ) => {
	const { formProps } = props;
	const shouldPreventClose = isFormTouched( formProps );

	useEffect( () => {
		const eventListener = ( e ) => {
			// If you prevent default behavior in Mozilla Firefox prompt will always be shown.
			e.preventDefault();

			// Chrome requires returnValue to be set.
			e.returnValue = '';
		};

		if ( shouldPreventClose ) {
			window.addEventListener( 'beforeunload', eventListener );
		}

		return () => {
			window.removeEventListener( 'beforeunload', eventListener );
		};
	}, [ shouldPreventClose ] );

	return (
		<>
			<SetupAdsTopBar formProps={ formProps } />
			<AdsStepper formProps={ formProps } />
		</>
	);
};

export default SetupAdsFormContent;
