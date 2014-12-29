<?php

/*
 * Execute and display the output in real time (stdout + stderr).
 *
 * Please note this snippet is prepended with an appropriate shebang for the
 * CLI. You can re-use only the function.
 *
 * Usage example:
 * chmod u+x proc_open.php
 * ./proc_open.php "ping -c 5 google.fr"; echo RetVal=$?
 */
define('BUF_SIZ', 1024);        # max buffer size
define('FD_WRITE', 0);        # stdin
define('FD_READ', 1);        # stdout
define('FD_ERR', 2);        # stderr

/*
 * Wrapper for proc_*() functions.
 * The first parameter $cmd is the command line to execute.
 * Return the exit code of the process.
 */
function proc_exec($cmd)
{
    $errbuf = '';
    $first_exitcode = '';
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
    );

    $ptr = proc_open($cmd, $descriptorspec, $pipes, NULL, $_ENV);
    if (!is_resource($ptr))
        return false;

    while (($buffer = fgets($pipes[FD_READ], BUF_SIZ)) != NULL
        || ($errbuf = fgets($pipes[FD_ERR], BUF_SIZ)) != NULL) {
        if (!isset($flag)) {
            $pstatus = proc_get_status($ptr);
            $first_exitcode = $pstatus["exitcode"];
            $flag = true;
        }
        if (strlen($buffer))
            echo $buffer;
        if (strlen($errbuf))
            echo "ERR: " . $errbuf;
    }

    foreach ($pipes as $pipe)
        fclose($pipe);

    /* Get the expected *exit* code to return the value */
    $pstatus = proc_get_status($ptr);
    if (!strlen($pstatus["exitcode"]) || $pstatus["running"]) {
        /* we can trust the retval of proc_close() */
        if ($pstatus["running"])
            proc_terminate($ptr);
        $ret = proc_close($ptr);
    } else {
        if ((($first_exitcode + 256) % 256) == 255
            && (($pstatus["exitcode"] + 256) % 256) != 255
        )
            $ret = $pstatus["exitcode"];
        elseif (!strlen($first_exitcode))
            $ret = $pstatus["exitcode"];
        elseif ((($first_exitcode + 256) % 256) != 255)
            $ret = $first_exitcode;
        else
            $ret = 0; /* we "deduce" an EXIT_SUCCESS ;) */
        proc_close($ptr);
    }

    return ($ret + 256) % 256;
}