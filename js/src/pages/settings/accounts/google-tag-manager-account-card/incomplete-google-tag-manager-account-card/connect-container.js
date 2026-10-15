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
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';
import AccountNameWithLink from '../account-name-with-link';
import AdsConversionDuplicateNotice from '../ads-conversion-duplicate-notice';
import SelectContainer from './select-container';
import NoContainerFound from './no-container-found';
import './connect-container.scss';

/**
 * Renders the detail for connecting a container to the already-connected account: the account, and
 * either the container selector (one or more containers exist) or the notice that none was found
 * (the account has zero containers — there's nothing to select). Both offer to create a container.
 *
 * @return {JSX.Element} The detail, or a loading spinner until the account and the containers list have resolved.
 */
export default function ConnectContainer() {
	const { account, hasFinishedResolution: hasResolvedAccount } =
		useGoogleTagManagerAccount();
	const { containers, hasFinishedResolution: hasResolvedContainers } =
		useGoogleTagManagerContainers();

	if ( ! hasResolvedAccount || ! hasResolvedContainers ) {
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
				{ containers?.length ? (
					<SelectContainer />
				) : (
					<NoContainerFound />
				) }
			</FlexItem>
		</Flex>
	);
}
