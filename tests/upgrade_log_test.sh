#!/bin/bash
# Run by tests/run.sh inside a test container, as root. Builds a stack of 4.3.0 checkouts whose
# gridctl lacks its bootstrap, so uslims_upgrade.php passes step 0's version check and then stops.
# Checks that --check writes nothing, and that --apply keeps report-<time>-pid<pid>.log with the software versions,
# each checkout's commit, the step lines and how the run ended.
set -u
UP=/repo/uslims_upgrade.php
fail=0
ok()  { echo "ok   $1"; }
bad() { echo "FAIL $1"; fail=1; }
has() { grep -qF -- "$2" "$1" && ok "$3" || { bad "$3: no '$2' in $(basename "$1")"; }; }

useradd -m us3 2>/dev/null
git config --global user.email test@example.org
git config --global user.name test
repo() { # $1 dir, $2 git|plain
    mkdir -p "$1"
    echo 4.3.0 > "$1/VERSION"
    [ "$2" = git ] && git -C "$1" init -q && git -C "$1" add VERSION && git -C "$1" commit -qm init
}
repo /home/us3/lims/bin git
repo /home/us3/lims/database/sql git
repo /srv/www/htdocs/common git
repo /srv/www/htdocs/uslims3 git
repo /srv/www/htdocs/uslims3/uslims3_T git
echo 4.3.0-dev > /srv/www/htdocs/uslims3/uslims3_T/VERSION
# A plain copy inside webinfo's checkout, so git would otherwise find webinfo's repository.
repo /srv/www/htdocs/uslims3/uslims3_P plain
# Checkouts belong to us3, as on a host, while the script runs as root.
chown -R us3: /home/us3/lims /srv/www/htdocs
SQL_COMMIT=$(git -C /home/us3/lims/database/sql rev-parse --short HEAD 2>/dev/null || git -c safe.directory='*' -C /home/us3/lims/database/sql rev-parse --short HEAD)
INDEX_BEFORE=$(stat -c '%U %Y' /srv/www/htdocs/uslims3/uslims3_T/.git/index)
ETC=/home/us3/lims/etc
mkdir -p $ETC

out=/var/tmp/check.out
php $UP --check --web-user us3 > $out 2>&1
has $out "us3_sql is 4.3.0 ($SQL_COMMIT)" "step 0 names a clean checkout's commit"
has $out "uslims3_T is 4.3.0-dev (" "step 0 reads the instance"
grep -qE "uslims3_T is 4\.3\.0-dev \([0-9a-f]+, modified\)" $out && ok "step 0 marks a modified checkout" || bad "step 0 marks a modified checkout"
has $out "uslims3_P is 4.3.0 (not a git checkout)" "step 0 does not credit webinfo's commit to a plain instance"
grep -qE "webinfo is 4\.3\.0 \([0-9a-f]+\)" $out && ok "step 0 names a checkout owned by us3" || bad "step 0 names a checkout owned by us3"
[ "$(stat -c '%U %Y' /srv/www/htdocs/uslims3/uslims3_T/.git/index)" = "$INDEX_BEFORE" ] && ok "--check leaves a modified checkout's index alone" || bad "--check rewrote a checkout's index"
[ -z "$(ls -d $ETC/uslims_upgrade-backup-* 2>/dev/null)" ] && ok "--check creates no backup directory" || bad "--check creates no backup directory"
[ $fail = 0 ] || sed -n '/^0\./,/^Terminating/p' $out

# Without the check pass (the env var --apply sets for it), --apply reaches step 0 and stops there.
useradd -m usadmin 2>/dev/null
SUDO_USER=usadmin USLIMS_UPGRADE_PREFLIGHT=batch php $UP --apply --web-user us3 > /var/tmp/apply.out 2>&1
rc=$?
[ $rc = 255 ] && ok "--apply stops at gridctl's contract (exit $rc)" || bad "--apply exit was $rc, not 255"
dirs=$(ls -d $ETC/uslims_upgrade-backup-* 2>/dev/null)
log=$dirs/report-${dirs##*/uslims_upgrade-backup-}.log
if [ "$(echo "$dirs" | grep -c .)" != 1 ] || [ ! -f "$log" ]; then
    bad "--apply keeps report-<time>-pid<pid>.log in one backup directory"
    ls -l $dirs; cat /var/tmp/apply.out
else
    ok "--apply keeps report-<time>-pid<pid>.log in one backup directory"
    [ "$(stat -c '%a %U' "$log")" = "600 usadmin" ] && ok "the log belongs to the operator who ran sudo, mode 600" || bad "the log is $(stat -c '%a %U' "$log")"
    has "$log" "uslims_upgrade.php --apply" "log header"
    has "$log" "command:  " "log records the command"
    has "$log" "backups:  $dirs" "log names its backup directory"
    has "$log" "php:      $(php -r 'echo PHP_VERSION;')" "log records PHP"
    has "$log" "mariadb:  " "log records MariaDB"
    has "$log" "os:       " "log records the OS"
    has "$log" "us3_sql is 4.3.0 ($SQL_COMMIT)" "log has step 0 with commits"
    has "$log" "gridctl is not the 4.3.0 contract" "log has error_exit's message"
    has "$log" "Terminating due to errors." "log has the termination line"
    has "$log" "exit 255 (stopped before the end)" "log records how the run ended"
    # A second run keeps its own log next to the first.
    sleep 1
    USLIMS_UPGRADE_PREFLIGHT=batch php $UP --apply --web-user us3 > /dev/null 2>&1
    [ "$(ls $ETC/uslims_upgrade-backup-*/report-*.log | sort -u | wc -l)" = 2 ] && ok "a second --apply keeps a second, separate log" || bad "a second --apply keeps a second, separate log"
fi

# --status only reports, so it refuses --apply before anything is created.
rm -rf $ETC/uslims_upgrade-backup-*
php $UP --status --apply --web-user us3 > /var/tmp/status.out 2>&1
rc=$?
[ $rc != 0 ] && grep -q "cannot be combined with --apply" /var/tmp/status.out && [ -z "$(ls -d $ETC/uslims_upgrade-backup-* 2>/dev/null)" ] \
    && ok "--status --apply is refused and creates nothing" || bad "--status --apply is refused and creates nothing (exit $rc)"

# With the check pass, a refused --apply changes nothing and so keeps no log either.
rm -rf $ETC/uslims_upgrade-backup-*
php $UP --apply --web-user us3 > /var/tmp/apply2.out 2>&1
[ -z "$(ls -d $ETC/uslims_upgrade-backup-* 2>/dev/null)" ] && ok "a refused --apply creates nothing" || bad "a refused --apply creates nothing"

exit $fail
