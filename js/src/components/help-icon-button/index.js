/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconHelpOutline from 'gridicons/dist/help-outline';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import styles from './index.module.scss';

/**
 * "Help" button is clicked.
 *
 * @event gla_help_click
 * @property {string} context Indicates the place where the button is located, e.g. `setup-ads`.
 */

/**
 * Renders a button with a help icon and "Help" text.
 * Upon click, it will open documentation page in a new tab,
 * and call `gla_help_click` track event.
 *
 * @fires gla_help_click
 *
 * @param {Object} props Props
 * @param {string} props.eventContext Context to be used in `gla_help_click` track event.
 * @return {JSX.Element} The button.
 */
const HelpIconButton = ( { eventContext } ) => {
	return (
		<AppButton
			className={ styles.helpIconButton }
			eventName="gla_help_click"
			eventProps={ {
				context: eventContext,
			} }
			href="https://woocommerce.com/document/google-for-woocommerce/"
			target="_blank"
		>
			<GridiconHelpOutline />
			{ __( 'Help', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default HelpIconButton;
