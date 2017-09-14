--TEST--
Test GearmanJob::workload(), GearmanJob::workloadSize()
--SKIPIF--
<?php
require_once('skipif.inc');
require_once('skipifconnect.inc');
?>
--FILE--
<?php
require_once('connect.inc');

print "Start" . PHP_EOL;

$job_name = uniqid();

$pid = pcntl_fork();
if ($pid == -1) {
    die("Could not fork");
} else if ($pid > 0) {
    // Parent. This is the worker
    $worker = new GearmanWorker();
    $worker->addServer($host, $port);
    $worker->addFunction(
        $job_name,
        function($job, $data) {
            print "GearmanJob::workload (OO): "
                . $job->workload()
                . PHP_EOL;

            print "GearmanJob::workloadSize (OO): "
                . $job->workloadSize()
                . PHP_EOL;
            }
    );

    $worker->work();

    $worker->unregister($job_name);

    // Wait for child
    $exit_status = 0;
    if (pcntl_wait($exit_status) <= 0) {
        print "pcntl_wait exited with error" . PHP_EOL;
    } else if (!pcntl_wifexited($exit_status)) {
        print "child exited with error" . PHP_EOL;
    }
} else {
    //Child. This is the client. Don't echo anything here
    $client = new GearmanClient();
    if ($client->addServer($host, $port) !== true) {
        exit(1); // error
    };

    $tasks = [];
    $tasks[] = $client->addTask($job_name, "normal");
    $client->runTasks();
    if ($client->returnCode() != GEARMAN_SUCCESS) {
        exit(2); // error
    }
    exit(0);
}

print "Done";
--EXPECTF--
Start
GearmanJob::workload (OO): normal
GearmanJob::workloadSize (OO): 6
Done
