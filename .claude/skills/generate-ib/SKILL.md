---
name: generate-ib
description: Generate an Implementation Brief given a user story and Acceptance Criteria
argument-hint: <paste user story and AC>
allowed-tools: [Read, Bash, Glob, Grep]
---

# Implementation Brief Generator

The user has provided the following:

$ARGUMENTS

---

**Output style:** Compressed — drop articles, filler, pleasantries. Fragments OK. Technical terms exact. IB and Test Coverage output sections: write normal.

---

## Step 1 — Parse input

Identify:
- **User story** — the "as a / I want / so that" or equivalent summary describing the feature goal
- **Acceptance Criteria (AC)** — the testable outcomes the implementation must satisfy

If either is missing, ask before proceeding.

---

## Step 2 — Understand the scope

From the user story and AC, determine:
- Is this BE only, FE only, or both?
- What domain is this touching? (e.g. product sync, markets, jobs, REST API, merchant centre, JS data store, React UI)
- What is the core behaviour being added or changed?

---

## Step 3 — Research the codebase

Based on the scope identified in Step 2, search the codebase to find relevant existing patterns, services, and utilities. Read 2–3 representative examples of the pattern being followed — understand the structure well enough to give accurate direction.

**For PHP/BE changes:**
- Relevant existing services, classes, or helpers in `src/` that will be modified or reused
- Existing patterns to follow:
  - New ActionScheduler job → read an existing job in `src/Jobs/`
  - New REST controller → read a comparable controller in `src/API/Site/Controllers/`
  - New service → check `src/Internal/DependencyManagement/` for the relevant provider pattern
  - Hook usage → check `src/MerchantCenter/` or `src/Product/` for `add_action` / `add_filter` patterns
- Existing utilities that could be reused (grep for key concepts from the AC to avoid reinventing)
- The DI registration pattern for whatever service type is involved

**For JS/FE changes:**
- Relevant existing components in `js/src/components/`
- Relevant existing hooks in `js/src/hooks/`
- Data store slice if state is involved (`js/src/data/`)
- Comparable pages or flows in `js/src/pages/`

Also check `docs/context/` for any file covering the area being changed.

---

## Step 4 — Identify dependencies

List any:
- Unmerged tickets that must land first — construct Linear links as `https://linear.app/a8c/issue/GOOWOO-{number}`
- Symbols referenced in the AC that don't yet exist in the codebase (expected if a dependency ticket introduces them)
- Architectural prerequisites the implementation relies on

---

## Step 5 — Draft the Implementation Brief

Apply these rules when writing:

**Scope and tone**
- Purpose: give enough context and direction that another engineer can execute without duplicating the research effort, and that a reviewer can audit the approach before execution
- Not a todo list — direction and context, not a mechanical checklist of tasks
- Most IBs should be writable in a few hours; if it's growing into a spec, cut it back

**Level of detail**
- Describe HOW at the right level — enough for an unfamiliar engineer, not so granular it specifies exact variable names, import statements, or line numbers
- Prefer abstract over concrete — summarise intended class/method names and how they should work at a high level, rather than spelling out the exact code
- If a class or function is unique by name in the codebase, referencing the name alone is sufficient — no need to specify the file path

**Code references and links**
- Where referencing existing code as prior art or context, use GitHub commit-SHA permalinks (not branch URLs, which are fragile). If a permalink can't be generated, write `[permalink to <symbol or file> needed]`
- All Linear ticket references: full URL `https://linear.app/a8c/issue/GOOWOO-{number}`
- Code snippets: use conservatively — examples or pseudo-code to convey direction only, not finished implementation. Excessive inline code turns the IB review into a premature code review

**AC and duplication**
- Never duplicate AC content verbatim — reference it instead (e.g. "use the copy from the AC", "per the AC criterion for…"). AC is the source of truth; duplicating it causes definition drift when AC changes later
- Does NOT include Test Coverage — that goes in its own section

**Format**
- Flat bulleted list — no subheadings, no bold section headings
- Blank line between logical groups (prerequisites, service changes, new class, registration)
- Where a parent bullet names a method or class with multiple distinct sub-steps, use nested child bullets (one level only)
- Each bullet: one actionable or contextual statement

---

## Step 6 — Draft Test Coverage

This section covers Jest unit/component tests, PHPUnit, and E2E. Rules:
- Check existing test files for the code being changed — note what's already covered before describing additions
- Never use "N/A" — even when no coverage changes are foreseen, describe expectations for the engineer (e.g. what to watch for, what existing tests are relevant)
- Break down by test type (PHPUnit / Jest / E2E) where applicable
- Keep it high-level — summarise what should be added, updated, or removed, not how to write the tests
- Format: nested bulleted list — parent bullet per test type or behaviour group; child bullets one scenario per line

---

## Step 7 — Output

```
## Tech Lead Summary

<5–8 bullets: change type (BE/FE/both), what's new at a high level, new files or significant changes, unmerged dependencies, explicit callout if no JS/REST API/DB schema changes>

---

## Implementation Brief

<flat bulleted list per Step 5 rules — no AC duplication, no todo list, abstract direction with Linear links and GitHub permalinks>

---

## Test Coverage

<nested bulleted list per Step 6 rules — broken down by test type where applicable, no "N/A">
```
