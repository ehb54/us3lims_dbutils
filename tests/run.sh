#!/bin/bash
# Regression tests for the GRANTS export (grants_export.php) and the instance grants
# (instance_grants.php), in throwaway containers (ultrascan-tickets#1290):
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
for p in "$@"; do
    [ -f tests/containers/$p/Containerfile ] || { echo "unknown platform '$p' (el8, el10)"; exit 2; }
    img=us3lims-dbutils-tests:$p
    echo "== $p: building $img"
    if ! $ENGINE build -q -t $img -f tests/containers/$p/Containerfile tests/containers/$p > /dev/null; then
        echo "FAIL $p: image build"; rc=1; continue
    fi
    $ENGINE run --rm --security-opt label=disable -v "$PWD":/repo:ro $img bash /repo/tests/in-container.sh || rc=1
done
[ $rc = 0 ] && echo "all passed" || echo "FAILURES"
exit $rc
