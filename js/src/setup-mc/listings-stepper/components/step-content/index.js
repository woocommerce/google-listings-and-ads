/**
 * Internal dependencies
 */
import './index.scss';

const StepContent = ( props ) => {
	const { className = '', children, ...rest } = props;

	return (
		<div className={ `gla-step-content ${ className }` } { ...rest }>
			<div className="gla-step-content__container">{ children }</div>
		</div>
	);
};

export default StepContent;
