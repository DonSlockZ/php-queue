<?php
require_once __DIR__ . "/../src/PhpQueue/AutoLoader.php";

use PhpQueue\Queue;
use PhpQueue\AutoLoader;
use PhpQueue\Drivers\FileDriver;
use PhpQueue\TaskPerformer;

AutoLoader::RegisterDirectory(array('Callbacks', 'Tasks/Example'));
AutoLoader::RegisterNamespaces(array('PhpQueue' => '../src/PhpQueue'));
AutoLoader::RegisterAutoLoader();

$driver = new FileDriver('file_queue');

function delete($path)
{
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    /**
     * @var \SplFileInfo[] $it
     */
    foreach ($it as $file) {
        if (in_array($file->getBasename(), array('.', '..'))) {
            continue;
        } elseif ($file->isDir()) {
            rmdir($file->getPathname());
        } elseif ($file->isFile() || $file->isLink()) {
            unlink($file->getPathname());
        }
    }
}


delete(__DIR__ . '/file_queue');

$queue = new Queue($driver);

//one task
$task = new \PhpQueue\Task("Job");
$task
    ->set_exclusive(true)
    ->sub_tasks()
    ->add(new \PhpQueue\Task("Job1", 5))
    ->add(new \PhpQueue\Task("Job1", 10))
    ->add(new \PhpQueue\Task("Job1", 15))
    ->add(new \PhpQueue\Task("Job1", 15))
    ->add(new \PhpQueue\Task("Job1", 15))
    ->add(new \PhpQueue\Task("Job1", 15))
    ->add(new \PhpQueue\Task("Job1", 15));

$queue->add_task($task);

$task = new \PhpQueue\Task("Job");
//$task->set_priority(2);
$queue->add_task($task);

$task = new \PhpQueue\Task("Job");
//$task->set_priority(1);
$queue->add_task($task);

$task = new \PhpQueue\Task("Job");
//$task->set_priority(3);
$queue->add_task($task);

$task_performer = new \PhpQueue\TaskPerformer();

for ($i = 0; $i < 10; $i++) {
    $new_task = $queue->get_task();

    if (!$new_task) break;

    $new_task = $task_performer->execute_task($new_task);

    $queue->modify_task($new_task);
}