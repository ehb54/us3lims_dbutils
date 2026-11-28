#!/bin/bash
# Run by tests/run.sh inside a test container. Starts two throwaway MariaDB servers, source and
# target, both with skip-name-resolve as config-lims-database sets it, then tests/grants_test.php.
# The source gets an account at a host name before a restart, so that the server ignores it, as
# on hosts that older roles set up. It runs as the image's user, mysql: root's database account
# has no password, so the tests log in as it over the socket.
set -u
BIN=$(ls /usr/libexec/mariadbd /usr/libexec/mysqld 2>/dev/null | head -1)
INIT=$(command -v mariadb-install-db || command -v mysql_install_db)
INIT_OPTS=""   # 10.4+ makes root's account socket-only unless told otherwise; 10.3 has no such option
$INIT --help 2>/dev/null | grep -q auth-root-authentication-method && INIT_OPTS="--auth-root-authentication-method=normal"
declare -A PID
start() { # $1 name, $2 port
    local d=/var/tmp/$1 i
    $BIN --no-defaults --datadir=$d --socket=$d.sock \
        --port=$2 --skip-name-resolve --log-error=$d.err &
    PID[$1]=$!
    for i in $(seq 1 60); do
        mysql --no-defaults -S $d.sock -u root -e 'SELECT 1' > /dev/null 2>&1 && return 0
        sleep 1
    done
    echo "server $1 did not start:"; tail -5 $d.err; exit 1
}
stop() { kill ${PID[$1]}; wait ${PID[$1]}; }
for s in source target; do
    mkdir -p /var/tmp/$s
    $INIT $INIT_OPTS --datadir=/var/tmp/$s > /var/tmp/$s.init 2>&1 || { echo "init $s failed"; tail /var/tmp/$s.init; exit 1; }
done
start source 3306
mysql --no-defaults -S /var/tmp/source.sock -u root \
    -e "CREATE USER 'hn_user'@'db.example.org' IDENTIFIED BY 'hn'; GRANT SELECT ON mysql.db TO 'hn_user'@'db.example.org'" 2>&1 | grep -v -i warning
stop source
start source 3306
start target 3307
echo "MariaDB $(mysql --no-defaults -S /var/tmp/source.sock -u root -N -B -e 'SELECT VERSION()'), PHP $(php -r 'echo PHP_VERSION;')"
rc=0
SRC_SOCK=/var/tmp/source.sock DST_SOCK=/var/tmp/target.sock php /repo/tests/grants_test.php || rc=1
if [ -d /us3_sql ]; then
    SRC_SOCK=/var/tmp/source.sock US3_SQL_DIR=/us3_sql US3_SQL_BASE_DIR=/us3_sql_base php /repo/tests/row_versions_test.php || rc=1
fi
exit $rc
