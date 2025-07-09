/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '~/components/section';

/**
 * Renders the settings section for Enhanced Conversions setup.
 *
 * @fires gla_documentation_link_click with `{ context: 'setup-enhanced-conversions', link_id: 'enhanced-conversions-read-more', href: 'https://support.google.com/google-ads/answer/9888656' }`
 */
const DisabledEnhancedConversionCard = () => {
	return (
		<Section.Card>
			<Section.Card.Body>
				<CheckboxControl
					label={ __(
						'Send Enhanced Conversions data to Google Ads',
						'google-listings-and-ads'
					) }
					checked={ false }
					disabled={ true }
					help={ __(
						'Google Ads account is not connected. Please connect your Google Ads account to use this feature.',
						'google-listings-and-ads'
					) }
				/>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default DisabledEnhancedConversionCard;
