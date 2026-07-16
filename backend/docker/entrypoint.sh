#!/bin/sh
set -e

# Явная команда (напр. `docker compose run --rm api php artisan migrate --force`) выполняется
# как есть — CONTAINER_ROLE-роутинг ниже применяется только когда команда не передана
# (обычный старт контейнера через `up`).
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

case "$CONTAINER_ROLE" in
  api)
    exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
    ;;
  queue)
    exec php artisan queue:work --tries=3 --backoff=5
    ;;
  scheduler)
    exec php artisan schedule:work
    ;;
  websocket)
    exec php artisan reverb:start --host=0.0.0.0
    ;;
  *)
    echo "Unknown CONTAINER_ROLE: '$CONTAINER_ROLE' (expected api|queue|scheduler|websocket)" >&2
    exit 1
    ;;
esac
