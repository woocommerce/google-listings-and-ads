/**
 * Internal dependencies
 */
import Section from '~/components/section';
import './accounts-group.scss';

/**
 * Renders a group of account cards with a title and description.
 *
 * @param {Object} props Component props.
 * @param {string} props.title The title of the group.
 * @param {string} props.description The description of the group.
 * @param {JSX.Element|JSX.Element[]} props.children The account cards to render inside the group.
 * @return {JSX.Element} The accounts group component.
 */
const AccountsGroup = ( { title, description, children } ) => {
	return (
		<Section.Card className="gla-accounts-group">
			<Section.Card.Body>
				<div className="gla-accounts-group__header">
					<h3 className="gla-accounts-group__title">{ title }</h3>
					<p className="gla-accounts-group__description">
						{ description }
					</p>
				</div>
				<div className="gla-accounts-group__cards">{ children }</div>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default AccountsGroup;
