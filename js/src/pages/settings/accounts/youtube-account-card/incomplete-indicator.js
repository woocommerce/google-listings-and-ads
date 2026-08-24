/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AccountCardActions from '../account-card-actions';

/**
 * Renders the incomplete indicator for the YouTube account card, including the "Complete setup" button and the account actions menu.
 *
 * @param {Object} props Component props.
 * @param {() => void} props.handleFinishSetup Callback when the user clicks to complete the YouTube account setup.
 * @param {boolean} props.loading Indicates if the setup completion is in progress.
 * @param {() => void} props.onDisconnect Callback when the user clicks to disconnect the YouTube account.
 * @return {JSX.Element} The incomplete indicator for the YouTube account card.
 */
const IncompleteIndicator = ( {
	handleFinishSetup,
	loading,
	onDisconnect,
} ) => {
	return (
		<Flex>
			<FlexItem>
				<AppButton
					eventName="gla_link_youtube_account_button_click"
					eventProps={ { context: 'settings-youtube' } }
					onClick={ handleFinishSetup }
					disabled={ loading }
					loading={ loading }
					isSecondary
				>
					{ __( 'Complete setup', 'google-listings-and-ads' ) }
				</AppButton>
			</FlexItem>

			{ ! loading && (
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

export default IncompleteIndicator;
