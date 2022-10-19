/**
 * External dependencies
 */
import classnames from 'classnames';

const styleName = {
	default: false, // The default style is pre-defined doesn't and need to set another class name.
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
 * @param {string} [props.width] Placeholder width style, 100% by default. It will be px if the unit is omitted.
 * @param {'blue'|'gray-100'|'gray-200'|'gray-300'|'gray-400'|'gray-500'} [props.color='gray-100'] Placeholder color.
 * @param {'thinnest'|'thinner'|'default'|'thicker'} [props.stroke='default'] Placeholder stroke size.
 */
export default function Placeholder( {
	width,
	color = 'gray-100',
	stroke = 'default',
} ) {
	const className = classnames(
		'gla-ads-mockup__placeholder',
		styleName[ color ],
		styleName[ stroke ]
	);

	let style;

	if ( width !== undefined ) {
		style = {
			width: /^\d+$/.test( width ) ? `${ width }px` : width,
		};
	}

	return <div className={ className } style={ style } />;
}
