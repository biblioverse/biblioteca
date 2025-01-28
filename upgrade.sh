#/bin/bash
#
# This script is used to upgrade the application to the latest version using docker compose setup.
# !! It will perform a hard reset and loose local changes !!
#
# Usage: ./upgrade.sh [origin/main|branch] [--no-npm] [--fast]
#
set -e

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
cd $SCRIPT_DIR

# Parse the remote/branch to use
BRANCH="main"
REMOTE="origin"

# Parse args
FLAG_NO_NPM=false
FLAG_FAST=false
for arg in "$@"; do
  case $arg in
    --fast)
      echo "Fast mode enabled, this is not recommended" >&2
      FLAG_FAST=true
      ;;
    --no-npm)
      echo "NPM build is skipped" >&2
      FLAG_NO_NPM=true
      ;;
    default)
      if echo "$arg" | grep -q '/'; then
        REMOTE=$(echo "$arg" | cut -d'/' -f1)
        BRANCH=$(echo "$arg" | cut -d'/' -f2)
      else
        BRANCH=$arg
      fi
      ;;
  esac
done

# Fetch the latest version and discard local changes
echo "Upgrading to $REMOTE/$BRANCH."
if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  echo "You have local changes, they will be lost !"  >&2
fi
# Confirm with the user
if [ "$NO_INTERACTIVE" != "1" ]; then
  read -p "Are you sure you want to continue? (y/n): " CONFIRM
  if [[ "$CONFIRM" != "y" ]]; then
    echo "Upgrade aborted."
    exit 1
  fi
fi

git fetch
git reset --hard ${REMOTE}/${BRANCH}

# Dirty update when fast mode
if $FLAG_FAST; then
 rm -rf var/cache/*
 exit 0
fi

# Update the containers
docker compose pull
docker compose build

# Install dependencies, migrate db, clear cache
if ! $NO_COMPOSER; then
  docker compose run --rm biblioteca composer install
  docker compose run --rm biblioteca bin/console doctrine:migration:migrate -n
fi

# Build frontend assets
if ! $NO_NPM; then
  docker compose run --rm biblioteca npm install
  docker compose run --rm biblioteca npm run build
fi

# restart the containers, clear cache, index data
docker compose up -d --force-recreate --remove-orphans
docker compose exec biblioteca bin/console cache:clear --env=prod

# wait for typesense to be ready
tries=0
while ! curl -s http://localhost:8108/health >/dev/null 2>&1; do
  echo -n "."
  tries=$((tries + 1))
  sleep 1
  if [ $tries -gt 30 ]; then
    echo "Typesense is not ready, skipping wait." >&2
    break
  fi
done

docker compose exec biblioteca bin/console biblioverse:typesense:populate --env=prod
