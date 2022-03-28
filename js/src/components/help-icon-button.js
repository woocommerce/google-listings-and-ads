/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconHelpOutline from 'gridicons/dist/help-outline';

/**
 * Internal dependencies
 */
import AppIconButton from '.~/components/app-icon-button';

/**
 * An AppIconButton that renders GridiconHelpOutline icon and "Help" text.
 * Upon click, it will open documentation page in a new tab,
 * and call `gla_help_click` track event.
 *
 * @param {Object} props Props
 * @param {string} props.eventContext Context to be used in `gla_help_click` track event.
 * @return {import(".~/components/app-icon-button").default} The button.
 */
const HelpIconButton = ( props ) => {
	const { eventContext, ...rest } = props;

	return (
		<AppIconButton
			icon={ <GridiconHelpOutline /> }
			text={ __( 'Help', 'google-listings-and-ads' ) }
			href="https://docs.woocommerce.com/document/google-listings-and-ads/"
			target="_blank"
			eventName="gla_help_click"
			eventProps={ {
				context: eventContext,
			} }
			{ ...rest }
		/>
	);
};

export default HelpIconButton;
