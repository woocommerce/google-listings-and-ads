/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard from '~/components/account-card';
import LoadingLabel from '~/components/loading-label';

/**
 * Renders indication that the user is in the process of creating or connecting a Google Ads account.
 *
 * @param {Object} props Component props.
 * @param {string} props.upsertingAction The action the user is performing.
 */
const UpsertingAccount = ( { upsertingAction } ) => {
	const isConnecting = upsertingAction === 'update';

	let title = __(
		'Creating a new Google Ads account',
		'google-listings-and-ads'
	);
	let indicatorLabel = __( 'Creating…', 'google-listings-and-ads' );

	if ( isConnecting ) {
		title = __(
			'Connecting your Google Ads account',
			'google-listings-and-ads'
		);
		indicatorLabel = __( 'Connecting…', 'google-listings-and-ads' );
	}

	return (
		<AccountCard
			className="gla-google-combo-service-account-card--ads"
			helper={ __(
				'This may take a few moments, please wait…',
				'google-listings-and-ads'
			) }
			indicator={ <LoadingLabel text={ indicatorLabel } /> }
			title={ title }
		/>
	);
};

export default UpsertingAccount;
