/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import DisabledDiv from '.~/components/disabled-div';
import SectionContent from './section-content';

const GoogleMCAccount = ( props ) => {
	const { disabled = false } = props;

	return (
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
			<DisabledDiv disabled={ disabled }>
				<SectionContent disabled={ disabled } />
			</DisabledDiv>
		</Section>
	);
};

export default GoogleMCAccount;
