<?php
## Fills in the request of each row written before the column: the instance's one
## HPCAnalysisResult with that job number that no other row claims, on the row's own cluster. A row with no single match is reported and left.
if ( !function_exists( 'gfac_analysis_request_backfill' ) ) {
    function gfac_analysis_request_backfill( $gdb, $db, $apply ) {
        global $changes;

        if ( !preg_match( '/^\w+$/', (string) $db ) ) {
            report( 'FAIL', "invalid gfac database name '$db'" );
            return;
        }
        $rows = mysqli_query( $gdb, "SELECT id, gfacID, us3_db, cluster, metaschedulerClusterExecuting" // NOSONAR $db is validated above
                                  . " FROM `$db`.analysis WHERE HPCAnalysisRequestID IS NULL" );
        if ( !$rows ) {
            report( 'FAIL', "$db.analysis: could not read rows without a request: " . mysqli_error( $gdb ) );
            return;
        }
        $filled = 0;
        $left   = [];
        while ( $r = mysqli_fetch_assoc( $rows ) ) {
            $inst = $r[ 'us3_db' ];
            if ( !preg_match( '/^[A-Za-z0-9_]+$/', (string) $inst ) ) {
                $left[] = "{$r['gfacID']} ($inst)";
                continue;
            }
            $stmt = mysqli_prepare( $gdb, "SELECT r.HPCAnalysisRequestID, q.clusterName FROM `$inst`.HPCAnalysisResult r"
                . " JOIN `$inst`.HPCAnalysisRequest q ON q.HPCAnalysisRequestID = r.HPCAnalysisRequestID"
                . " WHERE r.gfacID = ? AND r.HPCAnalysisRequestID NOT IN"
                . " (SELECT HPCAnalysisRequestID FROM `$db`.analysis WHERE us3_db = ? AND HPCAnalysisRequestID IS NOT NULL)" );
            $res = $stmt && mysqli_stmt_bind_param( $stmt, 'ss', $r[ 'gfacID' ], $inst ) && mysqli_stmt_execute( $stmt )
                 ? mysqli_stmt_get_result( $stmt ) : false;
            $cands = $res ? mysqli_fetch_all( $res, MYSQLI_ASSOC ) : [];
            $at = array_filter( [ $r[ 'cluster' ], $r[ 'metaschedulerClusterExecuting' ] ] );
            ## A row that names its cluster takes only a request for that cluster, even a sole candidate.
            if ( $at ) {
                $cands = array_values( array_filter( $cands, function ( $c ) use ( $at ) {
                    return in_array( $c[ 'clusterName' ], $at, true );
                } ) );
            }
            if ( count( $cands ) !== 1 ) {
                $left[] = "{$r['gfacID']} ($inst)";
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
            report( 'note', "$db.analysis: " . count( $left ) . " row(s) without a request match no single request"
                          . " and are left alone; no monitor or cleanup acts on them: " . implode( ', ', $left ) );
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
            gfac_analysis_request_backfill( $gdb, $db, $apply );
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
        gfac_analysis_request_backfill( $gdb, $db, $apply );
    },
];
