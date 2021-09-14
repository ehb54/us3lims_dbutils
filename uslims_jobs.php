<?php

# developer defines
$logging_level = 2;
# end of developer defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options} {db_config_file}

list all uslims3_ databases in db

Options

--help                : print this information and exit

--db             name : select database to report (required for most options)
--reqid          id   : provide information on the specific HPCAnalysisRequestID
--gfacid         id   : provide information on the specific gfacID
--full                : display all field data (normally truncates multiline outputs to last line)
--queue-messages      : include queue message detail

__EOD;

require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$db      = false;
$reqid   = false;
$gfacid  = false;
$anyargs = false;
$fullrpt = false;
$qmesgs  = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    $anyargs = true;
    switch( $arg = $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--db": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $db = array_shift( $u_argv );
            break;
        }
        case "--reqid": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $reqid = array_shift( $u_argv );
            break;
        }
        case "--gfacid": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $gfacid = array_shift( $u_argv );
            break;
        }
        case "--full": {
            array_shift( $u_argv );
            $fullrpt = true;
            break;
        }
        case "--queue-messages": {
            array_shift( $u_argv );
            $qmesgs = true;
            break;
        }
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }        
}

$config_file = "db_config.php";
if ( count( $u_argv ) ) {
    $use_config_file = array_shift( $u_argv );
} else {
    $use_config_file = $config_file;
}

if ( !$anyargs || count( $u_argv ) ) {
    echo $notes;
    exit;
}

