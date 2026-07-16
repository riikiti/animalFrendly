#!/bin/sh
# HEALTHCHECK одинаков для всех 4 ролей одного образа (CONTAINER_ROLE), поэтому не может быть
# одной статичной командой — api/websocket слушают HTTP/TCP на 8080, а queue/scheduler не
# держат открытых портов вовсе, у них здоровье — это просто "процесс жив".
case "$CONTAINER_ROLE" in
  api)
    wget -qO- http://127.0.0.1:8080/up >/dev/null 2>&1
    ;;
  websocket)
    nc -z 127.0.0.1 8080
    ;;
  queue)
    pgrep -f "artisan queue:work" >/dev/null
    ;;
  scheduler)
    pgrep -f "artisan schedule:work" >/dev/null
    ;;
  *)
    exit 1
    ;;
esac
