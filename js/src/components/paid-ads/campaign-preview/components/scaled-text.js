/**
 * External dependencies
 */
import classnames from 'classnames';

const styleName = {
	default: false, // The default style is pre-defined doesn't and need to set another class name.
	adBadge: 'gla-ads-mockup__scaled-text--ad-badge',
	smaller: 'gla-ads-mockup__scaled-text--smaller',
	larger: 'gla-ads-mockup__scaled-text--larger',
	blue: 'gla-ads-mockup__scaled-text--blue',
	'gray-700': 'gla-ads-mockup__scaled-text--gray-700',
	'gray-800': 'gla-ads-mockup__scaled-text--gray-800',
};

/**
 * Renders a placeholder to draw a line.
 *
 * @param {Object} props React props.
 * @param {boolean} [props.adBadge=false] Whether to show the 'AD' badge.
 * @param {'smaller'|'default'|'larger'} [props.size='default'] Text size.
 * @param {'blue'|'gray-700'|'gray-800'} [props.color='gray-700'] Text color.
 * @param {JSX.Element} [props.children] Content to be rendered.
 */
export default function ScaledText( {
	adBadge = false,
	size = 'default',
	color = 'gray-700',
	children,
} ) {
	const className = classnames(
		'gla-ads-mockup__scaled-text',
		styleName[ color ],
		styleName[ size ],
		adBadge && styleName.adBadge
	);
	return <div className={ className } children={ children } />;
}
