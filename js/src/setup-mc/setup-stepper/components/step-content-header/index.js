/**
 * Internal dependencies
 */
import './index.scss';

const StepContentHeader = ( props ) => {
	const { step, title, description } = props;

	return (
		<header className="gla-step-content-header">
			<p className="step">{ step }</p>
			<h1>{ title }</h1>
			<p className="description">{ description }</p>
		</header>
	);
};

export default StepContentHeader;
