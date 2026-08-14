/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import useYouTubeAccount from '~/hooks/useYouTubeAccount';
import ConnectedBadge from '../connected-badge';
import { getYouTubeChannelUrl } from '~/utils/urls';

/**
 * ConnectedYouTubeAccountCard component displays the connected YouTube account information.
 *
 * @return {JSX.Element} The rendered component.
 */
const ConnectedYouTubeAccountCard = () => {
	const { youTubeAccount } = useYouTubeAccount();

	const getDetail = () => {
		if ( youTubeAccount.channel?.id ) {
			return (
				<ExternalLink
					href={ getYouTubeChannelUrl( youTubeAccount.channel ) }
				>
					{ youTubeAccount?.channel?.label }
				</ExternalLink>
			);
		}
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.YOUTUBE }
			description={ __(
				'List your products on YouTube and track sales from your videos.',
				'google-listings-and-ads'
			) }
			indicator={ <ConnectedBadge /> }
			detail={ getDetail() }
			alignIndicator="top"
			alignIcon="top"
		/>
	);
};

export default ConnectedYouTubeAccountCard;
