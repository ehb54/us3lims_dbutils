# Tests

Regression tests that run in throwaway containers, never against a real server.

```
tests/run.sh            # both platforms
tests/run.sh el10       # one: el8 (OL8, MariaDB 10.3, PHP 7.4) or el10 (OL10, MariaDB 10.11, PHP 8.3)
ENGINE=podman tests/run.sh
```

It exits non-zero if a test fails. The first run builds the images, `us3lims-dbutils-tests:el8` and `:el10`; remove them with `docker rmi` (or `podman rmi`) when done. On a shared host with rootless podman, give podman its own `--root`, `--runroot` and `--tmpdir`.

## grants_test.php (ultrascan-tickets#1290)

The container starts two servers with `skip-name-resolve`, as config-lims-database sets it. The source server has an account at a host name, made before a restart, so the server ignores it.

- **GRANTS export** (`grants_export.php`, used by `uslims_daily_backup.php`). The source also gets a role granted to a role that sorts before it, a user with a default role, and, on 10.11, grants to PUBLIC. The tests check that:
  - the export names exactly the accounts the server ignores;
  - every CREATE comes before any GRANT, and SET DEFAULT ROLE after them;
  - there is no CREATE ROLE for PUBLIC;
  - the file loads unmodified, without `--force`, into a freshly initialized server;
  - every exported account and role then has the same grants there.
- **Instance grants** (`instance_grants.php`, used by dbmigrate stage3 and dbupgrade stage2). The user names and passwords hold a quote. The tests check that:
  - the grants apply, and the accounts log in with those passwords;
  - a syntax error whose server message quotes the password is reported by its number only;
  - with a password policy that rejects every password, each grant is reported as error 1819;
  - neither report holds any part of a password.
