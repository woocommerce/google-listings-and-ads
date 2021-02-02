/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import GridiconChevronLeft from 'gridicons/dist/chevron-left';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const SetupBackLink = ( props ) => {
	const { className, ...rest } = props;

	return (
		<Link
			className={ classnames( 'gla-setup-back-link', className ) }
			{ ...rest }
		>
			<GridiconChevronLeft />
		</Link>
	);
};

export default SetupBackLink;
