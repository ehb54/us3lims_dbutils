<?php
## The names a request's clusterName can hold for a row's cluster keys: the key, its host name and, for a
## pool, its members' host names. Null when a key is no longer configured, so nothing can be compared.
if ( !function_exists( 'gfac_row_cluster_names' ) ) {
    function gfac_row_cluster_names( array $keys, array $details ) {
        $names = $keys;
        foreach ( $keys as $key ) {
            if ( !is_array( $details[ $key ] ?? null ) ) {
                return null;
            }
            foreach ( array_merge( [ $key ], (array) ( $details[ $key ][ 'clusters' ] ?? [] ) ) as $entry ) {
                if ( isset( $details[ $entry ][ 'name' ] ) ) {
                    $names[] = $details[ $entry ][ 'name' ];
                }
            }
        }
        return $names;
    }
}
## Fills in the request of each row written before the column: the instance's one HPCAnalysisResult with
## that job number, submitted by the row's last change, that no other row claims, on the row's own cluster.
## A row with no single match is reported and left.
if ( !function_exists( 'gfac_analysis_request_backfill' ) ) {
    function gfac_analysis_request_backfill( $gdb, $db, $apply, array $details ) {
        global $changes;

        if ( !preg_match( '/^\w+$/', (string) $db ) ) {
            report( 'FAIL', "invalid gfac database name '$db'" );
            return;
        }
        $rows = mysqli_query( $gdb, "SELECT id, gfacID, us3_db, cluster, metaschedulerClusterExecuting, `time`" // NOSONAR $db is validated above
                                  . " FROM `$db`.analysis WHERE HPCAnalysisRequestID IS NULL" );
        if ( !$rows ) {
            report( 'FAIL', "$db.analysis: could not read rows without a request: " . mysqli_error( $gdb ) );
            return;
        }
        $filled = 0;
        $left   = [];
        while ( $r = mysqli_fetch_assoc( $rows ) ) {
            $inst = $r[ 'us3_db' ];
            $label = "id={$r['id']} job {$r['gfacID']} ($inst)";
            if ( !preg_match( '/^[A-Za-z0-9_-]+$/', (string) $inst ) ) {
                $left[] = $label;
                continue;
            }
            $stmt = mysqli_prepare( $gdb, "SELECT r.HPCAnalysisRequestID, q.clusterName FROM `$inst`.HPCAnalysisResult r"
                . " JOIN `$inst`.HPCAnalysisRequest q ON q.HPCAnalysisRequestID = r.HPCAnalysisRequestID"
                . " WHERE r.gfacID = ? AND q.submitTime <= ? AND r.HPCAnalysisRequestID NOT IN"
                . " (SELECT HPCAnalysisRequestID FROM `$db`.analysis WHERE us3_db = ? AND HPCAnalysisRequestID IS NOT NULL)" );
            $res = $stmt && mysqli_stmt_bind_param( $stmt, 'sss', $r[ 'gfacID' ], $r[ 'time' ], $inst ) && mysqli_stmt_execute( $stmt )
                 ? mysqli_stmt_get_result( $stmt ) : false;
            if ( !$res ) {
                $errno = $stmt ? mysqli_stmt_errno( $stmt ) : mysqli_errno( $gdb );
                $error = $stmt ? mysqli_stmt_error( $stmt ) : mysqli_error( $gdb );
                ## Unknown database or table: the instance is gone, so there is nothing to import into.
                if ( in_array( $errno, [ 1049, 1146 ], true ) ) {
                    $left[] = $label;
                } else {
                    report( 'FAIL', "$db.analysis row {$r['id']}: could not look for its request in $inst: $error" );
                }
                continue;
            }
            $cands = mysqli_fetch_all( $res, MYSQLI_ASSOC );
            $at = array_values( array_filter( [ $r[ 'cluster' ], $r[ 'metaschedulerClusterExecuting' ] ] ) );
            ## A row takes only a request for its own cluster. A cluster no longer configured can't be
            ## compared, so there only a sole candidate is taken.
            $names = $at ? gfac_row_cluster_names( $at, $details ) : null;
            if ( $names !== null ) {
                $cands = array_values( array_filter( $cands, function ( $c ) use ( $names ) {
                    return in_array( $c[ 'clusterName' ], $names, true );
                } ) );
            }
            if ( count( $cands ) !== 1 ) {
                $left[] = $label;
                continue;
            }
            if ( $apply ) {
                $up = mysqli_prepare( $gdb, "UPDATE `$db`.analysis SET HPCAnalysisRequestID = ? WHERE id = ? AND HPCAnalysisRequestID IS NULL" );
                $req = (int) $cands[ 0 ][ 'HPCAnalysisRequestID' ];
                $id  = (int) $r[ 'id' ];
                if ( !$up || !mysqli_stmt_bind_param( $up, 'ii', $req, $id ) || !mysqli_stmt_execute( $up ) ) {
                    report( 'FAIL', "$db.analysis row {$r['id']}: " . mysqli_error( $gdb ) );
                    continue;
                }
                $changes++;
            }
            $filled++;
        }
        if ( $filled ) {
            report( $apply ? 'done' : 'todo', "$db.analysis: " . ( $apply ? 'recorded' : 'record' )
                                             . " the request of $filled row(s) written before the column" );
        }
        if ( $left ) {
            $shown = array_slice( $left, 0, 20 );
            report( 'note', "$db.analysis: " . count( $left ) . " row(s) without a request match no single request,"
                          . " so no monitor or cleanup acts on them: " . implode( ', ', $shown )
                          . ( count( $left ) > 20 ? ' and ' . ( count( $left ) - 20 ) . ' more' : '' )
                          . ". To finish one, run UPDATE $db.analysis SET HPCAnalysisRequestID = <its request> WHERE id = <id>,"
                          . " then php uslims_jobs.php --restart as us3. Delete a row whose job is not wanted." );
        }
    }
}
## Adds gfac.analysis.HPCAnalysisRequestID. Two clusters can issue the same
## Slurm job number, so a gfac.analysis row needs its own link back to the
## request it runs: monitor restarts and cleanup use it instead of guessing
## from the job number. Rows written before this get their request filled in
## when exactly one request matches; the rest are reported and left alone, and
## no runtime code acts on a row without its request.
return [
    'id'            => 'gfac_analysis_request_id',
    'since_version' => '4.3.0',
    'scope'         => 'host',
    'description'   => 'Adds HPCAnalysisRequestID to gfac.analysis.',
    'run'           => function ( $gdb, $db, $apply, $context ) {
        global $changes;

        $has = schema_query( $gdb, "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? "
                                 . "AND TABLE_NAME = 'analysis' AND COLUMN_NAME = 'HPCAnalysisRequestID'", $db );
        if ( $has && mysqli_num_rows( $has ) > 0 ) {
            report( 'ok', "$db.analysis has HPCAnalysisRequestID" );
            gfac_analysis_request_backfill( $gdb, $db, $apply, (array) ( $context[ 'cluster_details' ] ?? [] ) );
            return;
        }

        $alter = "ALTER TABLE `$db`.analysis ADD COLUMN HPCAnalysisRequestID int(11) DEFAULT NULL AFTER us3_db";
        ## Checked in --check too, so --check reports what --apply would fail on.
        if ( !gfac_runtime_table_alter_privileged( mysqli_grant_rows( $gdb ) ) ) {
            report( 'FAIL', "the gfac account cannot alter $db.analysis; as an account with ALTER on gfac run: $alter" );
            return;
        }
        report( 'todo', "add $db.analysis.HPCAnalysisRequestID" );
        if ( !$apply ) {
            return;
        }
        if ( !mysqli_query( $gdb, $alter ) ) {
            report( 'FAIL', "$db.analysis: " . mysqli_error( $gdb ) );
            return;
        }
        $changes++;
        report( 'done', "added $db.analysis.HPCAnalysisRequestID" );
        gfac_analysis_request_backfill( $gdb, $db, $apply, (array) ( $context[ 'cluster_details' ] ?? [] ) );
    },
];
