/**
 * Internal dependencies
 */
import './index.scss';

const StepContent = ( props ) => {
	const { className = '', ...rest } = props;

	return <div className={ `gla-step-content ${ className }` } { ...rest } />;
};

export default StepContent;
