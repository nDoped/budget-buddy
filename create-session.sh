#!/bin/bash

session_name='budget'
git_win='git'
dev_win='vim'
terminal_win='terminal'
mysql_win='mysql'
debug_win='debug'
tests_win='tests'
imports_win='imports'
tmux has-session -t $session_name &> /dev/null

if [ $? != 0 ]; then
    tmux new-session -s $session_name -n $git_win -d
    tmux send-keys "cd app && gstat" Enter

    tmux new-window -n $dev_win
    tmux split-window -v
    tmux select-pane -U
    tmux send-keys "cd app " Enter
    tmux send-keys "sale npm run dev"
    tmux split-window -h
    tmux send-keys "cd app && systemctl --user start docker-desktop && sale up" Enter
    tmux select-pane -U
    tmux send-keys "cd app" Enter
    tmux send-keys "nvim "

    tmux new-window -n $mysql_win
    tmux send-keys "cd app && vim exact_model_budget.sql" Enter

    tmux new-window -n $tests_win
    tmux send-keys "cd app/tests && nvim Feature/DashboardTest.php" Enter
    tmux split-window -h
    tmux send-keys "cd app/" Enter
    tmux send-keys "sale artisan test"

    tmux new-window -n $debug_win
    tmux send-keys "cd app && nvim storage/logs/laravel.log" Enter

    tmux new-window -n $imports_win
    tmux send-keys "cd app" Enter
    tmux send-keys "#sale artisan import:month 2023-01.csv 1" Enter
    tmux split-window -h
    tmux send-keys "cd app/storage/imports/" Enter
    tmux send-keys "nvim -p *" Enter


    tmux select-window -t $session_name:$dev_win
    tmux select-pane -L
fi

tmux attach -t $session_name
