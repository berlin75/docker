<?php
$workers = [];
$worker_num = 3;
 
for($i = 0; $i < $worker_num; $i++){
    $process = new swoole_process('callback_function', false, false);  // 开启进程管道通信
    $process->useQueue();         // 开启当前进程的队列,类似全局函数
    $pid = $process->start();
    $workers[$pid] = $process;
}
 
function callback_function(swoole_process $worker){
    //echo "Worker: start. PID=".$worker->pid."\n";
    //recv data from master
    $recv = $worker->pop();
    echo "From Master: $recv\n";
    sleep(2);
    $worker->exit(0);
}
 
// 主进程向子进程添加数据
foreach($workers as $pid => $process){
    $process->push("hello worker[$pid]\n");
}
 
// 等待子进程结束,回收资源
for($i = 0; $i < $worker_num; $i++){
    $ret = swoole_process::wait();
    $pid = $ret['pid'];
    unset($workers[$pid]);
    echo "Worker Exit, PID=".$pid.PHP_EOL;
}