# AmneziaWG (wg-easy v15)

Панель [wg-easy](https://github.com/wg-easy/wg-easy) v15 с включённым AmneziaWG
(`EXPERIMENTAL_AWG=true`). Веб-интерфейс — `https://awg.whitevpn.tech` через Caddy,
порт 51821 наружу не публикуется. Пиры клиентов заводит телеграм-бот через REST API.

## Как выбирается реализация

AmneziaWG умеет работать двумя способами, и оба уже есть в официальном образе
(`amneziawg-go` собирается в его Dockerfile начиная с v15.2.2):

- **kernel-модуль** — быстрее, но требует установки на хост;
- **userspace (`amneziawg-go`)** — ничего на хосте не нужно, медленнее.

По умолчанию wg-easy делает `modinfo amneziawg` **внутри контейнера** и, не найдя
модуль, молча откатывается на обычный WireGuard — без обфускации. Заметить это можно
только по отсутствию строк `Jc` / `S1` / `H1` в выданном `.conf`.

Поэтому в compose стоит `OVERRIDE_AUTO_AWG=awg`: он отключает этот автодетект, и
wg-easy зовёт `awg-quick up`. Дальше `awg-quick` решает сам — если на хосте есть
модуль, идёт kernel-путём, если нет, поднимает интерфейс через `amneziawg-go`.
Тихого отката на голый WireGuard не происходит ни в одном из случаев.

Для userspace нужен проброшенный `/dev/net/tun` — он есть в compose.

## Ускорение: kernel-модуль (опционально)

Не обязательно, деплой работает и без этого. Даёт прирост скорости, особенно
заметный на слабом сервере. Ставится один раз руками под root:

```sh
apt-get update
apt-get install -y software-properties-common python3-launchpadlib gnupg2 "linux-headers-$(uname -r)"
add-apt-repository -y ppa:amnezia/ppa
apt-get update
apt-get install -y amneziawg

modinfo amneziawg   # должен что-то напечатать
```

Требуется хотя бы одна раскомментированная строка `deb-src` в `/etc/apt/sources.list`,
иначе DKMS не соберёт модуль. После обновления ядра DKMS пересобирает модуль сам,
пока в системе стоят `linux-headers`. Менять конфиг после установки не нужно —
`awg-quick` подхватит модуль на следующем рестарте контейнера.

## Параметры обфускации

`Jc`, `Jmin`, `Jmax`, `S1-S4`, `H1-H4` в v15 задаются не через env, а в Admin Panel →
Interface. При первом старте выставляются случайными — это нормально.

## Проверка после деплоя

```sh
docker logs wg-easy
docker exec wg-easy wg show
```

Какая реализация поднялась — видно в логах: строка
`Missing WireGuard (Amnezia VPN) kernel module. Falling back to slow userspace implementation.`
означает `amneziawg-go`, её отсутствие — kernel-модуль.

Создать клиента в UI, скачать `.conf` и убедиться, что в нём есть `Jc`, `S1`, `H1`.
