"""Build database/data/steam-sample.json: a sample catalogue from Steam's public store API.

The demo and the tests run on this file instead of the full scraped database. For each game
it keeps only what the app shows or scores: name, genres, categories, price, reviews, a few
screenshots and the PC system requirements (raw HTML, parsed later by the importer).

    python scripts/fetch_sample_catalogue.py            # ~10 minutes (Steam rate limits)

Sources: store.steampowered.com/api/appdetails and /appreviews (public, no key), and the
most-played chart from api.steampowered.com. Steam allows about 200 appdetails calls per
5 minutes, so the script waits 1.6 s between games.
"""

from __future__ import annotations

import json
import sys
import time
import urllib.error
import urllib.request
from pathlib import Path

OUT = Path(__file__).resolve().parents[1] / "database" / "data" / "steam-sample.json"

# Well-known games across eras, genres and hardware needs (from light indies to heavy AAA),
# added to the current most-played chart so the catalogue is not only this month's hits.
CURATED = [
    413150, 105600, 367520, 504230, 391540, 1145360, 646570, 588650, 945360, 1794680, 294100, 427520,
    322330, 620, 220, 550, 440, 255710, 227300, 292030, 489830, 377160, 271590, 1174180, 1091500,
    1245620, 1086940, 990080, 1716740, 2358720, 1551360, 582010, 814380, 374320, 892970, 1623730,
    1966720, 553850, 1426210, 739630, 252490, 346110, 1326470, 242760, 526870, 548430, 648800,
    815370, 962130, 264710, 275850, 730, 570, 1172470, 578080, 359550, 230410, 238960, 1085660,
    2357570, 2767030, 1203220, 1938090, 2050650, 1196590, 883710, 1593500, 1817070, 1888930,
    1222670, 949230, 1446780, 2246340, 1517290, 381210, 236390, 8930, 289070, 394360, 281990,
    236850, 1158310, 431960, 1057090, 1142710, 1063730, 2399830, 1771300, 1868140, 2195250,
    2215430, 2420110, 1361210, 1293830, 1240440, 268910, 1150690, 632470, 1029690, 1675200,
]

UA = {"User-Agent": "GameSpecMatch sample builder (portfolio demo)"}


def get_json(url: str, tries: int = 4) -> dict | None:
    for attempt in range(tries):
        try:
            with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=20) as r:
                return json.loads(r.read().decode("utf-8"))
        except urllib.error.HTTPError as e:
            if e.code == 429:  # rate limited: back off
                time.sleep(30 * (attempt + 1))
                continue
            return None
        except (urllib.error.URLError, TimeoutError, json.JSONDecodeError):
            time.sleep(3)
    return None


def most_played() -> list[int]:
    data = get_json("https://api.steampowered.com/ISteamChartsService/GetMostPlayedGames/v1/") or {}
    return [r["appid"] for r in (data.get("response") or {}).get("ranks", [])]


def trim(d: dict) -> dict:
    """Keep the fields the app uses; drop long HTML descriptions and video links."""
    keep = ["steam_appid", "type", "name", "required_age", "is_free", "short_description", "header_image",
            "website", "developers", "publishers", "price_overview", "platforms", "metacritic", "categories",
            "genres", "release_date", "pc_requirements", "controller_support", "content_descriptors"]
    out = {k: d[k] for k in keep if k in d}
    out["recommendations"] = (d.get("recommendations") or {}).get("total")
    out["achievements"] = (d.get("achievements") or {}).get("total")
    out["screenshots"] = [{k: s[k] for k in ("id", "path_thumbnail", "path_full")} for s in (d.get("screenshots") or [])[:4]]
    return out


def main() -> int:
    ids = list(dict.fromkeys(most_played() + CURATED))
    print(f"{len(ids)} candidate app ids", flush=True)
    games = []
    for n, app_id in enumerate(ids, 1):
        details = get_json(f"https://store.steampowered.com/api/appdetails?appids={app_id}&cc=us&l=english")
        entry = (details or {}).get(str(app_id)) or {}
        data = entry.get("data") if entry.get("success") else None
        if data and data.get("type") == "game" and isinstance(data.get("pc_requirements"), dict) \
                and data["pc_requirements"].get("minimum"):
            game = trim(data)
            reviews = get_json(f"https://store.steampowered.com/appreviews/{app_id}?json=1&language=all&purchase_type=all&num_per_page=0")
            game["reviews"] = (reviews or {}).get("query_summary") or {}
            games.append(game)
            print(f"{n:3}/{len(ids)} {game['name']}", flush=True)
        time.sleep(1.6)

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps({"source": "Steam store API (public), fetched for demo use",
                               "fetched": time.strftime("%Y-%m-%d"), "games": games},
                              ensure_ascii=False, indent=1), encoding="utf-8")
    print(f"wrote {len(games)} games to {OUT}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
