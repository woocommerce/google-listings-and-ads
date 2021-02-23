/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import TrackableLink from '.~/components/trackable-link';
import './index.scss';

// TODO: These components are planned to be reused when implementing the Successful Campaign Creation Modal.
//       Ref: https://github.com/woocommerce/google-listings-and-ads/issues/180

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
 * Renders a TrackableLink component with preset props and additional styling. This link should be a link within the content of GuidePageContent.
 *
 * @param {Object} props Props to be forwarded to TrackableLink.
 * @param {Array<JSX.Element>} props.children Array of react element to be rendered inside the TrackableLink.
 * @param {string} [props.className] Additional CSS class name to be appended.
 */
export function ContentLink( props ) {
	const { children, className, ...restProps } = props;
	return (
		<TrackableLink
			className={ classnames(
				'gla-guide__page-content__link',
				className
			) }
			type="external"
			target="_blank"
			{ ...restProps }
		>
			{ children }
		</TrackableLink>
	);
}
