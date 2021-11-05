/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './index.scss';

const getPanelToggleHandler = ( trackName, id ) => ( isOpened ) => {
	recordEvent( trackName, {
		id,
		action: isOpened ? 'expand' : 'collapse',
	} );
};

/**
 * @typedef {Object} FaqItem
 * @property {string} trackId Track ID of this FAQ.
 * @property {JSX.Element} question The question of this FAQ.
 * @property {JSX.Element} answer The answer of this FAQ.
 */

/**
 * Renders a toggleable FAQs by the Panel layout
 *
 * @param {Object} props React props.
 * @param {string} props.trackName The track event name to be recorded when toggling on FAQ items.
 * @param {Array<FaqItem>} props.faqItems FAQ items for rendering.
 */
export default function FaqsPanel( { trackName, faqItems } ) {
	return (
		<Panel
			className="gla-faqs-panel"
			header={ __(
				'Frequently asked questions',
				'google-listings-and-ads'
			) }
		>
			{ faqItems.map( ( { trackId, question, answer } ) => {
				return (
					<PanelBody
						key={ trackId }
						title={ question }
						initialOpen={ false }
						onToggle={ getPanelToggleHandler( trackName, trackId ) }
					>
						<PanelRow>{ answer }</PanelRow>
					</PanelBody>
				);
			} ) }
		</Panel>
	);
}
