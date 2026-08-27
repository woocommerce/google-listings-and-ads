/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import ConnectedBadge from '../connected-badge';
import AccountCardActions from '../account-card-actions';

/**
 * Clicking on the button to link the YouTube account.
 *
 * @event gla_link_youtube_account_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-youtube'.
 */

/**
 * Renders the indicator for the YouTube account card, including the "Complete setup" button or the connected badge, and the account actions menu.
 *
 * @fires gla_link_youtube_account_button_click When the user clicks on the button to link the YouTube account.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.handleFinishSetup Callback when the user clicks to complete the YouTube account setup.
 * @param {boolean} props.isConnected Indicates if the YouTube account is connected.
 * @param {boolean} props.isLoading Indicates if the setup completion is in progress.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the YouTube account.
 * @return {JSX.Element} The indicator for the YouTube account card.
 */
const Indicator = ( {
	handleFinishSetup,
	isConnected,
	isLoading,
	onDisconnect,
} ) => {
	return (
		<Flex>
			<FlexItem>
				{ ! isConnected && (
					<AppButton
						disabled={ isLoading }
						eventName="gla_link_youtube_account_button_click"
						eventProps={ { context: 'settings-youtube' } }
						loading={ isLoading }
						onClick={ handleFinishSetup }
						isSecondary
					>
						{ __( 'Complete setup', 'google-listings-and-ads' ) }
					</AppButton>
				) }

				{ isConnected && <ConnectedBadge /> }
			</FlexItem>

			{ ! isLoading && (
				<FlexItem>
					<AccountCardActions
						accountTitle={ __(
							'YouTube',
							'google-listings-and-ads'
						) }
						onDisconnect={ onDisconnect }
					/>
				</FlexItem>
			) }
		</Flex>
	);
};

export default Indicator;
