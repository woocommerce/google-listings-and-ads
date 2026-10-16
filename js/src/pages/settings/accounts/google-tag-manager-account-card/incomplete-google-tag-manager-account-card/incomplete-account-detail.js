/**
 * External dependencies
 */
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCardTextDetail from '../../account-card-text-detail';
import AppSpinner from '~/components/app-spinner';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import AccountNameWithLink from '../account-name-with-link';
import AdsConversionDuplicateNotice from '../ads-conversion-duplicate-notice';
import ConnectContainer from './connect-container';
import PendingContainerNotice from './pending-container-notice';

/**
 * Renders the detail of the account card in the incomplete state: the already-connected account and
 * the Google Ads conflict notice, then either the notice for a container that was created but
 * couldn't be connected, or the way to connect one.
 *
 * @return {JSX.Element} The detail, or a loading spinner until the account has resolved.
 */
export default function IncompleteAccountDetail() {
	const { account, hasFinishedResolution } = useGoogleTagManagerAccount();

	if ( ! hasFinishedResolution ) {
		return <AppSpinner />;
	}

	return (
		<Flex direction="column" gap={ 4 }>
			<FlexItem>
				<AccountCardTextDetail>
					<AccountNameWithLink account={ account } />
				</AccountCardTextDetail>
			</FlexItem>
			<FlexItem>
				<AdsConversionDuplicateNotice />
			</FlexItem>
			<FlexItem>
				{ account?.pendingContainerId ? (
					<PendingContainerNotice />
				) : (
					<ConnectContainer />
				) }
			</FlexItem>
		</Flex>
	);
}
