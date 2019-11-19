#!/bin/bash
# Program:
#   php start ProcessManager script
# History:
# 2019.11.19 geekwho first release.

php ProcessManager.php &
echo "服务已启动，PID: $!"
