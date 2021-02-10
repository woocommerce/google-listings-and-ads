/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../../../wcdl/section';
import DisabledDiv from '../../../../components/disabled-div';
import SectionContent from './section-content';

const GoogleMCAccount = ( props ) => {
	const { disabled = false } = props;

	// // TODO: check whether user already has MC Account.
	// const hasMCAccount = true;

	// let card;
	// if ( disabled ) {
	// 	card = <DisabledCard />;
	// } else if ( hasMCAccount ) {
	// 	card = <ConnectMCCard />;
	// } else {
	// 	card = <CreateAccountCard />;
	// }

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
				<SectionContent disabled={ disabled } />
			</Section>
		</DisabledDiv>
	);
};

export default GoogleMCAccount;
