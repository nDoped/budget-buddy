#!/bin/bash

session_name='budget'
server_win='server'
dev_win='vim'
terminal_win='terminal'
mysql_win='mysql'
debug_win='debug'
tmux has-session -t $session_name &> /dev/null

if [ $? != 0 ]; then
    tmux new-session -s $session_name -n $server_win -d
    tmux send-keys "cd app && sale up" Enter
    tmux split-window -h
    tmux send-keys "cd app && sale npm run dev" Enter

    tmux new-window -n $dev_win
    tmux send-keys "cd app" Enter

    tmux new-window -n $terminal_win
    tmux send-keys "cd app" Enter

    tmux new-window -n $mysql_win
    tmux send-keys "cd app && sale mysql" Enter

    tmux new-window -n $debug_win
    tmux send-keys "cd app && vim storage/logs/laravel.log" Enter

    tmux select-window -t $session_name:$dev_win
    tmux select-pane -L
fi

tmux attach -t $session_name
