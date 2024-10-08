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
 * @param {Function} props.handleContinueClick Function to handle the continue button click.
 * @return {import(".~/components/paid-ads/continue-button").default} The button.
 */
const ContinueButton = ( { formProps, handleContinueClick } ) => {
	return (
		<AppButton
			isPrimary
			text={ __( 'Continue', 'google-listings-and-ads' ) }
			disabled={ ! formProps.isValidForm }
			onClick={ () => handleContinueClick() }
		/>
	);
};

export default ContinueButton;
