/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {} from '@wordpress/element';

/**
 * Internal dependencies
 */
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AudienceSection from './audience-section';

const FormContent = ( props ) => {
	const { formProps } = props;

	return (
		<>
			<AudienceSection formProps={ formProps } />
			<StepContentFooter>
				<Button isPrimary>
					{ __( 'Launch campaign', 'google-listings-and-ads' ) }
				</Button>
			</StepContentFooter>
		</>
	);
};

export default FormContent;
