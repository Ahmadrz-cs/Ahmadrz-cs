#!/bin/sh

# Script options variables
FORCE_DEFAULTS="false"

# Other script variables
dotenv_local=".env.local"
dotenv_local_test=".env.test.local"

# Print instructions and exit
usage () {
        echo "Usage: $(basename $0) [-hf]" 2>&1
        echo
        echo "Configure '.env.local' and '.env.test.local' files for the project"
        echo
        echo "   -f   Apply defaults even if there are existing dotenv files"
        echo "   -h   Show available options"
        echo
        exit 1
}

# Resolve options
while getopts :hf arg; do
  case ${arg} in
    f)
      FORCE_DEFAULTS="true"
      echo "Forcefully applying defaults. Existing files will be overwritten"
      ;;
    h)
      echo
      usage
      ;;
    ?)
      echo "Invalid option: -${OPTARG}."
      echo
      usage
      ;;
  esac
done

# Add .env.local file if not exists or forcing defaults
if [ ! -e "$dotenv_local" ] || [ $FORCE_DEFAULTS = 'true' ] ; then
    echo "Adding default docker dev $dotenv_local"
    cp .devcontainer/dev/.env.docker "$dotenv_local"
else
    echo "$dotenv_local found, keeping existing"
fi

# # Add .env.test.local file if not exists or forcing defaults
# if [ ! -e "$dotenv_local_test" ] || [ $FORCE_DEFAULTS = 'true' ] ; then
#     echo "Adding default docker dev $dotenv_local_test"
#     cp .devcontainer/dev/.env.test.docker $dotenv_local_test
# else
#     echo "$dotenv_local_test found, keeping existing"
# fi