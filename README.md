# PHP ProcessManager 示例代码

本仓库包含使用 PHP 的 `pcntl` 扩展创建和管理子进程的示例。

## 文件

### ProcessManager.php

此脚本演示如何：

1. 使用 `pcntl_fork` 创建父进程和子进程。父进程监听子进程退出状态，并支持扩展逻辑。
2. 在子进程中运行一个简单的任务循环，并支持优雅停止。
3. 处理 `SIGTERM`、`SIGHUP` 和 `SIGUSR1` 等信号。

#### 功能

- **信号处理**：

  - `SIGTERM`: 用于触发子进程的优雅退出。

  - `SIGHUP`: (当前实现) 打印重启消息，可在此扩展重启逻辑。

  - `SIGUSR1`: (当前实现) 打印自定义操作消息，可在此添加自定义逻辑。

- **子进程循环**：子进程每秒打印包含时间戳的消息，并响应优雅停止信号。

- **父进程监控**：父进程可监听子进程退出状态，并支持扩展逻辑（如重新启动子进程）。守护进程模式，支持后台运行（功能待实现）。

#### 使用方法

1. 确保已安装 `pcntl` 扩展：

   ```bash
   php -m | grep pcntl
   ```

   如果未安装，请参考 PHP 安装指南启用该扩展。

2. 启动和停止脚本：

   ```bash
    # 启动 ProcessManager
    ./start_process_manager.sh
    # 停止 ProcessManager
    ./stop_process_manager.sh
   ```

3. 向子进程发送信号：
   - 先找到脚本的父进程 ID (PID)：

     ```bash
     parent_process="php ProcessManager.php"
     parent_pid=$(ps -ef | grep "$parent_process" | grep -v grep | awk '$3 != 1 {print $3}')
     ```

   - 通过父进程找到子进程PID

     ```bash
     child_pids_array=($(ps -ef | grep -w "$parent_pid" | grep "$parent_process" | grep -v grep | awk '{if ($3 == '$parent_pid' && $2 != '$parent_pid') print $2}'))
     ```

   - 对子进程PID发送信号：

     ```bash
     kill -SIGTERM <PID>
     ```

#### 示例输出

```bash
父进程 (PID: 123), 让子进程 (PID: 456) 运行...
FORK: 子进程 (PID: 456) 准备运行...
子进程 (PID: 456) 每 1 秒运行一次。时间: 1690000000
子进程 (PID: 456) 每 1 秒运行一次。时间: 1690000001
子进程 (PID: 456) 每 1 秒运行一次。时间: 1690000003
子进程 (PID: 456) 收到优雅停止信号，正在退出...
父进程 (PID: 123) 监听到子进程 (PID: 456) 退出，退出状态为: 0
父进程 (PID: 123) run 方法执行完毕，准备退出。
```

## 环境要求

- PHP 7.1 或更高版本
- 启用 `pcntl` 扩展
- CLI 环境

## 许可证

本项目基于 MIT 许可证。
