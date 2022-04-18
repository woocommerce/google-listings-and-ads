/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';
import { Icon, plugins as pluginsIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import useJetpackAccount from '.~/hooks/useJetpackAccount';
import { getSettingsUrl } from '.~/utils/urls';
import AppSpinner from '.~/components/app-spinner';
import AccountCard from '.~/components/account-card';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import { ConnectWPComAccountCard } from '.~/components/wpcom-account-card';
import LinkedAccountsSectionWrapper from '../linked-accounts-section-wrapper';
import {
	lockInReconnection,
	unlockFromReconnection,
} from '../reconnectionLock';
import './reconnect-wpcom-account.scss';

export default function ReconnectWPComAccount() {
	const { jetpack } = useJetpackAccount();
	const isConnected = jetpack?.active === 'yes';

	useEffect( () => {
		if ( isConnected ) {
			getHistory().replace( getSettingsUrl() );
			unlockFromReconnection();
		} else {
			lockInReconnection();
		}
	}, [ isConnected ] );

	if ( ! jetpack ) {
		return <AppSpinner />;
	}

	return (
		<LinkedAccountsSectionWrapper>
			<VerticalGapLayout size="large">
				<AccountCard
					className="gla-wpcom-connection-lost-card"
					isBorderless
					size="small"
					icon={ <Icon icon={ pluginsIcon } size={ 24 } /> }
					title={ __(
						'Your WordPress.com account has been disconnected.',
						'google-listings-and-ads'
					) }
					helper={ __(
						'Connect your WordPress.com account to ensure your products stay listed on Google. If you do not re-connect, your products can’t be automatically synced to Google, and any existing listings may be removed from Google.',
						'google-listings-and-ads'
					) }
				/>
				<ConnectWPComAccountCard />
			</VerticalGapLayout>
		</LinkedAccountsSectionWrapper>
	);
}
