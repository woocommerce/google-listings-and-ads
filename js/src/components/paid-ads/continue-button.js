/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';

/**
 * Renders Continue button on paid ad campaign create and edit page.
 *
 * @param {Object} props Props
 * @param {Object} props.formProps Form props forwarded from `Form` component.
 * @param {Function} props.onClick Function to handle the continue button click.
 * @return {JSX.Element} The component.
 */
const ContinueButton = ( { formProps, onClick } ) => {
	return (
		<AppButton
			disabled={ ! formProps.isValidForm }
			onClick={ onClick }
			text={ __( 'Continue', 'google-listings-and-ads' ) }
			isPrimary
		/>
	);
};

export default ContinueButton;
