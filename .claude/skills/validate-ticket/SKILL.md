---
name: validate-ticket
description: Validate Acceptance Criteria and/or Implementation Brief text against team guidelines, then output a refined version with all issues corrected
argument-hint: <paste AC and/or IB text>
allowed-tools: [Read, Bash, Glob, Grep]
---

# Ticket Validation — AC & Implementation Brief

The user has pasted the following:

$ARGUMENTS

---

**Output style:** Compressed — drop articles, filler, pleasantries. Fragments OK. Technical terms exact. Severity labels, code, and refined output sections: write normal.

---

## Step 1 — Detect what's present

Identify which sections exist in the pasted text:

- **Acceptance Criteria (AC)** — look for a heading containing "Acceptance Criteria" or similar label
- **Implementation Brief (IB)** — look for a heading containing "Implementation Brief" or similar label

If neither heading is found but text was pasted, infer from context which it is. If still ambiguous, ask the user before proceeding.

---

## Step 2 — Validate Acceptance Criteria (if present)

Apply every rule below. Flag a violation only when you're confident — give the author the benefit of the doubt on ambiguous cases.

| Rule | Flag when… |
|---|---|
| Describes WHAT, not HOW | AC specifies which files, classes, functions, or architecture patterns to use |
| Outcomes are testable | A criterion is too vague to verify — e.g. "it should feel faster", "it should work correctly" |
| No implementation detail | AC prescribes a specific technical approach (e.g. "use a REST endpoint", "add a column to the DB") |
| Additional requirements are appropriate | This is acceptable: design specs, expected API schemas, UI copy — flag only if they read as implementation instructions |
| Does not duplicate IB content | AC and IB sections repeat the same information verbatim |

---

## Step 3 — Validate Implementation Brief (if present)

Apply every rule below.

| Rule | Flag when… |
|---|---|
| Describes HOW at the right level | Too vague: no direction for an unfamiliar engineer. Too granular: specifies exact file paths, line numbers, or import statements |
| Not a step-by-step todo list | IB reads as a mechanical checklist of tasks rather than contextual direction |
| External links present where referenced | Mentions external docs, APIs, or prior art without a link |
| Code references use GitHub permalinks | Links point to a branch URL (`/tree/{branch}/`) rather than a commit SHA permalink (`/blob/{sha}/`) — branch links are fragile |
| Avoids excessive code snippets | Large inline code blocks that look like the finished implementation rather than examples or pseudo-code |
| Abstract over concrete | Describes exact code to write (specific imports, exact method signatures) instead of naming intent at a high level |
| Does NOT duplicate AC | Copies AC wording verbatim — should reference AC instead |
| Has a Test Coverage section | Section is missing entirely |
| Test Coverage avoids "N/A" | Uses "N/A" — should describe expectations even when no coverage changes are foreseen |
| Reasonably scoped | IB is so long or detailed it reads more like a spec than a brief |

---

## Step 4 — Validate Technical Approach (if IB is present)

This step checks whether the approach described in the IB is sound given the actual codebase. Only run this when an IB was provided.

### 4a — Extract technical claims

From the IB, identify:
- Components, classes, services, hooks, or functions the author says exist and will be modified or reused
- Architectural patterns the author says should be followed (e.g. "add a new REST controller", "register in a service provider", "use an ActionScheduler job")
- Any named prior art the author says to base the implementation on

### 4b — Verify claims against the codebase

For each claim identified above:

1. **Named symbol exists** — use `grep -r "<SymbolName>" src/ js/src/` to confirm it exists. Flag if the IB references a function, class, hook, or component that cannot be found.

2. **Proposed pattern matches existing architecture** — for each type of change proposed, check a representative example of how it's already done:
   - New REST controller → check `src/API/Site/Controllers/` for a comparable controller
   - New service / DI registration → check `src/Internal/DependencyManagement/` for the relevant provider
   - New ActionScheduler job → check `src/Jobs/` for an existing job
   - New JS component or hook → check `js/src/components/` or `js/src/hooks/` for a comparable example
   - New data store slice → check `js/src/data/` for structure conventions
   - Read the relevant `docs/context/` file if one covers the area being changed (see the load-context table in the `gla-review` skill for the mapping)

