# Browser-test targets for this module, sourced by the shared runner
# (~/Sites/0_ss-mods-maintenance/tools/browser/run.sh) and by .github/workflows/browser-tests.yml
# once that is synced in. Plain bash assignments only. CI tests only the targets with an empty
# SS<n>_SRC_REF (= this checkout): on this branch that is both.
# Ports are assigned in ~/Sites/0_ss-mods-maintenance/tools/browser/PORTS.md; take new ones there.

BROWSER_PACKAGE="restruct/silverstripe-faq"
BROWSER_TARGETS="ss5 ss6"

# This branch (main, 1.1.x) requires cms ^5 || ^6, so it serves BOTH majors (empty ref = the
# checkout the runner was given). The 0.1 branch is a pre-Composer SS3-era line, not a target.
SS5_RECIPE="^5"
SS5_PHP="8.3"
SS5_PORT="8871"
SS5_SRC_REF=""

SS6_RECIPE="^6"
SS6_PHP="8.3"
SS6_PORT="8872"
SS6_SRC_REF=""
