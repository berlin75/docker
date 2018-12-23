<?php
class MQ{

    //use Trait_Redis;

    private $maxProcesses = 800;
    private $child;
    private $masterRedis;
    private $redis;
    private $redis_task_wing = 'task:wing'; //待处理队列

    public function __construct(){
        // install signal handler for dead kids
        swoole_process::signal(SIGCHLD, array($this, "sig_handler"));
        set_time_limit(0);
        ini_set('default_socket_timeout', -1); //队列处理不超时,解决redis报错:read error on connection
    }

    private function sig_handler($signo) {
        switch ($signo) {
            case SIGCHLD:
                while($ret = swoole_process::wait(false)) {
                    // echo "PID={$ret['pid']}\n";
                    $this->child--;
                }
        }
    }

    public function testAction(){
        $process = new swoole_process(function(swoole_process $worker){
            for ($i = 0; $i < 100; $i++){
                $data = [ 'abc' => $i, 'timestamp' => time().rand(100,999) ];
                $worker->push(json_encode($data));
            }
        });
        $process->useQueue(1, 2 | swoole_process::IPC_NOWAIT);
        $process->start();
    }

    public function runAction(){
        $process = new swoole_process(function(swoole_process $worker){
            $msg = $worker->pop();
            echo "From Master: $msg\n";
        });
        $process->useQueue(1, 2 | swoole_process::IPC_NOWAIT);
        $pid = $process->start();
        /*
        while (1){
            if ($this->child < $this->maxProcesses){
                $data_pop = $this->redis->brpop($this->redis_task_wing, 3);//无任务时,阻塞等待
                if (!$data_pop) continue;
                echo "\t Starting new child | now we de have $this->child child processes\n";
                $this->child++;
                
            }
        }
        */
    }

    public function process(swoole_process $worker){// 第一个处理
        $GLOBALS['worker'] = $worker;
        swoole_event_add($worker->pipe, function($pipe) {
            $worker = $GLOBALS['worker'];
            $recv = $worker->read();            //send data to master

            sleep(rand(1, 3));
            echo "From Master: $recv\n";
            $worker->exit(0);
        });
        exit;
    }
}

$mq = new MQ;
$mq->testAction();
// $mq->runAction();


/*
$worker_num = 2;
$process_pool = [];
 
$process= null;
$pid = posix_getpid();
 
function sub_process(swoole_process $worker){
    sleep(1);        //防止父进程还未往消息队列中加入内容直接退出
    echo "worker ".$worker->pid." started".PHP_EOL;
    while($msg = $worker->pop()){
        if($msg === false) break;
        $sub_pid = $worker->pid;
        echo "[$sub_pid] msg : $msg".PHP_EOL;
        sleep(1);    //这里的sleep模拟任务耗时,否则可能1个worker就把所有信息全接受了
    }
    echo "worker ".$worker->pid." exit".PHP_EOL;
    $worker->exit(0);
}
 
$customMsgKey = 1;
$mod = 2 | swoole_process::IPC_NOWAIT;     //这里设置消息队列为非阻塞模式
 
//创建worker进程
for($i=0;$i<$worker_num; $i++) {
    $process=new swoole_process('sub_process');
    $process->useQueue($customMsgKey, $mod);
    $process->start();
    $pid = $process->pid;
    $process_pool[$pid] = $process;
}
 
$messages = [
    "Hello World!",
    "Hello Cat!",
    "Hello King",
    "Hello Leon",
    "Hello Rose"
];
//由于所有进程是共享使用一个消息队列,所以只需向一个子进程发送消息即可
$process = current($process_pool);
foreach ($messages as $msg) {
    $process->push($msg);
}
 
swoole_process::wait();
swoole_process::wait();
 
echo "master exit".PHP_EOL;

*/

/*
declare(ticks = 1);
class MQ{

    //use Trait_Redis;

    private $maxProcesses = 800;
    private $child;
    private $masterRedis;
    private $redis;
    private $redis_task_wing = 'task:wing'; //待处理队列

    public function __construct(){
        // install signal handler for dead kids
        pcntl_signal(SIGCHLD, array($this, "sig_handler"));
        set_time_limit(0);
        ini_set('default_socket_timeout', -1); //队列处理不超时,解决redis报错:read error on connection

        $redis = new \Redis(); 
        $redis->pconnect('127.0.0.1');
        $this->redis = $redis;
    }

    private function sig_handler($signo) {
        // echo "Recive: $signo \r\n";
        switch ($signo) {
            case SIGCHLD:
                while($ret = swoole_process::wait(false)) {
                    // echo "PID={$ret['pid']}\n";
                    $this->child--;
                }
        }
    }

    public function testAction(){
        for ($i = 0; $i < 100; $i++){
            $data = [
                'abc' => $i,
                'timestamp' => time().rand(100,999)
            ];
            $this->redis->lpush($this->redis_task_wing, json_encode($data));
        }
        exit;
    }

    public function runAction(){
        while (1){
            if ($this->child < $this->maxProcesses){
                $data_pop = $this->redis->brpop($this->redis_task_wing, 3);//无任务时,阻塞等待
                if (!$data_pop) continue;
                echo "\t Starting new child | now we de have $this->child child processes\n";
                $this->child++;
                $process = new swoole_process([$this, 'process']);
                $process->write(json_encode($data_pop));
                $pid = $process->start();
            }
        }
    }

    public function process(swoole_process $worker){// 第一个处理
        $GLOBALS['worker'] = $worker;
        swoole_event_add($worker->pipe, function($pipe) {
            $worker = $GLOBALS['worker'];
            $recv = $worker->read();            //send data to master

            sleep(rand(1, 3));
            echo "From Master: $recv\n";
            $worker->exit(0);
        });
        exit;
    }
}

$mq = new MQ;
$mq->testAction();
// $mq->runAction();

*/