3. **Existing utilities not overlooked** — grep for concepts in the IB to surface utilities the author may not know about. Flag if the IB proposes reimplementing something that already exists.

### 4c — Approach findings

Record each finding as one of:
- 🔴 **Approach — Critical**: The proposed approach contradicts a clear architectural constraint, references a symbol that doesn't exist, or would require significant rework if followed
- 🟡 **Approach — Improvement**: The approach is workable but a better-aligned alternative exists, or an existing utility should be used instead
- 🔵 **Approach — Nit**: Minor terminology mismatch or imprecise reference that could confuse the engineer

If the IB is too high-level to verify specific claims (no named symbols, no specific systems called out), note that the approach couldn't be verified at this level of detail — do not flag false positives.

---

## Step 5 — Tech Lead Summary

Before the Validation Report, output a short high-level summary aimed at a tech lead (who may be FE-specialized and not deep in the BE details). Cover:
- What type of change this is (BE only / FE only / both)
- What's new in one or two sentences — what fires, what it does
- Any new files or significant changes to existing ones (one line, no fine detail)
- Unmerged dependencies that must land first
- Explicit callout if there are no JS, REST API, or DB schema changes

Keep it to 5–8 bullet points max. No implementation detail — this is orientation, not instruction.

```
## Tech Lead Summary
<bullets>
```

---

## Step 6 — Validation Report

Output the following. Omit Critical, Improvements, or Nits sections that have no items. Always include Strengths.

```
## Validation Report

### Verdict
✅ Looks good / 🔄 Needs revision — one sentence

### Critical 🔴
Rule · AC or IB or Approach · Problem · How to fix

### Improvements 🟡
Rule · AC or IB or Approach · Suggestion

### Nits 🔵
Minor clarity, phrasing, or terminology issues only

### Strengths ✨
1–3 things done well
```

Approach findings from Step 4 are included inline with the other findings under the same severity tiers — do not create a separate Approach section.

---

## Step 7 — Refined Version

After the report, output a corrected version of each section that was provided. Apply all fixes from the validation — do not leave known violations in the refined text.

Follow these rules when rewriting:
- Preserve the author's intent and voice — change only what the guidelines require
- Restructure the minimum needed; don't over-polish
- Where a GitHub permalink is needed but can't be generated (no repo access), insert: `[permalink to <symbol or file> needed]`
- For Linear ticket references (e.g. GOOWOO-710), always construct the full URL as `https://linear.app/a8c/issue/GOOWOO-{number}` — never write "[link needed]" for these
- Where AC content was removed from the IB because it was duplicated, replace it with: `(refer to AC for <detail>)`
- If a section already passes all rules, output it unchanged
- Always output a Test Coverage section — separate from the IB, after the IB. It should set expectations even if brief; never omit or write "no changes needed" without context

Output format:

```
---

## Refined Acceptance Criteria

<corrected AC — outcomes only, each criterion testable, no implementation detail. Format as a nested bulleted list — group related criteria under a parent bullet (e.g. scheduling behaviour, negative/no-op cases, concurrent paths, failure behaviour). One level of nesting only.>

---

## Refined Implementation Brief

<corrected IB — contextual direction, appropriate links, abstract descriptions, no duplicated AC content.

Format as a bulleted list — no subheadings, no sections. Use a blank line between logical groups (prerequisites, service changes, new class, registration) to aid scanning, but no bold headings. Where a parent bullet names a method or class and has multiple distinct sub-steps, use nested child bullets for those sub-steps (e.g. `update_market()` with before/after option-write steps, or a new class with `schedule()` and `process_items()` behaviours). Keep nesting to one level only. Do NOT include Test Coverage here — it goes in its own section below.>

---

## Test Coverage

<nested bulleted list — group related test cases under a parent bullet per method or behaviour being tested (e.g. `update_market()`, `process_items()`, concurrent paths). Each parent bullet names the subject; child bullets are individual scenarios, one line each. Avoids "N/A". Brief — one line per case.>
```
