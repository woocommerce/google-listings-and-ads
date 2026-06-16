# Review Preferences

Sourced from 130 merged PRs on `woocommerce/google-listings-and-ads` (PRs #2419–#3495).

## Philosophy

- Reviews are a dialogue, not a verdict — 2–4 iterative rounds on complex PRs is normal
- Every finding needs a **why** and a suggested fix, not just identification of the problem
- Tone is collaborative: "Can we…?", "Let's use…", "I think we could…"
- Explain why the pattern matters so the author learns, not just what to change
- Not too strict — architectural catches (prop drilling, deprecated APIs, externalization violations) are genuine quality issues, not pedantry

## Always flag 🔴

These appear on nearly every PR where they occur:

- **Array index as React `key` prop** — use a unique, stable ID instead
- **`@wordpress/*` or `@woocommerce/*` bundled** — these must be externalized; flag the import and the webpack config
- **Deprecated store API** — `registerStore` must be replaced with `createReduxStore` + `register`
- **Prop drilling beyond 1 level** — flag and suggest a hook or context refactor; include a concrete restructuring sketch
- **Fire-and-forget API calls** — every API call needs error handling
- **Optimistic UI without failure rollback** — if the API call fails, the UI must not be left in a broken state
- **Falsy check on a numeric value where `0` is valid** — `!someNumber` or `!obj?.prop` treats `0` as missing; use `someNumber == null` to detect only null/undefined

## Frequently flag 🟡

Consistent patterns that come up across PRs:

**PHP**
- Unguarded array key access on filtered/external data — `$array['key']` where `$array` comes from a WP filter callback or external API response; use `$array['key'] ?? null` or `isset()` guard
- Non-obvious implementation choices (encoding, date format, API workaround) with no inline comment — flag for a `// reason:` comment explaining the constraint

**SCSS**
- Hardcoded pixel values — use `$grid-unit-*` variables (`$grid-unit-10`, `$grid-unit-20`, etc.)
- Raw hex colors — use existing color variables from the abstracts
- `index.scss` filename — prefer the component name (`notification.scss`, `panel.scss`)
- Concatenated/chained selectors — split into separate rules

**JS/React**
- Variable declared far from first use — move the declaration down to just before it's needed
- Single-letter or heavily abbreviated variable names (`n`, `e`, `p`) — name them for what they represent
- Hook that doesn't return `hasFinishedResolution` — callers need it for loading states
- Implicit arrow function returns — prefer explicit `return` for readability
- Not using existing WP/WC layout components when they fit: `Flex`, `FlexBlock`, `FlexItem`, `Badge`, `Card`
- HTML semantics: `<h3>` for non-heading content — use `<p>` or `<span>`
- Prop spreading (`{ ...actionProps }`) — destructure explicitly so props are clear at the call site
- JSDoc descriptions with alignment padding — keep tight, no extra spaces to align columns
- Loose comparisons where strictness matters — note when `=== false` is intentional vs. fragile
- Test helper function naming — prefer `expect[Component]To[Record/Emit/Render][Behavior]()` over `expect[Thing]With[Condition]()`; the name should encode subject, expected outcome, and trigger (e.g., `expectComponentToRecordEventWithFilteredProperties` not `expectEventWithPropertiesFilter`)
- Event tracking property scoping — when reviewing tracks changes, check whether a new property is being added to a shared filter (`FILTER_BUDGET_RECOMMENDATIONS`, etc.) that applies across multiple events; properties specific to one event's context should not pollute shared filters

## Usually nits 🔵

- SCSS file naming conventions
- Folder name inconsistency (pluralization, hyphenation relative to adjacent folders)
- Minor JSDoc formatting
- `useCallback` without clear justification — only warranted for perf optimization, custom hook API, or preventing `useEffect` re-triggers; if none apply, suggest removing it to reduce complexity
- `forwardRef` component missing `displayName` — every `forwardRef`-wrapped export should set `ComponentName.displayName = 'ComponentName'` for React DevTools / stack traces
- JSDoc description vague verbs — "handle", "valid", "process" are too imprecise; request specific alternatives ("propagate to", "filter by", "properties defined in X")

## What gets through without comment

- Emojis in comments (😁 👍 🥳) — fine
- Logic differences with no correctness impact
- Stylistic choices that match the existing file's local conventions

## React and JS patterns

- `useSelect` for reads, `useDispatch` for writes — never dispatch inside `useSelect`
- Custom data hooks return `hasFinishedResolution` alongside the data
- No direct DOM manipulation — use refs or React state
- For lists of similar components: use a memoized hook that returns static config keyed by ID, not a wrapper component per item
- Reducer updates use `setIn` utility — not spread chains
- Hook naming follows `useGoogleAdsAccountBillingStatus` pattern; store selector stored as `selectorName` constant
- Store action type constants go in `constants.js` — not inline strings
- Component names must be capitalized, even when dynamically assigned (`const NotificationComponent = notification.component`)

## SCSS patterns

- All spacing via `$grid-unit-*` — never raw pixels
- All colors via existing variables — never hex literals
- Avoid chained selectors; separate into individual rules
- Keep styles in component-specific files (not a shared `index.scss`)
- Prefer WordPress/WooCommerce layout components (`Flex`, `FlexBlock`) over custom CSS div layouts where they cover the use case

## Naming

- Parameter and variable names are descriptive — single letters need a justification in a comment
- Hook and utility names are descriptive but not redundant given their class or domain context
- Folder names match adjacent naming conventions (check pluralization and hyphenation)

## Architecture

- Flag prop drilling: if a config object passes through 2+ component levels unchanged, suggest a hook that returns the config keyed by ID
- Wrapper components that only relay props to a child — eliminate and inline
- Never hardcode page paths — use existing URL utility functions from `~/utils/urls`
- New CTA buttons should have analytics tracking via `recordGlaEvent`

## Comment quality

Each finding in a review must:
- Explain **why** it matters (not just what is wrong)
- Include a concrete fix or code suggestion
- Cross-reference related comments when they affect the same solution
- Not soften the language to the point of obscuring the issue — be direct but constructive
