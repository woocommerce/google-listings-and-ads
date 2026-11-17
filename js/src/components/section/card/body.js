/**
 * External dependencies
 */
import { CardBody } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './body.scss';

// The `large` padding is the default style, and no need to append any additional class name.
const paddingClassName = {
	large: false,
	medium: 'gla-section-card-body--padding-medium',
};

/**
 * Renders the body of a `Section.Card`.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {'large'|'medium'} [props.padding='large'] Indicate the padding of the card body.
 *     'large': A large padding.
 *     'medium': A medium padding.
 */
const Body = ( props ) => {
	const { className, padding = 'large', ...rest } = props;

	return (
		<CardBody
			className={ classnames(
				'gla-section-card-body',
				paddingClassName[ padding ],
				className
			) }
			{ ...rest }
		></CardBody>
	);
};

export default Body;
