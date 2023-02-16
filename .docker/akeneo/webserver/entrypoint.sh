#!/usr/bin/env bash

composer install -n
bin/console doc:mig:mig --no-interaction

exec "$@"
