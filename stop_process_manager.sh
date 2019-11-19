#!/bin/bash
# Program:
#   php stop ProcessManager script
# History:
# 2019.11.19 geekwho first release.

# 定义要关闭的父进程名称
parent_process="php ProcessManager.php"

# 查找父进程的 PID
parent_pid=$(ps -ef | grep "$parent_process" | grep -v grep | awk '$3 != 1 {print $3}')

if [ -z "$parent_pid" ]; then
  echo "未找到名为 '$parent_process' 的父进程。"
  exit 1 # 如果没有找到父进程，提前返回并返回错误码
fi

echo "找到父进程 PID: $parent_pid"

# 查找所有子进程的 PIDs
child_pids_array=($(ps -ef | grep -w "$parent_pid" | grep "$parent_process" | grep -v grep | awk '{if ($3 == '$parent_pid' && $2 != '$parent_pid') print $2}'))


if [ ${#child_pids_array[@]} -eq 0 ]; then
    echo "未找到父进程的子进程。"
    exit 0 # 如果没有子进程，直接返回
fi

echo "找到子进程 PIDs: ${child_pids_array[@]}"
echo "正在尝试优雅关闭子进程..."
for pid in "${child_pids_array[@]}"; do
    echo "尝试关闭子进程 PID: $pid"
    kill -SIGTERM "$pid"
    sleep 3 # 增加等待时间
done

# (可选) 检查是否所有子进程都已关闭
alive_children=$(ps -p "${child_pids_array[@]}" -o pid= | wc -l)
if [ "$alive_children" -gt 0 ]; then
    echo "警告：部分子进程在等待后仍然存活，但未被强制关闭。"
fi

parent_pid=$(ps -ef | grep "$parent_process" | grep -v grep | awk '$3 != 1 {print $3}')
if [ -n "$parent_pid" ]; then
    echo "正在关闭父进程 (PID: $parent_pid)..."
    kill -SIGTERM $parent_pid    # 发送 SIGTERM 信号尝试优雅关闭父进程
    sleep 5               # 等待一段时间让父进程优雅关闭
fi

echo "关闭进程操作完成。"