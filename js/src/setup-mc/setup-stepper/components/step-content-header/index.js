/**
 * Internal dependencies
 */
import './index.scss';

const StepContentHeader = ( props ) => {
	const { className = '', step, title, description } = props;

	return (
		<header className={ `gla-step-content-header ${ className }` }>
			<p className="step">{ step }</p>
			<h1>{ title }</h1>
			<div className="description">{ description }</div>
		</header>
	);
};

export default StepContentHeader;
