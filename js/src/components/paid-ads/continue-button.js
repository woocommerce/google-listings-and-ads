/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';

/**
 * Renders Continue button on paid ad campaign create and edit page.
 *
 * @param {Object} props Props
 * @param {Object} props.formProps Form props forwarded from `Form` component.
 * @param {Function} props.onClick Function to handle the continue button click.
 * @return {import(".~/components/app-button").default} The button.
 */
const ContinueButton = ( { formProps, onClick } ) => {
	return (
		<AppButton
			isPrimary
			text={ __( 'Continue', 'google-listings-and-ads' ) }
			disabled={ ! formProps.isValidForm }
			onClick={ onClick }
		/>
	);
};

export default ContinueButton;
