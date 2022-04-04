/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';

/**
 * Clicking on faq items to collapse or expand it in the Setup Ads page
 *
 * @event gla_setup_ads_faq
 * @property {string} id (faq identifier)
 * @property {string} action (`expand`|`collapse`)
 */

/**
 * @fires gla_setup_ads_faq
 */
const FaqsSection = () => {
	const getPanelToggleHandler = ( id ) => ( isOpened ) => {
		recordEvent( 'gla_setup_ads_faq', {
			id,
			action: isOpened ? 'expand' : 'collapse',
		} );
	};

	return (
		<Section>
			<Panel
				header={ __(
					'Frequently asked questions',
					'google-listings-and-ads'
				) }
			>
				<PanelBody
					initialOpen={ false }
					title={ __(
						'What do I pay for?',
						'google-listings-and-ads'
					) }
					onToggle={ getPanelToggleHandler( 'what-do-i-pay-for' ) }
				>
					<PanelRow>
						{ __(
							'You only pay when someone clicks on your product ads to your store.',
							'google-listings-and-ads'
						) }
					</PanelRow>
				</PanelBody>
				<PanelBody
					initialOpen={ false }
					title={ __(
						'What does daily average or monthly max mean?',
						'google-listings-and-ads'
					) }
					onToggle={ getPanelToggleHandler(
						'what-does-daily-average-monthly-max-mean'
					) }
				>
					<PanelRow>
						{ __(
							'Some days you might spend less than your daily average, and on others you might spend up to 4 times as much. But over a month, your total spend across the month will be approximately as calculated above.',
							'google-listings-and-ads'
						) }
					</PanelRow>
				</PanelBody>
			</Panel>
		</Section>
	);
};

export default FaqsSection;
