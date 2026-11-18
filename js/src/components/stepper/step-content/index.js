/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

// The `default` variant is the default style, and no need to append any additional class name.
const variantClassName = {
	default: false,
	narrow: 'gla-step-content--narrow',
};

/**
 * Renders the content of a step, centered within the page.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {'default'|'narrow'} [props.variant='default'] Indicate the layout of the content.
 *     'default': A wide container.
 *     'narrow': A narrower container that stacks its children with a gap between them.
 * @param {JSX.Element} [props.children] The content of the step.
 */
const StepContent = ( props ) => {
	const { className, variant = 'default', children, ...rest } = props;

	return (
		<div
			className={ classnames(
				'gla-step-content',
				variantClassName[ variant ],
				className
			) }
			{ ...rest }
		>
			<div className="gla-step-content__container">{ children }</div>
		</div>
	);
};

export default StepContent;
