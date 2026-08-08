# AmneziaWG (wg-easy v15)

Панель [wg-easy](https://github.com/wg-easy/wg-easy) v15 с включённым AmneziaWG
(`EXPERIMENTAL_AWG=true`). Веб-интерфейс — `https://awg.whitevpn.tech` через Caddy,
порт 51821 наружу не публикуется. Пиры клиентов заводит телеграм-бот через REST API.

## Разовая подготовка хоста

wg-easy использует AmneziaWG **только если на хосте установлен kernel-модуль**.
Если модуля нет — панель молча откатывается на обычный WireGuard, обфускации не будет,
и заметить это можно только по отсутствию строк `Jc` / `S1` / `H1` в выданном `.conf`.

Деплой этого не чинит: у CI-пользователя нет root. Ставится один раз руками:

```sh
# под root, на основном сервере
apt-get update
apt-get install -y software-properties-common python3-launchpadlib gnupg2 "linux-headers-$(uname -r)"
add-apt-repository -y ppa:amnezia/ppa
apt-get update
apt-get install -y amneziawg

modinfo amneziawg   # должен что-то напечатать
```

Требуется хотя бы одна раскомментированная строка `deb-src` в `/etc/apt/sources.list`,
иначе DKMS не соберёт модуль. После обновления ядра DKMS пересобирает модуль сам,
пока в системе стоят `linux-headers`.

Деплой падает с понятной ошибкой, если модуля нет — это защита от тихого отката на
голый WireGuard.

## Параметры обфускации

`Jc`, `Jmin`, `Jmax`, `S1-S4`, `H1-H4` в v15 задаются не через env, а в Admin Panel →
Interface. При первом старте выставляются случайными — это нормально.

## Проверка после деплоя

```sh
docker logs wg-easy
docker exec wg-easy wg show
```

Создать клиента в UI, скачать `.conf` и убедиться, что в нём есть `Jc`, `S1`, `H1`.
