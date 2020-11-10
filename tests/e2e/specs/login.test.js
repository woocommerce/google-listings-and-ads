/**
 * External dependencies
 */
import { loginUser } from '@wordpress/e2e-test-utils';

jest.setTimeout( 100000 );

describe( 'login', () => {
	it( 'should login as admin user successfully', async () => {
		await loginUser();

		expect( page.url() ).toContain( '/wp-admin' );
	} );
} );