if ( !file_exists( $use_config_file ) ) {
    fwrite( STDERR, "$self: 
$use_config_file does not exist

to fix:

cp ${config_file}.template $use_config_file
and edit with appropriate values
")
        ;
    exit(-1);
}
            
file_perms_must_be( $use_config_file );
require $use_config_file;

if ( !$db ) {
    error_exit( "ERROR: no database specified" );
}

if ( $reqid && $gfacid ) {
    error_exit( "ERROR: only one id option can be specified" );
}

$existing_dbs = existing_dbs();


if ( !in_array( $db, $existing_dbs ) ) {
    error_exit( "ERROR: database '$db' does not exist" );
}

if ( !$reqid && !$gfacid ) {
    # summary info for db
    $res = db_obj_result( $db_handle, "select HPCAnalysisRequestID from $db.HPCAnalysisRequest", True );
    $hpcreqs   = 0;
    $hpcress = 0;
    while( $row = mysqli_fetch_array($res) ) {
        $hpcreqid = $row['HPCAnalysisRequestID'];
        if ( $hpcreqs ) {
            if ( $hpcreqmin > $hpcreqid ) {
                $hpcreqmin = $hpcreqid;
            }
            if ( $hpcreqmax < $hpcreqid ) {
                $hpcreqmax = $hpcreqid;
            }
        } else {
            $hpcreqmin = $hpcreqid;
            $hpcreqmax = $hpcreqid;
        }
        $hpcreqs++;
    }
    

    $res = db_obj_result( $db_handle, "select HPCAnalysisResultID from $db.HPCAnalysisResult", True );
    while( $row = mysqli_fetch_array($res) ) {
        $hpcresid = $row['HPCAnalysisResultID'];
        if ( $hpcress ) {
            if ( $hpcresmin > $hpcresid ) {
                $hpcresmin = $hpcresid;
            }
            if ( $hpcresmax < $hpcresid ) {
                $hpcresmax = $hpcresid;
            }
        } else {
            $hpcresmin = $hpcresid;
            $hpcresmax = $hpcresid;
        }
        $hpcress++;
    }
    
    if ( $hpcreqs ) {
        echo "HPCAnalysisRequest count $hpcreqs id range $hpcreqmin:$hpcreqmax\n";
    } else {
        echo "HPCAnalysisRequest count $hpcreqs\n";
    }

    if ( $hpcreqs ) {
        echo "HPCAnalysisResult  count $hpcress id range $hpcresmin:$hpcresmax\n";
    } else {
        echo "HPCAnalysisResult  count $hpcress\n";
    }
    exit(0);
}

function gfacqmout( $id ) {
    global $fullrpt;
    global $db;
    global $db_handle;

    $res = db_obj_result( $db_handle, "select *  from gfac.queue_messages where analysisID=\"$id\" order by messageID", true, true );

    $out =
        echoline( '-', 80, false )
        ;

    if ( !$res ) {
        return $out . "gfac.queue_messages    [currently none]\n";
    }

    $out .= 
        "gfac.queue_messages\n"
        ;
    
    while( $row = mysqli_fetch_array($res) ) {
        $out .=
            echoline( '-', 80, false )
            . sprintf(
                "messageID              %s\n"
                . "analysisID             %s\n"
                . "message                %s\n"
                . "time                   %s\n"

                , $row[ 'messageID' ]
                , $row[ 'analysisID' ]
                , $row[ 'message' ]
                , $row[ 'time' ]
            )
            ;
    }
    return $out;
}

function gfacanalysisout( $gfacid ) {
    global $fullrpt;
    global $qmesgs;
    global $db;
    global $db_handle;

    $res = db_obj_result( $db_handle, "select *  from gfac.analysis where gfacID=\"$gfacid\"", true, true );

    $out =
        echoline( '-', 80, false )
        ;

    if ( !$res ) {
        return $out . "gfac.analysis          [currently none]\n";
    }

    $out .= 
        "gfac.analysis\n"
        ;
    
    while( $row = mysqli_fetch_array($res) ) {

        if ( !$fullrpt ) {
            $tmp = explode( "\n", trim( $row[ 'stdout' ] ) ); $row[ 'stdout' ] = end( $tmp );
            $tmp = explode( "\n", trim( $row[ 'stderr' ] ) ); $row[ 'stderr' ] = end( $tmp );
        }

        $out .=
            echoline( '-', 80, false )
            . sprintf(
                "id                     %s\n"
                . "gfacID                 %s\n"
                . "cluster                %s\n"
                . "us3_db                 %s\n"
                . "autoflowAnalysisID     %s\n"
                . "stdout                 %s\n"
                . "stderr                 %s\n"
                . "tarfile                %s\n"
                . "status                 %s\n"
                . "queue_msg              %s\n"
                . "time                   %s\n"
                , $row[ 'id' ]
                , $row[ 'gfacID' ]
                , $row[ 'cluster' ]
                , $row[ 'us3_db' ]
                , $row[ 'autoflowAnalysisID' ]
                , $row[ 'stdout' ]
                , $row[ 'stderr' ]
                , $row[ 'tarfile' ]
                , $row[ 'status' ]
                , $row[ 'queue_msg' ]
                , $row[ 'time' ]
            )
            ;

        if ( $qmesgs ) {
            $out .= gfacqmout( $row[ 'id' ] );
        }
    }
    return $out;
}

function hpcreqout( $reqid ) {
    global $fullrpt;
    global $db;
    global $db_handle;

    $res = db_obj_result( $db_handle, "select *  from $db.HPCAnalysisRequest where HPCAnalysisRequestID=\"$reqid\"" );
    if ( !$fullrpt ) {
        $tmp = explode( "\n", trim( $res->{ 'requestXMLFile' } ) ); $res->{ 'requestXMLFile' } = end( $tmp );
    }
    return
        echoline( '-', 80, false )
        . "HPCAnalysisRequest\n"
        . echoline( '-', 80, false )
        . sprintf(
            "HPCAnalysisRequestID   %s\n"
            . "HPCAnalysisRequestGUID %s\n"
            . "investigatorGUID       %s\n"
            . "submitterGUID          %s\n"
            . "email                  %s\n"
            . "experimentID           %s\n"
            . "requestXMLFile         %s\n"
            . "editXMLFilename        %s\n"
            . "submitTime             %s\n"
            . "clusterName            %s\n"
            . "method                 %s\n"
            . "analType               %s\n"

            , $res->{ 'HPCAnalysisRequestID' }
            , $res->{ 'HPCAnalysisRequestGUID' }
            , $res->{ 'investigatorGUID' }
            , $res->{ 'submitterGUID' }
            , $res->{ 'email' }
            , $res->{ 'experimentID' }
            , $res->{ 'requestXMLFile' }
            , $res->{ 'editXMLFilename' }
            , $res->{ 'submitTime' }
            , $res->{ 'clusterName' }
            , $res->{ 'method' }
            , $res->{ 'analType' }
        )
        ;
}    

function hpcresbyreqout( $reqid, $reqisgfac = false ) {
    global $fullrpt;
    global $db;
    global $db_handle;
    $out =
        echoline( '-', 80, false )
        . "HPCAnalysisResult(s)\n"
        ;

    if ( $reqisgfac ) {
        $res = db_obj_result( $db_handle, "select *  from $db.HPCAnalysisResult where gfacID=\"$reqid\"", True );
    } else {
        $res = db_obj_result( $db_handle, "select *  from $db.HPCAnalysisResult where HPCAnalysisRequestID=\"$reqid\"", True );
    }
    while( $row = mysqli_fetch_array($res) ) {
        if ( !$fullrpt ) {
            $tmp = explode( "\n", trim( $row[ 'jobfile' ] ) ); $row[ 'jobfile' ] = end( $tmp );
            $tmp = explode( "\n", trim( $row[ 'stderr'  ] ) ); $row[ 'stderr'  ] = end( $tmp );
            $tmp = explode( "\n", trim( $row[ 'stdout'  ] ) ); $row[ 'stdout'  ] = end( $tmp );
        }
        # debug_json( "row", $row );
        $out .=
            echoline( '-', 80, false )
            . sprintf(
                "HPCAnalysisResultID    %s\n"
                . "HPCAnalysisRequestID   %s\n"
                . "startTime              %s\n"
                . "endTime                %s\n"
                . "queueStatus            %s\n"
                . "lastMessage            %s\n"
                . "updateTime             %s\n"
                . "gfacID                 %s\n"
                . "jobfile                %s\n"
                . "wallTime               %s\n"
                . "CPUTime                %s\n"
                . "CPUCount               %s\n"
                . "mgroupcount            %s\n"
                . "max_rss                %s\n"
                . "calculatedData         %s\n"
                . "stderr                 %s\n"
                . "stdout                 %s\n"
                
                , $row[ 'HPCAnalysisResultID' ]
                , $row[ 'HPCAnalysisRequestID' ]
                , $row[ 'startTime' ]
                , $row[ 'endTime' ]
                , $row[ 'queueStatus' ]
                , $row[ 'lastMessage' ]
                , $row[ 'updateTime' ]
                , $row[ 'gfacID' ]
                , $row[ 'jobfile' ]
                , $row[ 'wallTime' ]
                , $row[ 'CPUTime' ]
                , $row[ 'CPUCount' ]
                , $row[ 'mgroupcount' ]
                , $row[ 'max_rss' ]
                , $row[ 'calculatedData' ]
                , $row[ 'stderr' ]
                , $row[ 'stdout' ]
            )
            ;
        if ( !$reqisgfac ) {
            $out .= gfacanalysisout( $row[ 'gfacID' ] );
        } else {
            $out .= hpcreqout( $row[ 'HPCAnalysisRequestID' ] );
        }
    }
    return $out;
}

if ( $reqid ) {
    $out = "";
    $out .= hpcreqout( $reqid );
    $out .= hpcresbyreqout( $reqid );
        
    echo $out;

    exit(0);
}

if ( $gfacid ) {
    $out = "";
    $out .= gfacanalysisout( $gfacid );
    $out .= hpcresbyreqout( $gfacid, true );

    echo $out;
    exit(0);
}
