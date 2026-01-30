/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedIconLabel from '~/components/connected-icon-label';
import Section from '~/components/section';
import DisconnectAccount from './disconnect-account';
import AppButton from '~/components/app-button';
import useYouTubeSetupCompleteCallback from '~/hooks/useYouTubeSetupCompleteCallback';
import { YOUTUBE_ACCOUNT_STATUS } from '~/constants';

/**
 * @typedef { import('./youtube-account-card.js').YouTubeAccount } YouTubeAccount
 */

/**
 * Clicking on the button to link the YouTube account.
 *
 * @event gla_link_youtube_account_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-youtube'.
 */

/**
 * Component to display a connected YouTube account.
 * Detects if setup completion is needed via URL query (youtube=connected) and triggers it.
 *
 * @fires gla_link_youtube_account_button_click When the user clicks on the button to link the YouTube account.
 *
 * @param {Object} props
 * @param {YouTubeAccount} props.youTubeAccount The connected YouTube account.
 */
const ConnectedYouTubeAccountCard = ( { youTubeAccount } ) => {
	const [ handleFinishSetup, { loading, error } ] =
		useYouTubeSetupCompleteCallback();
	const shouldLinkYouTubeAccount =
		youTubeAccount.status === YOUTUBE_ACCOUNT_STATUS.INCOMPLETE;

	let accountCardProps = {
		description: youTubeAccount.channel.label,
		indicator: <ConnectedIconLabel />,
	};

	if ( shouldLinkYouTubeAccount ) {
		accountCardProps = {
			indicator: (
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
			),
			detail: error?.message ? (
				<Notice status="error" isDismissible={ false }>
					{ error.message }
				</Notice>
			) : undefined,
			description: __(
				'Your YouTube account is connected, but setup isn’t complete yet.',
				'google-listings-and-ads'
			),
		};
	}

	return (
		<AccountCard
			appearance={ APPEARANCE.YOUTUBE }
			expandedDetail
			{ ...accountCardProps }
		>
			<Section.Card.Footer>
				<DisconnectAccount />
			</Section.Card.Footer>
		</AccountCard>
	);
};

export default ConnectedYouTubeAccountCard;
