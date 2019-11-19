<?php

/**
 * 通过pcntl扩展创建子进程
 * 1. 创建一个主进程和子进程。
 * 2. 子进程运行的函数是无限循环。
 * 3. 支持SIGTERM信号退出。
 * 
 * @Author: GeekWho
 * @Date:   2019-11-17
 * @Last Modified by:   GeekWho
 * @Last Modified time: 2019-11-19
 */
class ProcessManager
{
    private $gracefulStopSignal = false; // 优雅停止标识
    private $parentPid;
    private $childPid;

    /**
     * 执行初始化
     */
    public function run()
    {
        $this->checkEnvironment();
        $this->initializeProcess();
        $this->cleanup();
    }

    /**
     * 检查运行环境是否符合要求
     */
    private function checkEnvironment()
    {
        version_compare(PHP_VERSION, '7.1', '>=') or die('PHP version must be >= 7.1');
        extension_loaded('pcntl') or die('pcntl extension is not installed');
        php_sapi_name() === "cli" or die('only run cli');
    }

    /**
     * 初始化进程，包括信号处理和子进程创建
     */
    private function initializeProcess()
    {
        $this->parentPid = getmypid();
        $this->setupSignalHandlers();
        $pid = pcntl_fork();
        switch ($pid) {
            case -1:
                die('Fork failed');
                break;

            case 0:
                $this->childPid = getmypid();
                echo "FORK: 子进程 (PID: {$this->childPid}) 准备运行..." . PHP_EOL;
                $this->runWorker();
                echo "FORK: 子进程 (PID: {$this->childPid}) runWorker 方法执行完毕，准备退出。" . PHP_EOL;
                exit(0); // 显式退出子进程
                break;

            default:
                $this->childPid = $pid;
                echo "父进程 (PID: {$this->parentPid}), 让子进程 (PID: {$this->childPid}) 运行..." . PHP_EOL;
                /**
                 * pcntl_wait($status) 函数的作用
                 * 使父进程阻塞（暂停执行），直到它的一个子进程退出或接收到一个信号。
                 * 一旦子进程退出，pcntl_wait 函数就会返回子进程的 PID。
                 * 父进程会继续执行 pcntl_wait 之后的代码。
                 */
                $childPid = pcntl_wait($status);
                // 等待子进程的状态
                echo "父进程 (PID: {$this->parentPid}) 监听到子进程 (PID: {$childPid}) 退出，退出状态为: {$status}" . PHP_EOL;
                // 不想让父进程直接退出，可以执行函数 parentPidRunAlways() 让父进程持续运行
                break;
        }
    }

    /**
     * 进程退出清理逻辑
     */
    private function cleanup()
    {
        echo "父进程 (PID: {$this->parentPid}) run 方法执行完毕，准备退出。" . PHP_EOL;
    }

    /**
     * 子进程运行逻辑
     */
    private function runWorker()
    {
        while (true) {
            if ($this->gracefulStopSignal) {
                echo "子进程 (PID: {$this->childPid}) 收到优雅停止信号，正在退出..." . PHP_EOL;
                exit(0);
            }
            echo "子进程 (PID: {$this->childPid}) 每 1 秒运行一次。时间: " . time() . PHP_EOL;
            sleep(1);
        }
    }

    /**
     * 注册信号处理
     */
    private function setupSignalHandlers()
    {
        // 注册信号处理函数
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGHUP,  [$this, 'signalHandler']);
        pcntl_signal(SIGUSR1, [$this, 'signalHandler']);
    }

    /**
     * 信号处理函数
     */
    private function signalHandler($signo)
    {
        switch ($signo) {
            case SIGTERM:
                $this->handleSigterm();
                break;
            case SIGHUP:
                $this->handleSighup();
                break;
            case SIGUSR1:
                $this->handleSigusr1();
                break;
            default:
                echo "父进程 (PID: {$this->parentPid}) 或子进程 (PID: {$this->childPid}) 收到未知信号: {$signo}，退出中..." . PHP_EOL;
                break;
        }
    }

    /**
     * 默认情况下，当一个进程（无论是父进程还是其他进程）向进程组或特定进程发送信号时，该信号会被传递给该进程及其所有属于同一进程组的子进程。
     * 关键在于 pcntl_signal() 函数是在父进程和子进程中分别注册的。 当 pcntl_fork() 创建子进程时，子进程会复制父进程的进程空间，包括已经注册的信号处理函数。
     * @return void
     */
    private function handleSigterm()
    {
        echo "父进程 (PID: {$this->parentPid}) 或子进程 (PID: {$this->childPid}) 收到 SIGTERM 信号，优雅停止..." . PHP_EOL;
        $this->gracefulStopSignal = true;
    }

    private function handleSighup()
    {
        echo "父进程 (PID: {$this->parentPid}) 或子进程 (PID: {$this->childPid}) 收到 SIGHUP 信号，重启任务..." . PHP_EOL;
        // 添加重启逻辑
    }

    private function handleSigusr1()
    {
        echo "父进程 (PID: {$this->parentPid}) 或子进程 (PID: {$this->childPid}) 收到 SIGUSR1 信号，自定义操作..." . PHP_EOL;
        // 添加自定义逻辑
    }

    private function parentPidRunAlways()
    {
        while (true) {
            $childPid = pcntl_wait($status);
            if ($childPid > 0) {
                echo "父进程 (PID: {$this->parentPid}) 监听到子进程 (PID: {$childPid}) 退出，退出状态为: {$status}" . PHP_EOL;
                // 在这里可以添加父进程需要执行的逻辑，例如重新fork一个新的子进程
                break; // 这里为了演示父进程在子进程退出后跳出循环，您可以根据需求修改
            } elseif ($childPid === -1) {
                // 处理错误，例如信号中断
                if (pcntl_errno() !== 4) { // 4 是 EINTR 错误
                    echo "父进程 (PID: {$this->parentPid}) pcntl_wait 错误: " . pcntl_strerror(pcntl_errno()) . PHP_EOL;
                    break;
                }
                // 如果是信号中断，继续循环等待
            }
            sleep(1); // 防止 CPU 占用过高
        }
    }

    public function start()
    {
        echo "服务启动中..." . PHP_EOL;
        $this->run();
    }

    public function stop() {}

    /**
     * todo: 守护进程化
     *
     * @return void
     */
    private function daemonize() {}
}

(new ProcessManager)->run();
