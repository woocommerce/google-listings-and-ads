# Hooks

GLA has 113+ custom React hooks in `js/src/hooks/`, all prefixed `use`.

## Core Data Hooks

### Reading Store Data

```js
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';

// Returns { data, isResolving, hasFinishedResolution, invalidateResolution }
const { data: campaigns, isResolving } = useAppSelectDispatch( 'getAdsCampaigns' );

// With arguments — use useIsEqualRefValue to stabilize object/array args
import useIsEqualRefValue from '~/hooks/useIsEqualRefValue';

const stableArgs = useIsEqualRefValue( { country: 'US', currency: 'USD' } );
const { data } = useAppSelectDispatch( 'getBudgetRecommendations', stableArgs );
```

`useIsEqualRefValue` prevents infinite re-resolution loops when passing objects/arrays as selector args. Always use it for non-primitive arguments.

### Writing to Store

```js
import { useAppDispatch } from '~/data';

// In component body — never inside useSelect or useAppSelectDispatch
const { updateAdsCampaign, deleteAdsCampaign } = useAppDispatch();
```

### WordPress Notices

```js
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

const { createNotice } = useDispatchCoreNotices();
createNotice( 'success', __( 'Campaign saved.', 'google-listings-and-ads' ) );
```

## Domain Data Hooks

Hooks in `js/src/hooks/` wrap `useAppSelectDispatch` with domain logic:

```js
import useAdsCampaigns from '~/hooks/useAdsCampaigns';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useYouTubeAccount from '~/hooks/useYouTubeAccount';
import useBudgetRecommendation from '~/hooks/useBudgetRecommendation';
import useMCAccountCountries from '~/hooks/useMCAccountCountries';

// Most domain hooks return: { data, isResolving, hasFinishedResolution }
const { googleMCAccount, isResolving } = useGoogleMCAccount();
const { googleAdsAccount } = useGoogleAdsAccount();
const { adsCampaigns } = useAdsCampaigns();
```

## Creating a New Hook

```js
// js/src/hooks/useMyData.js
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';

const useMyData = ( params ) => {
    const stableParams = useIsEqualRefValue( params );
    const { data, isResolving, hasFinishedResolution } = useAppSelectDispatch(
        'getMyData',
        stableParams
    );

    return {
        myData: data,
        isResolving,
        hasFinishedResolution,
    };
};

export default useMyData;
```

## Testing Hooks

```js
import { renderHook, act } from '@testing-library/react';
import useMyData from '~/hooks/useMyData';

jest.mock( '~/data', () => ( {
    useAppDispatch: jest.fn().mockReturnValue( { fetchMyData: jest.fn() } ),
} ) );

describe( 'useMyData', () => {
    it( 'returns data', () => {
        const { result } = renderHook( () => useMyData( { id: 1 } ) );
        expect( result.current.myData ).toBeUndefined();
    } );
} );
```

## Common Patterns

- Use separate hooks for read (`useAppSelectDispatch`) and write (`useAppDispatch`) — never combine in one call
- Early-return pattern when data not ready: `if ( ! hasFinishedResolution ) return null;`
- `useApiFetchCallback` — wrapper for one-off API mutations that are not store-backed
- `useNavigateAwayPromptEffect` — shows browser "leave page?" dialog when form has unsaved changes
