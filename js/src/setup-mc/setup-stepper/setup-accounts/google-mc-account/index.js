/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../../../wcdl/section';
import DisabledDiv from '../../../../components/disabled-div';
import CreateAccountCard from './create-account-card';
import ConnectMCCard from './connect-mc-card';
import DisabledCard from './disabled-card';

const GoogleMCAccount = ( props ) => {
	const { disabled = false } = props;

	// TODO: check whether user already has MC Account.
	const hasMCAccount = true;

	let card;
	if ( disabled ) {
		card = <DisabledCard />;
	} else if ( hasMCAccount ) {
		card = <ConnectMCCard />;
	} else {
		card = <CreateAccountCard />;
	}

	return (
		<DisabledDiv disabled={ disabled }>
			<Section
				title={ __(
					'Google Merchant Center account',
					'google-listings-and-ads'
				) }
				description={ __(
					'WooCommerce products synced to your Merchant Center product feed will allow you to list your products on Google.',
					'google-listings-and-ads'
				) }
			>
				{ card }
			</Section>
		</DisabledDiv>
	);
};

export default GoogleMCAccount;
