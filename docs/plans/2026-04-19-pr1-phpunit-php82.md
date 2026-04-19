# PR 1: phpunit bump + PHP 8.2 requirement + build/tools cleanup — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Close Dependabot alerts #2 and #3 by (a) bumping `phpunit/phpunit` to a patched version, (b) adding a `php: ^8.2` constraint, and (c) deleting the unused `build/tools/` directory.

**Architecture:** Pure maintenance PR. No runtime code changes. Three commits on a feature branch: composer bump + test verification, `build/tools/` deletion, docs update. Establishes the baseline every subsequent PR (graycore CI + metric features) depends on.

**Tech Stack:** PHP 8.2+, Composer 2.x, phpunit 12/13. No new tooling.

**Sibling design doc:** `docs/plans/2026-04-19-maintenance-plan-design.md` (covers the full six-PR sequence this plan is step 1 of).

---

## Pre-flight context an executor needs

- **Repo is a Magento 2 module, not a Magento install.** There is no `bin/magento` here. See `CLAUDE.md` at the repo root for the full layout.
- **Our `require-dev` phpunit is only used for local `vendor/bin/phpunit Test/Unit` runs.** The graycore CI (landing in PR 2) uses Magento's vendored phpunit for CI, not ours.
- **`build/tools/` is dead code from the GitLab-CI era.** `grep -r 'build/tools' .` outside of `CLAUDE.md` returns no hits. Its `composer.lock` is the source of Dependabot alert #2.
- **Existing tests are already PHPUnit-modern:** no `@dataProvider`, `@test`, `@group`, or `@covers` annotations. `setUp(): void`. `final class`. `createMock(...)` / `->expects($this->once())`. No `MockBuilder::getMockForAbstractClass()`. Migration surface is therefore almost zero.
- **The `CLAUDE.md` in the working tree is currently untracked** (created in a prior session) and references `build/tools/`. Both the commit-and-fix are part of this PR.
- **Branch to use:** current branch `docs/2026-04-maintenance-plan` holds the design doc. Cut a new branch `chore/pr1-phpunit-php82` off `master` for this PR so the design-doc branch can merge independently if desired.

---

## Task 1: Cut the feature branch and stage CLAUDE.md

**Files:**
- Untracked: `CLAUDE.md` (will be committed in Task 4, not yet)

**Step 1: Confirm clean state from master**

Run:
```bash
git -C /Users/david/Herd/magento2-prometheus-exporter checkout master
git -C /Users/david/Herd/magento2-prometheus-exporter status
```
Expected: `On branch master ... Untracked files: CLAUDE.md` (CLAUDE.md carries over from the working tree).

**Step 2: Cut the new branch**

Run:
```bash
git -C /Users/david/Herd/magento2-prometheus-exporter checkout -b chore/pr1-phpunit-php82
```
Expected: `Switched to a new branch 'chore/pr1-phpunit-php82'`.

**Step 3: No commit yet.** CLAUDE.md stays untracked until Task 4.

---

## Task 2: Bump composer.json

**Files:**
- Modify: `composer.json`

**Step 1: Read current state**

Open `composer.json` and note these three lines that will change:
- `"require": { ... no "php" key today ... }`
- `"require-dev": { "phpunit/phpunit": "^9.5|^10.0" }`

**Step 2: Apply the edit**

In `composer.json`:

- Add `"php": "^8.2"` as the **first** key of `"require"`. Magento convention: PHP constraint first.
- Change `"phpunit/phpunit": "^9.5|^10.0"` to `"phpunit/phpunit": "^12.5.22 || ^13.1.6"`.

Resulting `"require"` (partial):
```json
"require": {
  "php": "^8.2",
  "magento/framework": "*",
  ...
}
```

Resulting `"require-dev"`:
```json
"require-dev": {
  "phpunit/phpunit": "^12.5.22 || ^13.1.6"
}
```

**Step 3: Validate composer.json syntax**

Run:
```bash
composer validate --no-check-all --strict composer.json
```
Expected: `./composer.json is valid`.

---

## Task 3: Install new phpunit, record any deprecation output

**Files:** none modified directly; produces `composer.lock`.

**Step 1: Update the phpunit dependency**

