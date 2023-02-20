#!/bin/bash

session_name='budget'
server_win='server'
dev_win='vim'
terminal_win='terminal'
mysql_win='mysql'
debug_win='debug'
tests_win='tests'
imports_win='imports'
tmux has-session -t $session_name &> /dev/null

if [ $? != 0 ]; then
    tmux new-session -s $session_name -n $server_win -d
    tmux send-keys "cd app && gstat" Enter
    tmux split-window -h
    tmux send-keys "cd app" Enter
    tmux send-keys "sale npm run dev"
    tmux split-window -v
    tmux send-keys "cd app && sale up" Enter

    tmux new-window -n $dev_win
    tmux send-keys "cd app" Enter

    tmux new-window -n $terminal_win
    tmux send-keys "cd app" Enter

    tmux new-window -n $mysql_win
    tmux send-keys "cd app" Enter
    tmux send-keys "sale mysql"

    tmux new-window -n $debug_win
    tmux send-keys "cd app" Enter
    tmux send-keys "vim storage/logs/laravel.log" Enter

    tmux new-window -n $tests_win
    tmux send-keys "cd app/tests" Enter

    tmux new-window -n $imports_win
    tmux send-keys "cd app/storage/imports/" Enter
    tmux send-keys "#sale artisan import:month 2023-01.csv 1" Enter
    tmux split-window -h
    tmux send-keys "cd app/storage/imports/" Enter
    tmux send-keys "vim -p *" Enter


    tmux select-window -t $session_name:$dev_win
    tmux select-pane -L
fi

tmux attach -t $session_name
