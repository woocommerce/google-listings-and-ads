/**
 * External dependencies
 */
import { CardBody } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const Body = ( props ) => {
	const { className, ...rest } = props;

	return (
		<CardBody
			className={ classnames( 'wcdl-section-card-body', className ) }
			{ ...rest }
		></CardBody>
	);
};

export default Body;
