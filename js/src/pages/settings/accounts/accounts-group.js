/**
 * Internal dependencies
 */
import Section from '~/components/section';
import './accounts-group.scss';

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
