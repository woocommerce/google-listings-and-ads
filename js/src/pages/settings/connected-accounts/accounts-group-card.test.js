/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AccountsGroupCard from './accounts-group-card';

describe( 'AccountsGroupCard', () => {
	it( 'uses a specialized row component supplied by the account model', () => {
		function SpecializedRow( { account } ) {
			return <div>{ `Specialized ${ account.title } row` }</div>;
		}

		render(
			<AccountsGroupCard
				title="Grow your reach"
				description="Optional accounts"
				accounts={ [
					{
						id: 'example',
						title: 'Example',
						RowComponent: SpecializedRow,
					},
				] }
				onDisconnect={ jest.fn() }
			/>
		);

		expect(
			screen.getByText( 'Specialized Example row' )
		).toBeInTheDocument();
	} );
} );
