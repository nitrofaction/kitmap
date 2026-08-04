# Nitro Kitmap

[![Active Development](https://img.shields.io/badge/status-active-brightgreen)](https://github.com/nitrofaction/kitmap)
[![PocketMine-MP API](https://img.shields.io/badge/PocketMine--MP-API%205.0.0-orange)](https://github.com/pmmp/PocketMine-MP)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4)](https://www.php.net/)
[![GitHub stars](https://img.shields.io/github/stars/nitrofaction/kitmap?style=social)](https://github.com/nitrofaction/kitmap/stargazers)

Game server for [Nitro Kitmap](https://nitrofaction.fr/) — a French Minecraft Bedrock kitmap/faction server, written
from scratch on PocketMine-MP.

Not a demo plugin. This is the code that ran the server in production.

| | |
| --- | --- |
| Unique players served | **50,000+** |
| Peak concurrent (NitroFaction network) | **700** |
| Peak concurrent (kitmap, this codebase) | **300** |
| Development | **5 years**, several full rewrites |
| Codebase | 280 PHP files, ~24,000 lines |

## What it does

A complete server game mode, in one plugin:

- **Factions** — claims, power, ranks with a per-permission matrix, allies, audit logs, and a private generated world
  per faction
- **Live events** — Nexus boss, KOTH, Domination, Outpost, FarmWars, MoneyZone, PrimeTime, scheduled by config
- **Economy** — dynamic stock exchange, player-to-player market, shops, bounties, casino, multi-currency
- **Progression** — 3 leveled jobs, NPC quests, loot crates, cosmetics, a custom endgame material tier
- **Custom content** — 6 enchantments, ~20 items, ~20 blocks, custom entities and NPCs, 12 ranks with per-rank kits
- **Moderation** — escalating sanction matrix, alt detection, proxy detection, staff tooling, Discord webhooks

## Engineering

The interesting constraint: hold **300 concurrent players on a single PHP process**. That drove the design.

- **Boot-time cache** — every player file, faction, claim, ban and market entry loads into static arrays at startup.
  Leaderboards, claim checks and faction lookups are array reads, never disk reads, on the hot path.
- **One repeating task, not thirty** — `PlayerTask` ticks once a second and fans out to every event subsystem, with
  cheaper work gated behind modulo checks. Each feature costs tick budget, not a scheduler slot.
- **`WeakMap` sessions** — per-player state is keyed by the `Player` object, so it is garbage-collected on disconnect
  instead of leaking.
- **Reflective command registration** — dropping a class under `src/command` registers it. No 169-entry list to keep
  in sync.
- **Async I/O** — Discord webhooks and vote requests run off-thread; network latency never reaches the main loop.
- **Custom enchantments aliased onto unused vanilla IDs** — the Bedrock client renders and glows them natively, with
  no client-side mod.
- **`WaterdogPacketHandler`** — rewrites the login handler so real player IP and XUID survive the proxy, instead of
  every player appearing to connect from the proxy address.
- **Graceful shutdown** — flushes every session to disk and transfers online players to the hub rather than dropping
  them.
- **Config over code** — jobs, quests, events, shops, sanctions, prices and scheduling are all JSON. Retuning the
  server is not a deploy.

## Structure

```
src/
├── Main.php          Entry point
├── Session.php       Per-player state (WeakMap)
├── Util.php          Shared helpers
├── block/     (23)   Custom blocks, tiles, 4 world generators
├── command/  (169)   faction/ player/ staff/ util/ — auto-registered
├── entity/    (26)   Entities, NPCs, floating text
├── handler/   (15)   Cache, Faction, Job, Rank, Sanction, Discord, Waterdog…
├── item/      (31)   Custom items and enchantments
├── listener/   (1)   EventListener
└── task/      (12)   PlayerTask + event subtasks + async tasks

resources/config/     All gameplay tuning, as JSON
```

## Install

Requires [PocketMine-MP](https://github.com/pmmp/PocketMine-MP) API 5.0.0 (PHP 8.1+). Drop into `plugins/`, install the
virions below, start. Data folders are created on first boot.

Virions: [bossbarpi, commando, formapi, invmenu, npcdialogue, simplepackethandler](https://github.com/nitrofaction/virions)

> Forking? `resources/config/main.json` ships with this server's Discord webhook URLs — replace them with your own.

## Contact

Open-source for PocketMine developers: personal projects yes, commercial use no.

Discord: [`neuilleneuille`](https://discordapp.com/users/1042541730823667814) · [open an issue](https://github.com/nitrofaction/kitmap/issues/new) · [LICENSE](LICENSE)
