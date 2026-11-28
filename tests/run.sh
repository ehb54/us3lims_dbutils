#!/bin/bash
# Regression tests for the GRANTS export (grants_export.php), the instance grants
# (instance_grants.php) and row versions in place (row_versions.php), in throwaway containers:
#   el8:  OL8,  MariaDB 10.3,  PHP 7.4
#   el10: OL10, MariaDB 10.11, PHP 8.3
# usage: tests/run.sh [el8|el10]...      (default: both)
# ENGINE=docker|podman picks the container engine (default: docker if installed, else podman).
# Exits non-zero if any test fails.
set -u
cd "$(dirname "$0")/.."
ENGINE=${ENGINE:-$(command -v docker >/dev/null 2>&1 && echo docker || echo podman)}
[ $# -gt 0 ] || set -- el8 el10
rc=0
# Row versions (tests/row_versions_test.php) need us3_sql with row versions (US3_SQL_DIR, default the
# stack's checkout) and from before them (US3_SQL_BASE_REF in that checkout, default origin/main).
US3_SQL_DIR=${US3_SQL_DIR:-$PWD/../../../../../docker/mariadb/sql}
SQL_MOUNTS=()
if [ -f "$US3_SQL_DIR/us3_row_version_tables.tsv" ]; then
    base=$(mktemp -d)
    trap 'rm -rf "$base"' EXIT
    git -C "$US3_SQL_DIR" archive "${US3_SQL_BASE_REF:-origin/main}" | tar -x -C "$base"
    chmod -R a+rX "$base"
    SQL_MOUNTS=(-v "$(cd "$US3_SQL_DIR" && pwd)":/us3_sql:ro -v "$base":/us3_sql_base:ro)
else
    echo "note: no us3_sql with row versions at $US3_SQL_DIR; tests/row_versions_test.php is skipped"
fi
for p in "$@"; do
    [ -f tests/containers/$p/Containerfile ] || { echo "unknown platform '$p' (el8, el10)"; exit 2; }
    img=us3lims-dbutils-tests:$p
    echo "== $p: building $img"
    if ! $ENGINE build -q -t $img -f tests/containers/$p/Containerfile tests/containers/$p > /dev/null; then
        echo "FAIL $p: image build"; rc=1; continue
    fi
    $ENGINE run --rm --security-opt label=disable -v "$PWD":/repo:ro "${SQL_MOUNTS[@]}" $img bash /repo/tests/in-container.sh || rc=1
done
[ $rc = 0 ] && echo "all passed" || echo "FAILURES"
exit $rc
