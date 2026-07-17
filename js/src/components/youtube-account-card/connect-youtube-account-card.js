/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import AppButton from '~/components/app-button';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import YouTubeMerchantTermsLink from './youtube-merchant-terms-link';
import './connect-youtube-account-card.scss';

/**
 * Clicking on the button to connect YouTube account.
 *
 * @event gla_youtube_account_connect_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-youtube'.
 */
/**
 * @fires gla_youtube_account_connect_button_click
 * @fires gla_documentation_link_click with `{ context: 'settings-connect-youtube-account-card', link_id: 'youtube-merchant-terms' }` and the URL.
 */
const ConnectYouTubeAccountCard = () => {
	const { createNotice } = useDispatchCoreNotices();

	const query = { next_page_name: 'setup-youtube' };
	const path = addQueryArgs( `${ API_NAMESPACE }/youtube/connect`, query );
	const [ fetchYouTubeConnect, { loading, data } ] = useApiFetchCallback( {
		path,
	} );

	const handleConnectClick = async () => {
		try {
			const d = await fetchYouTubeConnect();
			window.location.href = d.url;
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

	return (
		<AccountCard
			appearance={ APPEARANCE.YOUTUBE }
			description={
				<div className="gla-connect-youtube-account-card__description">
					<p>
						{ __(
							'Sign in to view your channels.',
							'google-listings-and-ads'
						) }
					</p>
					<YouTubeMerchantTermsLink />
				</div>
			}
			indicator={
				<AppButton
					isSecondary
					loading={ loading || data }
					eventName="gla_youtube_account_connect_button_click"
					eventProps={ { context: 'settings-youtube' } }
					onClick={ handleConnectClick }
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default ConnectYouTubeAccountCard;
