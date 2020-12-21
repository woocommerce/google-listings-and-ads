/**
 * Internal dependencies
 */
import Card from './card';
import './index.scss';

const Section = ( props ) => {
	const { title, description, children } = props;

	return (
		<section className="wcdl-section">
			<header>
				<h1>{ title }</h1>
				<p>{ description }</p>
			</header>
			<div className="wcdl-section__body">{ children }</div>
		</section>
	);
};

Section.Card = Card;

export default Section;
