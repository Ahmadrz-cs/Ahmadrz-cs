#!/bin/sh

# Script options variables
FORCE_DEFAULTS="false"

# Other script variables
codeception_config="codeception.yml" # not used atm, pending update
codeception_generated="tests/_support/_generated/AcceptanceTesterActions.php"
vendor_path="vendor"
git_completion="git-completion.bash"
git_completion_url="https://raw.githubusercontent.com/git/git/master/contrib/completion/git-completion.bash"

# Print instructions and exit
usage () {
        echo
        echo "Usage: $(basename $0) [-fh]" 2>&1
        echo
        echo "Execute first time setup for the project"
        echo "Configures app parameters, build codeception, install dependencies"
        echo
        echo "   -f   Apply first time setup defaults even if there are existing files"
        echo "   -h   Show available options"
        echo
        exit 1
}

# Resolve options
while getopts :fh arg; do
  case ${arg} in
    f)
      FORCE_DEFAULTS="true"
      echo "Forcefully applying defaults. Existing files will be overwritten"
      ;;
    h)
      usage
      ;;
    ?)
      echo "Invalid option: -${OPTARG}."
      usage
      ;;
  esac
done

# Run composer install if vendor directory doesn't exist or forcing defaults
if [ ! -d "$vendor_path" ] || [ $FORCE_DEFAULTS = 'true' ] ; then
    echo "Executing composer install for first time setup"
    composer install --no-interaction
else
    echo "$vendor_path directory found, you can manually run 'composer install' if required"
fi

# Build codeception test files if not exists or forcing defaults
if [ ! -e "$codeception_generated" ] || [ $FORCE_DEFAULTS = 'true' ] ; then
    echo "Building codeception files"
    vendor/bin/codecept build
    echo
else
    echo "Codeception already built, use 'vendor/bin/codecept build' to manually renegerate"
fi

# Install git completion if not exists
if  [ -e "$HOME/$git_completion" ] && grep -q "$git_completion" "$HOME/.bashrc" ; then
    echo "git completion already setup"
else
    echo "Adding git completion bash script"
    curl $git_completion_url -o $HOME/$git_completion
    echo >> "$HOME/.bashrc" # Add new line 
    echo "[ -f ~/$git_completion ] && . ~/$git_completion" >> "$HOME/.bashrc"
fi
