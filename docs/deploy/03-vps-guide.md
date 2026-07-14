# Гайд: разворачивание на голом VPS

[← к оглавлению деплоя](../DEPLOY.md)

Два варианта. **Вариант A рекомендуется** — тот же Helm-chart, что и в
[`02-kubernetes.md`](./02-kubernetes.md), разворачивается на однонодовом k3s, поэтому
прод-манифесты не расходятся между «настоящим» кластером и одним VPS. Вариант B —
без Kubernetes вообще, для случая, когда k3s избыточен (совсем маленький проект/бюджет).

Требования к серверу (оба варианта): Ubuntu 22.04 LTS, минимум 2 vCPU / 4 GB RAM на старте
(с ростом нагрузки — 4 vCPU / 8 GB), домен с A-записью, указывающей на IP сервера, открытые
порты 80 и 443 (и 22 для SSH).

## Вариант A — k3s (Kubernetes)

### 1. Базовая подготовка сервера

```bash
apt update && apt upgrade -y
adduser deploy && usermod -aG sudo deploy      # непривилегированный пользователь
ufw allow OpenSSH && ufw allow 80 && ufw allow 443 && ufw enable
```

### 2. Установка k3s

```bash
curl -sfL https://get.k3s.io | sh -
# k3s уже включает Traefik как Ingress-контроллер и local-path-provisioner как storage class
kubectl get nodes                               # проверка, что нода Ready
```

Kubeconfig лежит в `/etc/rancher/k3s/k3s.yaml` — скопировать на локальную машину для
управления кластером удалённо (`scp` + `export KUBECONFIG=...`) или работать по SSH прямо
на сервере.

### 3. Helm и cert-manager

```bash
curl https://raw.githubusercontent.com/helm/helm/main/scripts/get-helm-3 | bash
helm repo add jetstack https://charts.jetstack.io && helm repo update
helm install cert-manager jetstack/cert-manager \
  --namespace cert-manager --create-namespace --set installCRDs=true
```

Создать `ClusterIssuer` для Let's Encrypt (HTTP-01 через Traefik) — манифест в
`deploy/k8s/cluster-issuer.yaml`, применяется один раз: `kubectl apply -f cluster-issuer.yaml`.

### 4. Образы приложения

Собрать и запушить `backend`/`frontend` образы в container registry (GitHub Container
Registry или Docker Hub) из CI (см. `.github/workflows`). Для первого ручного деплоя
допустимо собрать образы прямо на сервере (`docker build`) и загрузить в локальный
containerd k3s через `k3s ctr images import`, но для регулярных обновлений — только через
registry и CI.

### 5. Namespace и секреты

```bash
kubectl create namespace animalfriendly-production
kubectl create secret generic animalfriendly-secrets \
  --namespace animalfriendly-production \
  --from-literal=APP_KEY='...' \
  --from-literal=DB_PASSWORD='...' \
  --from-literal=YOOKASSA_SHOP_ID='...' \
  --from-literal=YOOKASSA_SECRET_KEY='...' \
  --from-literal=YOOKASSA_WEBHOOK_SECRET='...' \
  --from-literal=FCM_CREDENTIALS='...' \
  --from-literal=AWS_SECRET_ACCESS_KEY='...'
```

Полный список переменных — [`../plan/10-integrations.md`](../plan/10-integrations.md).
Значения секретов никогда не попадают в git.

### 6. PostgreSQL и Redis

На одиночном VPS без managed-БД — self-hosted `StatefulSet` с PVC на стандартном
`local-path`-storage class k3s (манифесты — `postgres-statefulset.yaml`,
`redis-statefulset.yaml` в чарте). Это самое слабое звено отказоустойчивости на одной
машине — см. обязательные бэкапы в [`04-operations.md`](./04-operations.md).

### 7. Установка приложения

```bash
helm install animalfriendly deploy/k8s/charts/animalfriendly \
  --namespace animalfriendly-production \
  -f deploy/k8s/charts/animalfriendly/values-production.yaml \
  --set ingress.host=animalfriendly.ru
```

Helm-хук `migrate-job` автоматически прогонит `php artisan migrate --force` перед тем, как
раскатится новая версия `api` (см. [`02-kubernetes.md`](./02-kubernetes.md)).

### 8. Проверка

```bash
kubectl get pods -n animalfriendly-production
kubectl logs deploy/api -n animalfriendly-production
curl https://animalfriendly.ru/api/health
```

### 9. Вебхук ЮKassa

В личном кабинете ЮKassa указать URL вебхука `https://animalfriendly.ru/api/webhooks/yookassa`
— сертификат уже валиден благодаря cert-manager из шага 3.

### 10. Обновление версии

```bash
helm upgrade animalfriendly deploy/k8s/charts/animalfriendly \
  -n animalfriendly-production -f values-production.yaml
```

---

## Вариант B — Docker Compose (без Kubernetes)

Проще в поддержке для одного маленького сервера, но без автоскейлинга и без единообразия
с прод-манифестами из [`02-kubernetes.md`](./02-kubernetes.md) — переход на k8s позже
потребует ручной работы.

### 1. Установка Docker

```bash
curl -fsSL https://get.docker.com | sh
usermod -aG docker deploy
```

### 2. Код и конфигурация

```bash
git clone <repo> /opt/animalfriendly && cd /opt/animalfriendly
cp .env.example .env        # заполнить все переменные — список в plan/10-integrations.md
```

### 3. Reverse proxy с автоматическим TLS

Использовать Caddy как отдельный контейнер (`Caddyfile` с доменом — сертификат Let's
Encrypt выпускается и продлевается автоматически, без ручной настройки cert-manager):

```
animalfriendly.ru {
    handle /api/* { reverse_proxy api:9000 }
    handle /ws/*  { reverse_proxy websocket:8080 }
    handle        { reverse_proxy frontend:80 }
}
```

### 4. Запуск

```bash
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml run --rm api php artisan migrate --force
```

`restart: unless-stopped` в `docker-compose.prod.yml` для всех сервисов — контейнеры
поднимаются заново после перезагрузки сервера без дополнительной настройки systemd.

### 5. Обновление версии

```bash
git pull
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml run --rm api php artisan migrate --force
```

Бэкапы, мониторинг и логи — общие для обоих вариантов, см.
[`04-operations.md`](./04-operations.md).
