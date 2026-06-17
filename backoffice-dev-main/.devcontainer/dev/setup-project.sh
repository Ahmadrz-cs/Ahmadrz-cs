#!/bin/sh

# Script options variables
FORCE_DEFAULTS="false"
SETUP_DATABASE="false"

# Other script variables
codeception_config="codeception.yml"
vendor_path="vendor"
node_modules_path="node_modules"
git_completion="git-completion.bash"
git_completion_url="https://raw.githubusercontent.com/git/git/master/contrib/completion/git-completion.bash"

# Load in variables from the .env defaults
source $(dirname "$0")/.env.docker

# Print instructions and exit
usage () {
        echo
        echo "Usage: $(basename $0) [-fdh]" 2>&1
        echo
        echo "Execute first time setup for the project"
        echo "Configures codeception, install dependencies, setup database, symfony-cli"
        echo
        echo "   -f   Apply first time setup defaults even if there are existing files"
        echo "   -d   Setup database with schema and fixtures (overwrites existing data)"
        echo "   -h   Show available options"
        echo
        exit 1
}

# Resolve options
while getopts :fdh arg; do
  case ${arg} in
    f)
      FORCE_DEFAULTS="true"
      echo "Forcefully applying defaults. Existing files will be overwritten"
      ;;
    d)
      SETUP_DATABASE="true"
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

# Add the docker specific codeception config if not exists or forcing defaults
if [ ! -e "$codeception_config" ] || [ $FORCE_DEFAULTS = 'true' ] ; then
    echo "Using default docker dev $codeception_config"
    cp .devcontainer/dev/codeception.docker.yml $codeception_config
    vendor/bin/codecept build
    echo
else
    echo "$codeception_config found, keeping existing"
fi

# Clean database then load schema and development fixtures
# Only run if vendor/doctrine directory exists and setup database option is on
if [ -d "$vendor_path/doctrine" ] && [ $SETUP_DATABASE = 'true' ] ; then
    echo "Setting up database schema and loading initial data fixtures"
    php bin/console doctrine:schema:drop --full-database --force
    php bin/console doctrine:schema:update --force --complete
    php bin/console doctrine:fixtures:load --no-interaction --group=DevFixtures

    echo "Loading database views"
    mysql -h db -u $DATABASE_USER -p$DATABASE_PASSWORD crowddb < src/SQLReports/ViewSetup.sql

    echo "Syncing doctrine migrations"
    php bin/console doctrine:migrations:sync-metadata-storage
    php bin/console doctrine:migrations:version --add --all --no-interaction
else
    echo "Use option '-d' with '$(basename $0)' to setup the database"
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

# Install symfony binary if not exists
if  [ -e "$HOME/.symfony5/bin/symfony" ] && grep -q 'export PATH=$HOME/.symfony5/bin:$PATH' "$HOME/.bashrc" ; then
    echo "Symfony binary already setup"
else
    if [ ! -e "$HOME/.symfony5/bin/symfony" ] ; then
        echo "Downloading and installing Symfony binary"
        wget https://get.symfony.com/cli/installer -O - | bash
    fi
    # Note single quotes when grep-ing to prevent variable expansion
    if ! grep -q 'export PATH=$HOME/.symfony5/bin:$PATH' "$HOME/.bashrc" ; then
        echo "Adding Symfony binary to PATH"
        echo >> "$HOME/.bashrc" # Add new line 
        # Note single quotes when echoing to prevent variable expansion
        echo 'export PATH=$HOME/.symfony5/bin:$PATH' >> "$HOME/.bashrc"
    fi
fi

# Install mago binary if not exists
if  [ -e "$HOME/.mago/bin/mago" ] && grep -q 'export PATH=$HOME/.mago/bin:$PATH' "$HOME/.bashrc" ; then
    echo "Mago binary already setup. Version: $($HOME/.mago/bin/mago --version)"
else
    if [ ! -d "$HOME/.mago/bin" ]; then
        echo "Creating directory in $HOME for Mago"
        mkdir -p $HOME/.mago/bin
    fi
    if [ ! -e "$HOME/.mago/bin/mago" ] ; then
        echo "Downloading and installing Mago binary"
        wget -qO- https://carthage.software/mago.sh | bash -s -- --install-dir=$HOME/.mago/bin
    fi
    # Note single quotes when grep-ing to prevent variable expansion
    if ! grep -q 'export PATH=$HOME/.mago/bin:$PATH' "$HOME/.bashrc" ; then
        echo "Adding Mago binary to PATH"
        echo >> "$HOME/.bashrc" # Add new line 
        # Note single quotes when echoing to prevent variable expansion
        echo 'export PATH=$HOME/.mago/bin:$PATH' >> "$HOME/.bashrc"
    fi
    # Note that `mago` by itself may not be available as we've just added it to PATH
    echo "Version: $($HOME/.mago/bin/mago --version)"
fi

# Run npm install if node_modules directory doesn't exist or forcing defaults
if [ ! -d "$node_modules_path" ] || [ $FORCE_DEFAULTS = 'true' ] ; then
    echo "Executing npm install for first time setup"
    npm install
    echo "Run webpack build for first time setup"
    npm run dev
else
    echo "$node_modules_path directory found, you can manually run 'npm install' and 'npm run dev' if required"
fi