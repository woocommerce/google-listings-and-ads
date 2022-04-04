/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import TrackableLink from '.~/components/trackable-link';
import './index.scss';

/**
 * Renders header title and content within a [Guide modal](https://wordpress.github.io/gutenberg/?path=/docs/components-guide--default).
 *
 * @param {Object} props Component props.
 * @param {string} props.title Guide's title.
 * @param {Array<JSX.Element>} props.children Array of react element to be rendered inside the Guide as content.
 */
export default function GuidePageContent( { title, children } ) {
	return (
		<div className="gla-guide__page-content">
			<h2 className="gla-guide__page-content__header">{ title }</h2>
			<div className="gla-guide__page-content__body">{ children }</div>
		</div>
	);
}

/**
 * Clicking on a text link within the modal content
 *
 * @event gla_modal_content_link_click
 * @property {string} context indicate which link is clicked
 * @property {string} href link's URL
 */

/**
 * Renders a TrackableLink component with preset props and additional styling. This link should be a link within the content of GuidePageContent.
 *
 * @param {Object} props Props to be forwarded to TrackableLink.
 * @param {string} props.context Indicate which link is clicked.
 * @param {string} props.href Link's URL and it also be passed to `TrackableLink` component.
 * @param {string} [props.className] Additional CSS class name to be appended.
 *
 * @fires gla_modal_content_link_click with given `context, href`
 */
export function ContentLink( props ) {
	const { context, href, className, ...restProps } = props;
	return (
		<TrackableLink
			className={ classnames(
				'gla-guide__page-content__link',
				className
			) }
			eventName="gla_modal_content_link_click"
			eventProps={ { context, href } }
			type="external"
			target="_blank"
			href={ href }
			{ ...restProps }
		/>
	);
}
