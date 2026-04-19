# PR 2: Graycore check-extension CI — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a GitHub Actions workflow that runs graycore's reusable `check-extension.yaml` against the module on every push and pull request, gating the four standard checks (unit tests, DI compile, Magento coding standard, integration tests) against the Mage-OS `supported-version` matrix.

**Architecture:** One new file — `.github/workflows/ci.yml` — delegates all heavy lifting to `graycoreio/github-actions-magento2/.github/workflows/check-extension.yaml@main`. Matrix is computed by `graycoreio/github-actions-magento2/supported-version@main` (currently a single row: Mage-OS 2.4.8-p4 / PHP 8.4 / Composer 2.9.3). No secrets needed — the default `magento_repository` is `https://mirror.mage-os.org/`.

**Tech Stack:** GitHub Actions (reusable workflow, `workflow_call` + `workflow_dispatch`). No new PHP deps.

**Sibling design doc:** `docs/plans/2026-04-19-maintenance-plan-design.md` (PR 2 section). First half of PR 2 is the workflow file itself; second half is the **fix loop** for whatever the four jobs surface on first run.

---

## Pre-flight context an executor needs

- Active master SHA is `bf7f293` (PR 1 + docs merged). CLAUDE.md and `docs/plans/` are now on master.
- Repo has no existing `.github/` directory. This PR creates it.
- `composer.json` has `"php": "^8.2"`. Mage-OS `2.2.0` (the sole non-EOL row in graycore's matrix today) resolves PHP 8.4 — satisfies the constraint.
- Existing test state on master is **known-imperfect**: four unit tests are broken for pre-existing reasons (`IndexUnitTest`, `AggregateMetricsCronUnitTest`, `ProductCountAggregatorTest`, `AdminUserCountAggregatorTest` — see PR #58's scope-revision discussion). Under graycore's `unit-test-extension` job the tests run via **Magento's vendored phpunit**, not ours — failures may differ from what we saw locally.
- The integration test abstract (`Test/Integration/IntegrationTestAbstract.php`) exists and two real integration tests live in `Test/Integration/Controller/IndexControllerTest.php` and `Test/Integration/Repository/MetricRepositoryTest.php`. Their working state in CI is unknown.
- Existing style tooling (`.php-cs-fixer.php`) enforces `@PSR12 + @Symfony`, which **overlaps but doesn't match** Magento PHPCS (`Magento2` coding standard). The `coding-standard` job will almost certainly flag violations. Strategy is to fix the violations, not to drop the check.
- Branch to cut: `ci/pr2-graycore-check-extension` off the freshly-synced `master`.

## What the graycore workflow does (from `.github/workflows/check-extension.yaml@main`)

Four independent jobs, each with the `supported-version` matrix:

1. **`unit-test-extension`** — `setup-magento` in `extension` mode, `cache-magento`, declares a local path repo for our module, `composer require <our/package>:@dev --no-install`, `composer install`, appends `Extension_Unit_Tests` testsuite into `dev/tests/unit/phpunit.xml.dist`, runs `vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist --testsuite Extension_Unit_Tests`.
2. **`compile-extension`** — same setup through install, then `php bin/magento module:enable --all` and `php bin/magento setup:di:compile`. Catches broken DI / missing type args.
3. **`coding-standard`** — `composer require magento/magento-coding-standard magento/php-compatibility-fork`, `vendor/bin/phpcs --config-set installed_paths ...`, then if **no** `.phpcs.xml` / `phpcs.xml[.dist]` is present, falls back to `./vendor/bin/phpcs --standard=Magento2 --ignore=*vendor/* .` from the repo root. That scans `src/`, `lib/`, `Test/`, everything.
4. **`integration_test`** — provisions MySQL/Elasticsearch-or-OpenSearch/RabbitMQ/Redis as services from the matrix row, sets up Magento, patches `etc/install-config-mysql.php.dist` for the CI DB credentials, appends `Extension_Integration_Tests` suite, runs `vendor/bin/phpunit -c phpunit.xml.dist --testsuite Extension_Integration_Tests`. Uploads the sandbox dir on failure.

## Strategy for this PR

Two phases. **Phase A** (Task 1-3): ship the workflow file in a green or mostly-green state. **Phase B** (Task 4+): triage whichever jobs fail and fix in targeted commits. Goal is the final PR to merge with **all four jobs green**. The PR stays in draft while red.

We do not know in advance which jobs will fail or why. The plan's Phase B is therefore a triage loop with templates for the likely failure cases, not a fixed list of commits.

---

## Task 1: Cut branch and add the CI workflow

**Files:**
- Create: `.github/workflows/ci.yml`

**Step 1: Ensure on synced master**

```bash
cd /Users/david/Herd/magento2-prometheus-exporter
git checkout master
git status                # must be clean
git log -1 --oneline      # must be bf7f293 (or newer if upstream moved)
```

**Step 2: Cut the branch**

```bash
git checkout -b ci/pr2-graycore-check-extension
```

**Step 3: Create `.github/workflows/ci.yml`**

Exact content:

```yaml
name: CI

on:
  push:
    branches: [master]
  pull_request:
    branches: [master]
  workflow_dispatch:

jobs:
  compute_matrix:
    name: Compute Mage-OS matrix
    runs-on: ubuntu-latest
    outputs:
      matrix: ${{ steps.supported-version.outputs.matrix }}
    steps:
      - uses: actions/checkout@v6
      - uses: graycoreio/github-actions-magento2/supported-version@main
        id: supported-version
      - name: Echo matrix
        run: echo "${{ steps.supported-version.outputs.matrix }}"

  check-extension:
    name: Check extension
    needs: compute_matrix
    uses: graycoreio/github-actions-magento2/.github/workflows/check-extension.yaml@main
    with:
      matrix: ${{ needs.compute_matrix.outputs.matrix }}
      fail-fast: false
```

**Why these choices:**

- `on.push: master` + `on.pull_request: master` mirrors the graycore README example.
- `workflow_dispatch` lets us manually re-run for debugging without a push.
- `fail-fast: false` so we see all job failures on the first run, not just the first one.
- No `composer_cache_key` override → uses graycore's default `_mageos`. Fine for now.
- No `composer_auth` secret → we only test against mage-os mirror, which doesn't need it.
- Matrix is not narrowed — `supported-version` already filters to non-EOL releases, which today is a single row.

**Step 4: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "$(cat <<'EOF'
ci: add graycore check-extension workflow

Runs unit tests, DI compile, Magento coding standard, and integration
tests on every push and PR against the Mage-OS supported-version
matrix (currently Mage-OS 2.4.8-p4 / PHP 8.4). No secrets required —
default magento_repository is the public mage-os mirror.

Delegates entirely to graycoreio/github-actions-magento2@main. Matrix
is auto-filtered to non-EOL Mage-OS releases.
EOF
)"
```

**Step 5: Push branch (triggers the workflow)**

```bash
git push -u origin ci/pr2-graycore-check-extension
```

Do NOT open the PR yet — wait to see what the first run produces.

---

## Task 2: Watch the first run

**Step 1: Find the run**

```bash
gh run list --repo run-as-root/magento2-prometheus-exporter --branch ci/pr2-graycore-check-extension --limit 3
```

Grab the top run's ID.

**Step 2: Wait for completion**

```bash
gh run watch <run-id> --repo run-as-root/magento2-prometheus-exporter
```

**Step 3: Report status per job**

```bash
gh run view <run-id> --repo run-as-root/magento2-prometheus-exporter --json jobs --jq '.jobs[] | {name, conclusion}'
```

Expected output shape:
```
{"name": "Compute Mage-OS matrix", "conclusion": "success"}
{"name": "Check extension / unit-test-extension (...)", "conclusion": "success|failure"}
{"name": "Check extension / compile-extension (...)", "conclusion": "success|failure"}
{"name": "Check extension / coding-standard (...)", "conclusion": "success|failure"}
{"name": "Check extension / integration_test (...)", "conclusion": "success|failure"}
```

**Step 4: Record which jobs failed**

Write the failing job names to a scratch list. We'll tackle them one by one in Task 4+.

---

## Task 3: Open the PR (draft if anything is red)

**Step 1: Determine PR state**

If all four `check-extension` jobs are green → `--draft=false`. Otherwise `--draft=true`.

**Step 2: Open**

```bash
gh pr create \
  --repo run-as-root/magento2-prometheus-exporter \
  --base master \
  --title "ci: add graycore check-extension workflow" \
  --draft \
  --body "$(cat <<'EOF'
## Summary

Adds \`.github/workflows/ci.yml\` which calls graycore's reusable \`check-extension.yaml\` workflow against the Mage-OS \`supported-version\` matrix. Four jobs run on every push and PR:

- \`unit-test-extension\` — runs \`Test/Unit/\` via Magento's vendored phpunit after composer-installing this module into a Mage-OS install.
- \`compile-extension\` — \`bin/magento setup:di:compile\` against the installed extension.
- \`coding-standard\` — \`magento/magento-coding-standard\` (Magento2 PHPCS) over the module.
- \`integration_test\` — \`Test/Integration/\` via Magento's integration test framework, with MySQL/OpenSearch/RabbitMQ/Redis services provisioned from the matrix row.

Default \`magento_repository\` is \`https://mirror.mage-os.org/\` — no Adobe credentials required.

## Test plan

- [ ] \`unit-test-extension\` green across the matrix
- [ ] \`compile-extension\` green
- [ ] \`coding-standard\` green
- [ ] \`integration_test\` green

## Context

Second PR of six documented in \`docs/plans/2026-04-19-maintenance-plan-design.md\`. While this PR is in draft, follow-up commits address whatever the first CI run flags.
EOF
)"
```

Drop `--draft` if everything is already green.

---

## Task 4+ (adaptive): Fix what the first run flags

The rest of the PR depends entirely on what CI shows. Below are templates for the **likely** failures, ordered by expected severity. Execute only the templates that match actual failures. Skip everything green.

### Template A — `unit-test-extension` fails

Most probable cause: one of the pre-existing-broken tests (see PR #58 discussion). Under Magento's phpunit inside a real Magento install, factory autogeneration should resolve and `UnknownTypeException` errors likely disappear, but mock-expectation staleness (`getConnection was not expected to be called more than once`) and constructor-signature drift won't.

**Diagnostic commands (run after the red build):**

```bash
# Download the failing job's log
gh run view <run-id> --repo run-as-root/magento2-prometheus-exporter --log-failed | tee /tmp/ci-unit.log
grep -E "FAIL|Error|Expectation failed" /tmp/ci-unit.log | head -50
```

**Fix playbook:**

- If `AggregateMetricsCronUnitTest` fails with "Too few arguments to constructor" → update the test's constructor call to match current `src/Cron/AggregateMetricsCron.php`. Read the production constructor, update the test to pass the expected args as mocks.
- If `ProductCountAggregatorTest` / `AdminUserCountAggregatorTest` fail with "method getConnection was not expected to be called more than X times" → replace `->expects($this->once())` with `->expects($this->atLeastOnce())` OR count the actual calls in the production code and adjust the expectation. Prefer the latter — it catches real behavior drift.
- If `IndexUnitTest` fails with `ObjectManager isn't initialized` → the test is trying to exercise real framework code. Move it to `Test/Integration/` OR mock the `PrometheusResult` collaborator properly.
- Each fix is its own commit: `fix(test): <short reason>`.

### Template B — `compile-extension` fails

Most probable causes: new DI rule violation under PHP 8.4 strict type-inference, or a missing type hint somewhere graycore's compiler catches.

**Diagnostic:**

```bash
gh run view <run-id> --repo run-as-root/magento2-prometheus-exporter --log-failed | tee /tmp/ci-compile.log
grep -E "Error|Exception|Compilation failed" /tmp/ci-compile.log | head -30
```

**Fix playbook:**

- If `UnknownTypeException` for a class that should auto-resolve → the class name is probably misspelled in `etc/di.xml` or a factory reference. Grep for the symbol.
- If a constructor argument is flagged as missing type → add the type hint in the class's constructor signature.
- Each fix is its own commit: `fix(di): <class or XML file fixed>`.

### Template C — `coding-standard` fails

Most probable cause: the first time Magento PHPCS runs on this codebase it'll flag dozens-to-hundreds of violations. Expected rule violations:

- `Magento2.Functions.DiscouragedFunction` (e.g., `var_dump`, `print_r` in tests)
- `Magento2.Annotation.MethodArguments` (missing `@param` PHPDoc)
- `Magento2.CodeAnalysis.EmptyBlock`
- `Magento2.PHP.LiteralNamespaces` — prefer `::class` over string classnames
- `Magento2.Classes.AbstractApi` — `src/Api/` classes must be abstract or interfaces

**Fix playbook:**

1. First look at the numbers: `gh run view <run-id> --log-failed | grep -E "^FILE:|^[[:space:]]+[0-9]+ \| ERROR" | head -100`.
2. If the count is small (< 50 violations): fix them all, commit `style: fix Magento PHPCS violations`. Use `vendor/bin/phpcs --standard=Magento2 --ignore=*vendor/* .` locally (after `composer require magento/magento-coding-standard` in a scratch worktree or via graycore's installation-test action locally) to iterate.
3. If the count is large (> 50): consider a minimal **`phpcs.xml.dist`** that excludes the noisiest rules while keeping the real ones. File it alongside `phpstan.neon`. The graycore workflow respects a committed `phpcs.xml.dist` and uses it instead of the `--standard=Magento2` fallback — verified in `check-extension.yaml` line 159.
   - Example minimal `phpcs.xml.dist`:
     ```xml
     <?xml version="1.0"?>
     <ruleset name="RunAsRoot Prometheus Exporter">
       <description>Magento 2 coding standard with project-specific exclusions.</description>
       <arg name="extensions" value="php"/>
       <file>src</file>
       <file>lib</file>
       <file>Test</file>
       <rule ref="Magento2"/>
       <!-- Add exclude-pattern or exclude children as needed -->
     </ruleset>
     ```
   - Start from `<rule ref="Magento2"/>` with no exclusions. Only exclude rules after triaging each violation category. Bulk-disabling `Magento2.Annotation.*` is a common starting move; don't disable security-critical rules.
4. If legitimate style violations exist, fix them. If `lib/` (the New Relic internal library) disagrees too much with Magento2 style: add `<exclude-pattern>lib/*</exclude-pattern>` to the ruleset with a comment explaining why (`lib/` has its own PSR12-based identity, independent of Magento).
5. Each cleanup is its own commit: `style: fix <rule-family> violations` or `style: suppress <rule> in lib/ with justification`.

### Template D — `integration_test` fails

Most probable cause: `Test/Integration/` hasn't run in a Magento context for a long time; API drift.

**Diagnostic:**

```bash
gh run view <run-id> --repo run-as-root/magento2-prometheus-exporter --log-failed | tee /tmp/ci-integration.log
# Download uploaded sandbox artifact for detailed Magento install logs
gh run download <run-id> --name 'sandbox-data-*'
```

**Fix playbook:**

- If the two integration tests (`IndexControllerTest`, `MetricRepositoryTest`) have API drift → update them to match current `src/` code.
- If services fail to come up (MySQL, OpenSearch) → surfaces in the matrix `services` section; usually not our fault. Report and retry.
- If the integration test count is small and fixing is non-trivial → consider skipping with `markTestSkipped` and a `TODO` pointing at a follow-up issue, rather than blocking PR 2. Update the PR body to flag the skip.

### Template E — `compute_matrix` fails

Extremely unlikely — means graycore's `supported-version` action changed its contract. Log a comment in the PR and pin the graycore refs from `@main` to a specific SHA while investigating.

---

## Task N (final): Flip PR out of draft

Once all four `check-extension` jobs are green:

```bash
gh pr ready <pr-number> --repo run-as-root/magento2-prometheus-exporter
gh pr merge <pr-number> --repo run-as-root/magento2-prometheus-exporter --merge --delete-branch
```

Do NOT merge automatically — wait for the user's go-ahead. The verification step of PR 1 (PRs #58, #59) followed this pattern: "merge X, then Y, then verify". Same pattern here.

---

## Out of scope for this PR

- Widening the matrix to EOL Mage-OS or Adobe Commerce. Requires secrets or accepting EOL drift.
- Replacing `.php-cs-fixer.php` with PHPCS entirely. If both style tools stay, that's a follow-up decision.
- Closing any of the five open issues. Starting at PR 3.
- Nightly scheduled CI runs against Mage-OS latest. Add later if the team wants it.
- Branch protection rules requiring CI green before merge. The user can enable in repo settings once CI is trusted.

## Known risks and mitigations

- **Risk:** graycore updates `@main` mid-PR and jobs start failing for unrelated reasons.
  **Mitigation:** if instability surfaces, pin each graycore ref to a specific SHA. Accept the cost of manual updates.
- **Risk:** `coding-standard` surfaces many hundreds of violations and fixing them all blows up PR size.
  **Mitigation:** Template C Step 3 — ship a `phpcs.xml.dist` that starts permissive (excludes non-security rule families) and tighten over time as follow-ups.
- **Risk:** `integration_test` flakiness from ephemeral service containers.
  **Mitigation:** if a test is flaky on rerun, `markTestSkipped` with a TODO rather than blocking this PR. CI reliability beats coverage perfection when establishing a baseline.
