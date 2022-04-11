/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Card from './card';
import './index.scss';

/**
 * Renders a section layout with optional blocks.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {JSX.Element} [props.title] Section title at the left side.
 * @param {JSX.Element} [props.description] Section description under the title.
 * @param {JSX.Element} [props.children] Section content at the right side.
 * @param {boolean} [props.disabled] Whether display the whole section in disabled style.
 */
const Section = ( { className, title, description, children, disabled } ) => {
	const sectionClassName = classnames(
		'wcdl-section',
		disabled ? 'wcdl-section--is-disabled' : false,
		className
	);

	return (
		<section className={ sectionClassName }>
			<header>
				{ title && <h1>{ title }</h1> }
				{ description }
			</header>
			<div className="wcdl-section__body">{ children }</div>
		</section>
	);
};

Section.Card = Card;

export default Section;
