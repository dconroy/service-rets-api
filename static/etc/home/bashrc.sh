#!/bin/sh

alias service='sudo service'

alias gs='git status'
alias push='git add . --all && git commit -m "Automated push. [ci skip]" && git push'
alias pull='git fetch origin && git reset --hard HEAD && git clean -fd && git pull'

alias rm='rm -i'
alias cp='cp -i'
alias mv='mv -i'

alias ls='ls -hFG'
alias l='ls -lF'
alias ll='ls -alF'
alias lt='ls -ltrF'
alias ll='ls -alF'
alias lls='ls -alSrF'
alias llt='ls -altrF'

alias tarc='tar cvf'
alias tarcz='tar czvf'
alias tarx='tar xvf'
alias tarxz='tar xvzf'
alias untar='tar xvf'

alias g='git'
alias less='less -R'
alias os='lsb_release -a'
alias vi='vim'

# Colorize directory listing
alias ls="ls -ph --color=auto"

# Colorize grep
if echo hello|grep --color=auto l >/dev/null 2>&1; then
  export GREP_OPTIONS="--color=auto" GREP_COLOR="1;31"
fi

# Shell
export CLICOLOR="1"
if [ -f $HOME/.scripts/git-prompt.sh ]; then
  source $HOME/.scripts/git-prompt.sh
  export GIT_PS1_SHOWDIRTYSTATE="1"
  export PS1="\[\033[40m\]\[\033[34m\][ \u@\H:\[\033[36m\]\w\$(__git_ps1 \" \[\033[35m\]{\[\033[32m\]%s\[\033[35m\]}\")\[\033[34m\] ]$\[\033[0m\] "
else
  export PS1="\[\033[40m\]\[\033[34m\][ \u@\H:\[\033[36m\]\w\[\033[34m\] ]$\[\033[0m\] "
fi
export LS_COLORS="di=34:ln=35:so=32:pi=33:ex=1;40:bd=34;40:cd=34;40:su=0;40:sg=0;40:tw=0;40:ow=0;40:"

## CoreOS root Colors
## export PS1='\[\033[01;31m\]\h\[\033[01;34m\] \W \$\[\033[00m\] '

## CoreOs "core" colors
## export PS1='\[\033[01;32m\]\u@\h\[\033[01;34m\] \w \$\[\033[00m\]'

## Our brigher green colors.. Pretty gay I know, but what else to do while waiting for things to buid...
#export PS1="\[\033[30m\]\[\033[22m\]\u@\h\[\033[33m\] \w\[\033[33m\] $ \[\033[0m\]" # black
#export PS1="\[\033[31m\]\[\033[22m\]\u@\h\[\033[33m\] \w\[\033[33m\] $ \[\033[0m\]" # red
#export PS1="\[\033[38m\]\[\033[22m\]\u@\h\[\033[33m\] \w\[\033[33m\] $ \[\033[0m\]" # gray?
export PS1="\[\033[32m\]\[\033[22m\]\u@\h\[\033[33m\] \w\[\033[33m\] $ \[\033[0m\]" # green

## Git
if [ -f $HOME/.scripts/git-completion.sh ]; then
  source $HOME/.scripts/git-completion.sh
fi;

if [ -f /etc/bash_completion.d/docker.io ]; then
  source /etc/bash_completion.d/docker.io
fi;


