# Tests

GLA JS tests use Jest + `@testing-library/react`, co-located with source files.

## Configuration

- Base config: `jest.config.js` extends `@wordpress/scripts/config/jest-unit.config`
- Test environment: `jsdom`
- Setup file: `js/src/tests/jest-unit.setup.js` — runs before every test suite

## Test File Placement

```
js/src/components/my-component/
├── index.js
└── index.test.js           ← co-located test

js/src/data/test/
├── reducer.test.js          ← data layer tests
├── adapters.test.js
├── resolvers.test.js
└── __helpers__/             ← shared test utilities
```

## Path Alias

`~` maps to `js/src/` in jest via `moduleNameMapper`:

```js
import useMyHook from '~/hooks/useMyHook';  // resolves to js/src/hooks/useMyHook.js
```

## Global Mocks (jest-unit.setup.js)

Pre-configured mocks available in every test:

```js
// glaData global — update this file when adding new glaData properties
global.glaData = {
    slug: 'gla',
    mcSetupComplete: true,
    mcSupportedCountry: true,
    mcSupportedLanguage: true,
    adsSetupComplete: true,
    enableReports: true,
    dateFormat: 'F j, Y',
    timeFormat: 'g:i a',
    initialWpData: { version: undefined },
};

// useDispatchCoreNotices — returns { createNotice: () => {} }
jest.mock( '~/hooks/useDispatchCoreNotices', ... );

// console.error — silenced; real calls tested in handleError.test.js
jest.mock( '~/utils/console', ... );
```

Override `glaData` per test when needed:

```js
beforeEach( () => {
    global.glaData.mcSetupComplete = false;
} );
```

## Component Tests

```js
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import MyComponent from './index';

describe( 'MyComponent', () => {
    it( 'renders the title', () => {
        render( <MyComponent title="Hello" /> );
        expect( screen.getByText( 'Hello' ) ).toBeInTheDocument();
    } );

    it( 'calls onClick on button click', async () => {
        const onClick = jest.fn();
        render( <MyComponent onClick={ onClick } /> );
        await userEvent.click( screen.getByRole( 'button' ) );
        expect( onClick ).toHaveBeenCalledTimes( 1 );
    } );
} );
```

## Hook Tests

```js
import { renderHook, act } from '@testing-library/react';
import useMyHook from '~/hooks/useMyHook';

jest.mock( '~/data', () => ( {
    useAppDispatch: jest.fn().mockReturnValue( { saveData: jest.fn() } ),
} ) );

it( 'returns initial state', () => {
    const { result } = renderHook( () => useMyHook() );
    expect( result.current.data ).toBeNull();
} );
```

## Store/Reducer Tests

```js
import reducer from '~/data/reducer';
import TYPES from '~/data/action-types';

it( 'stores campaigns on receive', () => {
    const state = reducer( {}, { type: TYPES.RECEIVE_ADS_CAMPAIGNS, campaigns: [ { id: 1 } ] } );
    expect( state.ads_campaigns ).toHaveLength( 1 );
} );
```

## Running Tests

```bash
npm run test:js                                          # all tests
npm run test:js:watch                                    # watch mode
npm run test:js -- path/to/index.test.js                 # single file
npm run test:js -- --testNamePattern "renders the title" # by name
```
