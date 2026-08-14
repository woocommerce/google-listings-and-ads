/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectButton from './connect-button';
import { recordGlaEvent } from '~/utils/tracks';

const YOUTUBE_MERCHANT_TERMS_URL = 'https://www.youtube.com/t/merchant_terms';

/**
 * Records a click on the YouTube Merchant Terms link.
 *
 * @fires gla_documentation_link_click with `{ context: 'settings-connect-youtube-account-card', link_id: 'youtube-merchant-terms' }` and the URL.
 */
function handleYouTubeMerchantTermsClick() {
	recordGlaEvent( 'gla_documentation_link_click', {
		context: 'settings-connect-youtube-account-card',
		link_id: 'youtube-merchant-terms',
		href: YOUTUBE_MERCHANT_TERMS_URL,
	} );
}

const ConnectYoutubeAccountCard = () => {
	return (
		<AccountCard
			appearance={ APPEARANCE.YOUTUBE }
			description={ __(
				'List your products on YouTube and track sales from your videos.',
				'google-listings-and-ads'
			) }
			indicator={ <ConnectButton /> }
			detail={
				<ExternalLink
					onClick={ handleYouTubeMerchantTermsClick }
					href={ YOUTUBE_MERCHANT_TERMS_URL }
				>
					{ __(
						'YouTube Merchant Terms',
						'google-listings-and-ads'
					) }
				</ExternalLink>
			}
			alignIndicator="top"
			alignIcon="top"
		/>
	);
};

export default ConnectYoutubeAccountCard;
