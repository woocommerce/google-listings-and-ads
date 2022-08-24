/**
 * External dependencies
 */
import classnames from 'classnames';

const styleName = {
	thinnest: 'gla-ads-mockup__placeholder--thinnest',
	thinner: 'gla-ads-mockup__placeholder--thinner',
	thicker: 'gla-ads-mockup__placeholder--thicker',
	blue: 'gla-ads-mockup__placeholder--blue',
	'gray-100': 'gla-ads-mockup__placeholder--gray-100',
	'gray-200': 'gla-ads-mockup__placeholder--gray-200',
	'gray-300': 'gla-ads-mockup__placeholder--gray-300',
	'gray-400': 'gla-ads-mockup__placeholder--gray-400',
	'gray-500': 'gla-ads-mockup__placeholder--gray-500',
};

/**
 * Renders a placeholder to draw a line.
 *
 * @param {Object} props React props.
 * @param {boolean} [props.thinnest] Whether to draw a thinnest placeholder.
 * @param {boolean} [props.thinner] Whether to draw a thinner placeholder.
 * @param {boolean} [props.thicker] Whether to draw a thicker placeholder.
 * @param {string} [props.width] Placeholder width style, 100% by default. It will be px if the unit is omitted.
 * @param {'blue'|'gray-100'|'gray-200'|'gray-300'|'gray-400'|'gray-500'} [props.color='gray-100'] Placeholder color.
 */
export default function Placeholder( {
	thinnest,
	thinner,
	thicker,
	width,
	color = 'gray-100',
} ) {
	const className = classnames(
		'gla-ads-mockup__placeholder',
		styleName[ color ],
		thinnest && styleName.thinnest,
		thinner && styleName.thinner,
		thicker && styleName.thicker
	);

	let style;

	if ( width !== undefined ) {
		style = {
			width: /^\d+$/.test( width ) ? `${ width }px` : width,
		};
	}

	return <div className={ className } style={ style } />;
}
