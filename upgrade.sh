#/bin/bash
#
# This script is used to upgrade the application to the latest version using docker compose setup.
# By default, you have a built container and you need only to pass the "--with-migrations" flag to upgrade the database schema.
# However, if you aim to develop the application, you have additional flags available.
#
# !! It will perform a hard reset on the file system. You will lost the file you edited locally (they will be updated to the latest release) !!
#
# Usage: ./upgrade.sh [origin/main|branch] [--with-migrations] [--with-npm] [--with-docker-build] [--with-composer] [--all]
#
set -e

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
cd $SCRIPT_DIR

# Parse the remote/branch to use
BRANCH="main"
REMOTE="origin"

# Parse args
FLAG_WITH_NPM=0
FLAG_FAST=0
FLAG_WITH_DOCKER_BUILD=0
FLAG_WITH_MIGRATIONS=0
FLAG_WITH_COMPOSER=0
FLAG_WITH_TYPESENSE=0
TYPESENSE_URL="http://typesense:8108/health"
for arg in "$@"; do
  case $arg in
    --fast)
      echo "Fast mode enabled, this is not recommended" >&2
      FLAG_FAST=1
      ;;
    --with-docker-build)
      echo "Build docker" >&2
      FLAG_WITH_DOCKER_BUILD=1
      ;;
    --with-npm)
        echo "Build NPM" >&2
        FLAG_WITH_NPM=1
        ;;
    --with-migrations)
        echo "With migration" >&2
        FLAG_WITH_MIGRATIONS=1
        ;;
    --with-composer)
        echo "With composer" >&2
        FLAG_WITH_COMPOSER=1
        ;;
    --with-typesense)
        echo "With typesense populate" >&2
        FLAG_WITH_TYPESENSE=1
        ;;
    --typesenseurl)
        TYPESENSE_URL=$2
        echo "With typesense_url ${TYPESENSE_URL}" >&2
        shift
        ;;
    --all)
        echo "Does all the build operations" >&2
        echo "- npm" >&2
        echo "- docker" >&2
        echo "- db migrations" >&2
        echo "- composer" >&2
        echo "- typesense" >&2
        FLAG_WITH_NPM=1
        FLAG_WITH_DOCKER_BUILD=1
        FLAG_WITH_MIGRATIONS=1
        FLAG_WITH_COMPOSER=1
        FLAG_WITH_TYPESENSE=1
        ;;
    *)
      if echo "$arg" | grep -q '/'; then
        REMOTE=$(echo "$arg" | cut -d'/' -f1)
        BRANCH=$(echo "$arg" | cut -d'/' -f2)
      else
        BRANCH=$arg
      fi
      ;;
  esac
done

# Keep the user on the same branch.
if [[ "$(git branch --show-current)" != "$BRANCH" && "$BRANCH" = "main" ]]; then
  echo "You are not on the default branch..."
  BRANCH=$(git branch --show-current)
fi

# Fetch the latest version and discard local changes
echo "Upgrading to $REMOTE/$BRANCH."
if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  echo "You have local changes, they will be lost !"  >&2
fi
# Confirm with the user
if [ "$NO_INTERACTIVE" != "1" ]; then
  read -p "Are you sure you want to continue (ctrl+c to abort) ? (y/n): " CONFIRM
  if [[ "$CONFIRM" != "y" ]]; then
    echo "Upgrade aborted."
    exit 1
  fi
fi

git fetch
git reset --hard ${REMOTE}/${BRANCH}


# Update the containers
docker compose pull

if [ $FLAG_WITH_DOCKER_BUILD -eq 1 ]; then
  docker compose build
fi

# Install dependencies
if [ $FLAG_WITH_COMPOSER -eq 1 ]; then
  docker compose run --rm biblioteca composer install
fi

if [ $FLAG_WITH_MIGRATIONS -eq 1 ]; then
  docker compose run --rm biblioteca bin/console app:backup-db
  docker compose run --rm biblioteca bin/console doctrine:migrations:migrate --no-interaction
fi

# Build frontend assets
if [ $FLAG_WITH_NPM -eq 1 ]; then
  docker compose run --rm biblioteca npm install
  docker compose run --rm biblioteca npm run build
fi

# restart the containers, clear cache, index data
docker compose up -d --force-recreate --remove-orphans
docker compose exec biblioteca bin/console cache:clear --env=prod

# wait for typesense to be ready
if [ $FLAG_WITH_TYPESENSE -eq 1 ]; then
  tries=0
  echo "Waiting for Typesense to be ready"
  sleep 1
  while true; do
    EXIT_CODE=0
    docker compose exec biblioteca curl -s -f "${TYPESENSE_URL}" >/dev/null 2>&1 || EXIT_CODE=$?
    if [ $EXIT_CODE -eq 0 ]; then
      break
    fi

    echo -n "."
    tries=$((tries + 1))
    sleep 1
    if [ $tries -gt 15 ]; then
      echo "Typesense is not ready, skipping wait." >&2
      break
    fi
  done
  docker compose exec biblioteca bin/console biblioverse:typesense:populate --env=prod
fi