Run:
```bash
composer update phpunit/phpunit --with-dependencies
```
Expected: phpunit resolved to `12.5.22` or `13.1.x`. Note in the terminal output whether phpunit 12 or 13 was selected — affects attribute imports if we need any in the future (we don't, in this PR).

**Step 2: Verify phpunit version**

Run:
```bash
vendor/bin/phpunit --version
```
Expected: `PHPUnit 12.5.22 by Sebastian Bergmann and contributors.` (or `13.1.x`).

---

## Task 4: Run the unit test suite against new phpunit

**Files:** none. Diagnostic only.

**Step 1: Run unit tests**

Run:
```bash
vendor/bin/phpunit Test/Unit
```
Expected (based on pre-flight scan): **all 27 unit-test files pass on the first attempt**. The tests don't use any phpunit 10-only API.

**Step 2: If anything fails, triage**

If there are failures:
- Deprecation notice about `willReturnSelf` / `returnValue` → rewrite to `willReturn($same)`.
- "Data provider must return iterable" → we have no data providers; ignore.
- "Method MockBuilder::getMockForAbstractClass does not exist" → we don't call it; ignore.
- Anything else: STOP and report. Don't silently mutate test logic to make it pass.

**Step 3: If all green, no commit yet.** Composer changes get committed with test-run evidence in Task 5.

---

## Task 5: Commit the phpunit / PHP bump

**Files:**
- Staged: `composer.json` only (`composer.lock` is gitignored in this repo — see `.gitignore:45`).

**Step 1: Confirm composer.lock will not be committed**

Run:
```bash
git -C /Users/david/Herd/magento2-prometheus-exporter check-ignore -v composer.lock
```
Expected: `.gitignore:45:composer.lock    composer.lock`. This confirms the file is ignored. Do NOT use `git add -f` to force-add it — module convention here is to let consumers resolve their own lock.

**Step 2: Stage and commit just the manifest change**

Run:
```bash
git -C /Users/david/Herd/magento2-prometheus-exporter add composer.json
git -C /Users/david/Herd/magento2-prometheus-exporter commit -m "$(cat <<'EOF'
chore: require PHP 8.2 and bump phpunit to 12.5.22/13.1.6

Closes GHSA-qrr6-mg7r-m243 (phpunit argument injection via newline in
INI values). Patched in phpunit 12.5.22 / 13.1.6; no patch in 9.x or
10.x, so the bump is mandatory.

PHP 8.2 constraint aligns with the Mage-OS 2.4.8+ matrix graycore CI
will test against.
EOF
)"
```
Expected: clean commit with 1 file changed. No hook failures (this repo has no pre-commit hooks).

---

## Task 6: Delete build/tools/

**Files:**
- Delete: `build/tools/composer.json`, `build/tools/composer.lock` (the only files in that directory)

**Step 1: Confirm nothing references build/tools outside of CLAUDE.md**

Run:
```bash
grep -r 'build/tools' /Users/david/Herd/magento2-prometheus-exporter \
  --exclude-dir=.git --exclude-dir=vendor --exclude-dir=build
```
Expected: zero matches. (The `--exclude-dir=build` keeps the directory itself from matching.)

Run also:
```bash
grep -rn 'build/tools' /Users/david/Herd/magento2-prometheus-exporter/CLAUDE.md
```
Expected: one match — the line "`build/` is for CI tooling only: `build/tools/composer.json` pins dev tools...". That line gets rewritten in Task 8.

**Step 2: Remove the directory**

Run:
```bash
git -C /Users/david/Herd/magento2-prometheus-exporter rm -r build/tools
```
Expected: two files staged for deletion. The `build/tests/` sibling and `build/.gitignore` remain untouched.

**Step 3: Verify build/ still exists with the right contents**

Run:
```bash
ls /Users/david/Herd/magento2-prometheus-exporter/build
```
Expected: `.gitignore  tests`. The `tools` directory is gone; `tests` (integration install config) stays.

**Step 4: Commit**

Run:
```bash
git -C /Users/david/Herd/magento2-prometheus-exporter commit -m "$(cat <<'EOF'
chore: delete unused build/tools directory

The directory contained an independent composer project for Robo +
PHPMD + PHPCPD + PDepend + PHPMetrics + sensiolabs/security-checker,
leftover from the pre-GitHub GitLab-CI era. It is referenced nowhere
in the repo and last touched by "cleanup build" commits in 2020.

Closes GHSA-r39x-jcww-82v6 (symfony/process MSYS2 arg escaping) whose
only surface in this repo was build/tools/composer.lock.
EOF
)"
```

---

## Task 7: Update README.md PHP version

**Files:**
- Modify: `README.md`

**Step 1: Find the line**

Open `README.md` and locate (around line 35):
```
- PHP 7.4 or higher
```

**Step 2: Replace**

Change to:
```
- PHP 8.2 or higher
```

**Step 3: Stage but don't commit yet.** Bundled with the CLAUDE.md fix in Task 8.

---

## Task 8: Fix CLAUDE.md and commit docs

**Files:**
- Modify: `CLAUDE.md` (currently untracked — this is the first commit that adds it)

**Step 1: Locate the stale line**

Open `CLAUDE.md` and find the paragraph starting `build/` is for CI tooling only (under the "Layout and autoload" section). The surrounding text reads:

> `build/` is for CI tooling only: `build/tools/composer.json` pins dev tools (robo, phpmd, phpcpd, phpmetrics, pdepend) and `build/tests/integration/install-config-mysql.php` is the Magento integration-test install config. Ignore `build/` for feature work.

**Step 2: Rewrite that sentence to match post-deletion reality**

Replace it with:

> `build/tests/integration/install-config-mysql.php` is the Magento integration-test install config used by `Test/Integration/`. That's the only reason `build/` still exists.

**Step 3: Stage and commit README + CLAUDE.md together**

Run:
```bash
git -C /Users/david/Herd/magento2-prometheus-exporter add README.md CLAUDE.md
git -C /Users/david/Herd/magento2-prometheus-exporter commit -m "$(cat <<'EOF'
docs: update README PHP requirement and land CLAUDE.md

README: PHP 7.4 -> 8.2 to match the new composer constraint.

CLAUDE.md: initial commit of the Claude Code guidance file, with the
reference to the now-deleted build/tools directory corrected.
EOF
)"
```

---

## Task 9: Full verification

**Files:** none modified.

**Step 1: Composer sanity**

Run:
```bash
composer validate --strict
composer install --dry-run
```
Expected: both succeed with no errors.

**Step 2: Tests**

Run:
```bash
vendor/bin/phpunit Test/Unit
```
Expected: all 27 unit test files pass, no deprecation warnings, no errors.

**Step 3: Static analysis**

Run:
```bash
vendor/bin/phpstan analyse
```
Expected: phpstan passes at level 5, same as before (the only code changes were in composer.json, which phpstan doesn't analyse). If phpstan is missing from `vendor/bin`, it was never a declared dev dep — noted for a follow-up, not a blocker here.

**Step 4: Style**

Run:
```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```
Expected: no diff output (no PHP files were touched).

**Step 5: Final git state**

Run:
```bash
git -C /Users/david/Herd/magento2-prometheus-exporter log --oneline master..HEAD
```
Expected three commits:
```
<sha>  docs: update README PHP requirement and land CLAUDE.md
<sha>  chore: delete unused build/tools directory
<sha>  chore: require PHP 8.2 and bump phpunit to 12.5.22/13.1.6
```

---

## Task 10: Push and open PR

**Step 1: Push branch**

Run:
```bash
git -C /Users/david/Herd/magento2-prometheus-exporter push -u origin chore/pr1-phpunit-php82
```

**Step 2: Open PR**

Run:
```bash
gh pr create \
  --repo run-as-root/magento2-prometheus-exporter \
  --base master \
  --title "chore: bump phpunit, require PHP 8.2, drop dead build/tools" \
  --body "$(cat <<'EOF'
## Summary

- Bumps `phpunit/phpunit` to `^12.5.22 || ^13.1.6` to close Dependabot alert #3 (GHSA-qrr6-mg7r-m243, argument injection via newline in INI values).
- Adds `php: ^8.2` to `composer.json`. Aligns with the Mage-OS 2.4.8 matrix that graycore CI will test against in the follow-up PR.
- Deletes `build/tools/`, leftover from the GitLab-CI era and referenced nowhere. Closes Dependabot alert #2 (GHSA-r39x-jcww-82v6, symfony/process on Windows MSYS2), whose only surface was `build/tools/composer.lock`.
- Updates README PHP version. Adds CLAUDE.md (initial commit, with the stale build/tools reference corrected).

No runtime code changes. All 27 unit tests pass on the new phpunit without modification.

## Test plan

- [x] `composer validate --strict` passes
- [x] `vendor/bin/phpunit Test/Unit` green on PHP 8.2
- [x] `vendor/bin/phpstan analyse` green
- [x] `vendor/bin/php-cs-fixer fix --dry-run` reports no changes

## Context

First of a six-PR sequence documented in `docs/plans/2026-04-19-maintenance-plan-design.md`.
EOF
)"
```
Expected: PR URL printed. Return it to the user.

---

## Out of scope for this PR

- Any `.github/workflows` files — that's PR 2.
- Any new metric code — PRs 4-6.
- Replacing `php-cs-fixer` with `magento/magento-coding-standard` — deliberate follow-up decision after CI reveals the PHPCS diff.
- Closing issue #24 — PR 3.
- Widening the matrix to EOL Mage-OS — out of scope per the design doc.

## Known risks and mitigations

- **Risk:** phpunit 12/13 introduces a deprecation or breakage not caught by the pre-flight annotation scan.
  **Mitigation:** Task 4 runs the full suite before anything is committed; Task 5 only commits after a green run. If anything fails, Task 4 Step 2 says STOP, don't mutate test logic — we'd need to pause the plan and re-brainstorm.
- **Risk:** `composer install` in a consumer Magento store breaks because Magento's own `require` wants PHP `^8.1` on older Mage-OS.
  **Mitigation:** out of scope — this module's declared PHP `^8.2` correctly represents what the test matrix covers. Consumers on older PHP were already pinned to the prior module release (`3.2.6`). The next release will be a major bump; semver is on our side.
- **Risk:** consumers still on PHP 8.1 can't upgrade.
  **Mitigation:** this is a feature, not a bug — if we allowed 8.1, CI couldn't guarantee it works, and the phpunit bump requires 8.2 anyway. Tag the next release as a major version bump. Consumers on 8.1 stay on `3.x`.
