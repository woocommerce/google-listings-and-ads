/**
 * Internal dependencies
 */
import './account-card-text-detail.scss';

/**
 * Renders the detail section of an account card, which is a simple text block.
 *
 * @param {Object} props Component props.
 * @param {JSX.Element} props.children The content to render in the detail section.
 * @return {JSX.Element} The account card text detail component.
 */
const AccountCardTextDetail = ( { children } ) => {
	return <div className="gla-account-card-text-detail">{ children }</div>;
};

export default AccountCardTextDetail;
