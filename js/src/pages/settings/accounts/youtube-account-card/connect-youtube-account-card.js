/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { recordGlaEvent } from '~/utils/tracks';
import AppButton from '~/components/app-button';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import './connect-youtube-account-card.scss';

/**
 * Clicking on the button to connect YouTube account.
 *
 * @event gla_youtube_account_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-youtube'.
 */

const TERMS_URL = 'https://www.youtube.com/t/merchant_terms';

/**
 * @fires gla_youtube_account_connect_button_click
 * @fires gla_documentation_link_click with `{ context: 'settings-connect-youtube-account-card', link_id: 'youtube-merchant-terms' }` and the URL.
 * @return {JSX.Element} YouTube connection card.
 */
const ConnectYouTubeAccountCard = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { hasGoogleMCConnection } = useGoogleMCAccount();
	const disabled = ! hasGoogleMCConnection;
	const disabledReason = __(
		'Connect a Google Merchant Center account before connecting YouTube.',
		'google-listings-and-ads'
	);
	const connectButtonLabel = __( 'Connect', 'google-listings-and-ads' );

	const query = { next_page_name: 'setup-youtube' };
	const path = addQueryArgs( `${ API_NAMESPACE }/youtube/connect`, query );
	const [ fetchYouTubeConnect, { loading, data } ] = useApiFetchCallback( {
		path,
	} );

	const handleConnectClick = async () => {
		try {
			const response = await fetchYouTubeConnect();
			window.location.href = response.url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your YouTube account. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	const handleClick = () => {
		recordGlaEvent( 'gla_documentation_link_click', {
			context: 'settings-connect-youtube-account-card',
			link_id: 'youtube-merchant-terms',
			href: TERMS_URL,
		} );
	};

	const connectButton = (
		<AppButton
			// Show spinner while the API request is in progress or while the user is being redirected to YouTube for authentication.
			loading={ loading || !! data }
			disabled={ disabled }
			__experimentalIsFocusable={ disabled }
			showTooltip={ disabled }
			label={ disabled ? disabledReason : undefined }
			aria-label={ connectButtonLabel }
			describedBy={ disabled ? disabledReason : undefined }
			eventName="gla_youtube_account_connect_button_click"
			eventProps={ { context: 'settings-youtube' } }
			onClick={ handleConnectClick }
			isSecondary
		>
			{ connectButtonLabel }
		</AppButton>
	);

	return (
		<AccountCard
			appearance={ APPEARANCE.YOUTUBE }
			helper={ disabled ? <span>{ disabledReason }</span> : undefined }
			description={
				<div className="gla-connect-youtube-account-card__description">
					<p>
						{ __(
							'List your products on YouTube and track sales from your videos.',
							'google-listings-and-ads'
						) }
					</p>
					<ExternalLink onClick={ handleClick } href={ TERMS_URL }>
						{ __(
							'YouTube Merchant Terms',
							'google-listings-and-ads'
						) }
					</ExternalLink>
				</div>
			}
			indicator={ connectButton }
		/>
	);
};

export default ConnectYouTubeAccountCard;
