/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const getPanelToggleHandler = ( trackName, id, context ) => ( isOpened ) => {
	recordEvent( trackName, {
		id,
		action: isOpened ? 'expand' : 'collapse',
		context,
	} );
};

/**
 * Clicking on faq item to collapse or expand it.
 *
 * @event gla_faq
 * @property {string} id FAQ identifier
 * @property {string} action (`expand`|`collapse`)
 * @property {string} context Indicates which page / module the FAQ is in
 */

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
 * @param {string} [props.className] The class name for this component.
 * @param {string} props.context The track event property to be recorded when toggling on FAQ items.
 */
export default function FaqsPanel( {
	trackName,
	faqItems,
	className,
	context,
} ) {
	return (
		<Panel
			className={ classnames( 'gla-faqs-panel', className ) }
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
						onToggle={ getPanelToggleHandler(
							trackName,
							trackId,
							context
						) }
					>
						<PanelRow>{ answer }</PanelRow>
					</PanelBody>
				);
			} ) }
		</Panel>
	);
}
