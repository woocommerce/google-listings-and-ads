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

const FaqsSection = () => {
	const getPanelToggleHandler = ( id ) => ( isOpened ) => {
		recordEvent( 'gla_setup_ads_faqs', {
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
				<PanelBody
					initialOpen={ false }
					title={ __(
						'What are the terms and conditions for the free ad credit?',
						'google-listings-and-ads'
					) }
					onToggle={ getPanelToggleHandler(
						'what-are-the-terms-conditions'
					) }
				>
					<PanelRow>
						{ /* TODO: what is the answer here? */ }
						{ __(
							'TODO: placeholder here.',
							'google-listings-and-ads'
						) }
					</PanelRow>
				</PanelBody>
			</Panel>
		</Section>
	);
};

export default FaqsSection;
