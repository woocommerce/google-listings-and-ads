/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { Form } from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import SetupAdsFormContent from './setup-ads-form-content';
import useSetupCompleteCallback from './useSetupCompleteCallback';

// when amount is null or undefined in an onChange callback,
// it will cause runtime error with the Form component.
const initialValues = {
	amount: 0,
	country: [],
};

const SetupAdsForm = () => {
	const [ isSubmitted, setSubmitted ] = useState( false );
	const [ handleSetupComplete, isSubmitting ] = useSetupCompleteCallback();

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	useEffect( () => {
		if ( isSubmitted ) {
			// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
			const nextPath = getNewPath(
				{ guide: 'campaign-creation-success' },
				'/google/dashboard'
			);
			window.location.href = `/wp-admin/${ nextPath }`;
		}
	}, [ isSubmitted ] );

	return (
		<Form
			initialValues={ initialValues }
			validate={ handleValidate }
			onSubmitCallback={ ( values ) => {
				const { amount, country: countryArr } = values;
				const country = countryArr && countryArr[ 0 ];
				handleSetupComplete( amount, country, () => {
					setSubmitted( true );
				} );
			} }
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
