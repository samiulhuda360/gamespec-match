"""
Enhanced Steam Game Scraper
Scrapes Steam game data including new fields (Metacritic, achievements, controller support, etc.)
and stores directly in MySQL database
"""

import requests
import mysql.connector
import json
import time
import re
from bs4 import BeautifulSoup
from datetime import datetime
from typing import Dict, Optional, List
import os
import subprocess
import sys



# -------------------------
# Database Configuration
# -------------------------


DB_CONFIG = {
    "host": "127.0.0.1",
    "user": "root",
    "password": "",
    "database": "steamdb",
    "port": 3306,
}


PLATFORM_MAP = {
    "pc_requirements": "pc",
    "mac_requirements": "mac",
    "linux_requirements": "linux",
}

# -------------------------
# Helper Functions
# -------------------------
def clean_html(text):
    """Remove HTML tags and return clean text"""
    if not text:
        return ""
    return BeautifulSoup(text, "html.parser").get_text(separator=" ", strip=True)

def parse_date(date_str):
    """Parse Steam date formats to MySQL date"""
    if not date_str or date_str == "Coming soon":
        return None

    date_str = date_str.strip()

    # If it's just a year (4 digits), return None - not precise enough
    if re.match(r'^\d{4}$', date_str):
        return None

    formats = [
        "%d %b, %Y",      # 21 Aug, 2012
        "%b %d, %Y",      # Aug 21, 2012
        "%Y-%m-%d",       # 2012-08-21
        "%B %Y",          # August 2012
    ]

    for fmt in formats:
        try:
            return datetime.strptime(date_str, fmt).date()
        except ValueError:
            continue

    return None

# -------------------------
# System Requirements Parser
# -------------------------
KEY_PATTERNS = [
    (re.compile(r"^(processor|cpu|proc)\b", re.I), "cpu"),
    (re.compile(r"^(memory|ram)\b", re.I), "ram"),
    (re.compile(r"^(graphics|graphic\s*card|video\s*card|videocard|gpu|vga)\b", re.I), "gpu"),
    (re.compile(r"^(storage|disk\s*space|disc\s*space|hard\s*drive|hdd|ssd|free\s*space|space\s*(required|available)?)\b", re.I), "storage"),
    (re.compile(r"^(direct\s*x|directx|dx)\b", re.I), "directx"),
]

IGNORE_KEYS = {
    "minimum", "recommended", "min", "rec",
    "os", "os version", "operating system",
    "sound", "audio", "additional notes", "notes", "other"
}

def _norm_key(s: str) -> str:
    s = s or ""
    s = s.replace("®", "").replace("™", "")
    s = s.strip().lower()
    s = re.sub(r"[\(\)\[\]{}]", " ", s)
    s = re.sub(r"[^a-z0-9\s\-\+]", " ", s)
    s = re.sub(r"\s+", " ", s).strip()
    return s

def _assign(out: dict, key_raw: str, val: str):
    key = _norm_key(key_raw)
    if not key or key in IGNORE_KEYS:
        return

    for pat, canon in KEY_PATTERNS:
        if pat.search(key):
            val = (val or "").strip()
            if not val:
                return
            if out.get(canon) is None or (len(val) > len(out.get(canon) or "")):
                out[canon] = val
            return

def html_to_kv(html: str) -> dict:
    """Extract system requirements from HTML"""
    out = {"cpu": None, "ram": None, "gpu": None, "storage": None, "directx": None}
    if not html:
        return out

    soup = BeautifulSoup(html, "html.parser")

    for br in soup.find_all("br"):
        br.replace_with("\n")

    lines = []
    for li in soup.select("li"):
        txt = " ".join(li.get_text(" ", strip=True).split())
        if txt:
            lines.append(txt)

    if not lines:
        txt = soup.get_text("\n", strip=True)
        lines = [ln.strip() for ln in txt.split("\n") if ln.strip()]

    for ln in lines:
        ln_clean = re.sub(r"\s+", " ", ln).strip()

        if re.fullmatch(r"(minimum|recommended)\s*:?\s*", ln_clean, flags=re.I):
            continue

        m = re.match(r"^\s*([^:]{1,60})\s*:\s*(.+?)\s*$", ln_clean)
        if m:
            key_raw = m.group(1).strip()
            val = m.group(2).strip()
            _assign(out, key_raw, val)
            continue

        if re.match(r"^\s*direct\s*x\b", ln_clean, flags=re.I) and ":" not in ln_clean:
            if re.search(r"\bsound\b", ln_clean, flags=re.I):
                continue
            val = re.sub(r"^\s*direct\s*x\s*", "", ln_clean, flags=re.I).strip()
            if val:
                _assign(out, "directx", val)
            continue

    for strong in soup.find_all("strong"):
        key_text = strong.get_text(" ", strip=True).strip()
        key_text = re.sub(r"\s+", " ", key_text)
        key_text = key_text.rstrip(":").strip()

        val_parts = []
        for sib in strong.next_siblings:
            if isinstance(sib, str):
                if "\n" in sib:
                    before = sib.split("\n", 1)[0].strip()
                    if before:
                        val_parts.append(before)
                    break
                chunk = sib.strip()
                if chunk:
                    val_parts.append(chunk)
            else:
                txt = sib.get_text(" ", strip=True).strip()
                if txt:
                    if "\n" in txt:
                        val_parts.append(txt.split("\n", 1)[0].strip())
                        break
                    val_parts.append(txt)

        val = " ".join(val_parts).strip()
        if val:
            _assign(out, key_text, val)

    return out

# -------------------------
# External API Functions
# -------------------------
def get_steam_reviews(app_id):
    """Fetch review data from Steam"""
    url = f"https://store.steampowered.com/appreviews/{app_id}?json=1&num_per_page=1"
    try:
        response = requests.get(url, timeout=10)
        data = response.json().get("query_summary", {})
        return {
            "total_reviews": data.get("total_reviews", 0),
            "total_positive": data.get("total_positive", 0),
            "total_negative": data.get("total_negative", 0),
            "review_score_desc": data.get("review_score_desc", "")
        }
    except Exception as e:
        print(f"  ⚠️ Reviews fetch failed: {e}")
        return {
            "total_reviews": 0,
            "total_positive": 0,
            "total_negative": 0,
            "review_score_desc": ""
        }

def get_pricing_info(app_id, currency="us"):
    url = f"https://store.steampowered.com/api/appdetails?appids={app_id}&cc={currency}&filters=price_overview"

    try:
        r = requests.get(url, timeout=10)
        data = r.json()

        app_block = data.get(str(app_id))
        if not app_block:
            print(f"  ⚠️ No data returned for app {app_id}")
            return None

        if not isinstance(app_block, dict):
            print(f"  ⚠️ app_block is not a dict for {app_id}: {type(app_block)}")
            return None

        if not app_block.get("success"):
            print(f"  ⚠️ API returned success=False for app {app_id}")
            return None

        game_data = app_block.get("data")

        if not isinstance(game_data, dict):
            print(f"  ⚠️ Unexpected data type for app {app_id}: {type(game_data)}")
            print(f"  ⚠️ Raw data: {game_data}")
            return None

        # Handle FREE games
        if game_data.get("is_free") is True:
            return {
                "currency": "USD",
                "initial_price": 0,
                "final_price": 0,
                "discount_percent": 0,
                "is_free": True
            }

        # Handle games with pricing
        price_data = game_data.get("price_overview")

        if not isinstance(price_data, dict):
            return None

        return {
            "currency": price_data.get("currency", "USD"),
            "initial_price": price_data.get("initial", 0),
            "final_price": price_data.get("final", 0),
            "discount_percent": price_data.get("discount_percent", 0),
            "is_free": False
        }

    except requests.exceptions.RequestException as e:
        print(f"  ⚠️ Network error fetching pricing for {app_id}: {e}")
        return None
    except json.JSONDecodeError as e:
        print(f"  ⚠️ JSON decode error for {app_id}: {e}")
        return None
    except Exception as e:
        print(f"  ⚠️ Unexpected error fetching pricing for {app_id}: {e}")
        return None


# -------------------------
# Database Insert Functions
# -------------------------
def upsert_app(cursor, app_id, game_data, captured_at):
    """Insert or update app in apps table with NEW FIELDS"""

    release_info = game_data.get("release_date", {})
    release_date = parse_date(release_info.get("date", ""))

    # ✅ Extract NEW fields
    metacritic = game_data.get("metacritic", {})
    metacritic_score = metacritic.get("score") if isinstance(metacritic, dict) else None
    metacritic_url = metacritic.get("url") if isinstance(metacritic, dict) else None

    achievements_data = game_data.get("achievements", {})
    total_achievements = achievements_data.get("total", 0) if isinstance(achievements_data, dict) else 0

    # Controller support: can be "full", "partial", or absent
    controller_support = game_data.get("controller_support", "none")
    if controller_support and controller_support.lower() not in ["none", "partial", "full"]:
        controller_support = "partial"  # Default to partial if unrecognized

    # DLC information
    dlc_list = game_data.get("dlc", [])
    dlc_count = len(dlc_list) if isinstance(dlc_list, list) else 0
    dlc_ids = json.dumps(dlc_list) if dlc_list else None

    # Adult content flags
    content_descriptors = game_data.get("content_descriptors", {})
    if isinstance(content_descriptors, dict):
        has_adult_content = bool(content_descriptors.get("ids"))  # Non-empty IDs list
        has_adult_content_sex = 3 in (content_descriptors.get("ids", []))  # ID 3 is sexual content
        has_adult_content_violence = 2 in (content_descriptors.get("ids", []))  # ID 2 is violence
    else:
        has_adult_content = False
        has_adult_content_sex = False
        has_adult_content_violence = False

    # Package information
    package_groups = game_data.get("package_groups", [])
    package_ids = []
    if isinstance(package_groups, list):
        for group in package_groups:
            if isinstance(group, dict) and "subs" in group:
                for sub in group["subs"]:
                    if isinstance(sub, dict) and "packageid" in sub:
                        package_ids.append(sub["packageid"])
    package_ids_json = json.dumps(package_ids) if package_ids else None

    # Support info
    support_info = game_data.get("support_info", {})
    support_url = support_info.get("url") if isinstance(support_info, dict) else None
    support_email = support_info.get("email") if isinstance(support_info, dict) else None

    # Legal notices
    legal_notice = game_data.get("legal_notice")
    drm_notice = game_data.get("drm_notice")
    ext_account_notice = game_data.get("ext_user_account_notice")

    # Platform flags
    platforms = game_data.get("platforms", {})
    supports_windows = platforms.get("windows", False) if isinstance(platforms, dict) else False
    supports_mac = platforms.get("mac", False) if isinstance(platforms, dict) else False
    supports_linux = platforms.get("linux", False) if isinstance(platforms, dict) else False

    sql = """
    INSERT INTO apps (
        app_id, captured_at, type, name, steam_appid,
        required_age, is_free, short_description, detailed_description, about_the_game,
        supported_languages, website, release_date, release_date_text, coming_soon,
        developers, publishers, header_image, background_image,
        content_descriptor_notes, total_recommendations,
        metacritic_score, metacritic_url, total_achievements, controller_support,
        dlc_count, dlc_ids, has_adult_content, has_adult_content_sex, has_adult_content_violence,
        package_ids, support_url, support_email, legal_notice, drm_notice, ext_account_notice,
        supports_windows, supports_mac, supports_linux
    ) VALUES (
        %s, %s, %s, %s, %s,
        %s, %s, %s, %s, %s,
        %s, %s, %s, %s, %s,
        %s, %s, %s, %s,
        %s, %s,
        %s, %s, %s, %s,
        %s, %s, %s, %s, %s,
        %s, %s, %s, %s, %s, %s,
        %s, %s, %s
    )
    ON DUPLICATE KEY UPDATE
        captured_at = VALUES(captured_at),
        type = VALUES(type),
        name = VALUES(name),
        steam_appid = VALUES(steam_appid),
        required_age = VALUES(required_age),
        is_free = VALUES(is_free),
        short_description = VALUES(short_description),
        detailed_description = VALUES(detailed_description),
        about_the_game = VALUES(about_the_game),
        supported_languages = VALUES(supported_languages),
        website = VALUES(website),
        release_date = VALUES(release_date),
        release_date_text = VALUES(release_date_text),
        coming_soon = VALUES(coming_soon),
        developers = VALUES(developers),
        publishers = VALUES(publishers),
        header_image = VALUES(header_image),
        background_image = VALUES(background_image),
        content_descriptor_notes = VALUES(content_descriptor_notes),
        total_recommendations = VALUES(total_recommendations),
        metacritic_score = VALUES(metacritic_score),
        metacritic_url = VALUES(metacritic_url),
        total_achievements = VALUES(total_achievements),
        controller_support = VALUES(controller_support),
        dlc_count = VALUES(dlc_count),
        dlc_ids = VALUES(dlc_ids),
        has_adult_content = VALUES(has_adult_content),
        has_adult_content_sex = VALUES(has_adult_content_sex),
        has_adult_content_violence = VALUES(has_adult_content_violence),
        package_ids = VALUES(package_ids),
        support_url = VALUES(support_url),
        support_email = VALUES(support_email),
        legal_notice = VALUES(legal_notice),
        drm_notice = VALUES(drm_notice),
        ext_account_notice = VALUES(ext_account_notice),
        supports_windows = VALUES(supports_windows),
        supports_mac = VALUES(supports_mac),
        supports_linux = VALUES(supports_linux)
    """

    params = (
        app_id,
        captured_at,
        game_data.get("type"),
        game_data.get("name"),
        game_data.get("steam_appid"),
        game_data.get("required_age", 0),
        game_data.get("is_free", False),
        clean_html(game_data.get("short_description", "")),
        clean_html(game_data.get("detailed_description", "")),
        clean_html(game_data.get("about_the_game", "")),
        clean_html(game_data.get("supported_languages", "")),
        game_data.get("website"),
        release_date,
        release_info.get("date"),
        release_info.get("coming_soon", False),
        json.dumps(game_data.get("developers", [])),
        json.dumps(game_data.get("publishers", [])),
        game_data.get("header_image"),
        game_data.get("background"),
        clean_html(game_data.get("content_descriptors", {}).get("notes", "")),
        game_data.get("recommendations", {}).get("total"),
        # NEW FIELDS
        metacritic_score,
        metacritic_url,
        total_achievements,
        controller_support,
        dlc_count,
        dlc_ids,
        has_adult_content,
        has_adult_content_sex,
        has_adult_content_violence,
        package_ids_json,
        support_url,
        support_email,
        legal_notice,
        drm_notice,
        ext_account_notice,
        supports_windows,
        supports_mac,
        supports_linux
    )

    cursor.execute(sql, params)

def upsert_content_ratings(cursor, app_id, game_data):
    """Insert or update content ratings (ESRB, PEGI, USK, etc.)"""
    ratings = game_data.get("ratings", {})

    if not isinstance(ratings, dict) or not ratings:
        return

    # Delete existing ratings for this app
    cursor.execute("DELETE FROM content_ratings WHERE app_id = %s", (app_id,))

    # Map of rating systems
    for system, data in ratings.items():
        if not isinstance(data, dict):
            continue

        rating_value = data.get("rating", "")
        descriptors_list = data.get("descriptors", [])

        # Convert descriptors list to comma-separated string
        if isinstance(descriptors_list, list):
            descriptors = ", ".join(descriptors_list)
        else:
            descriptors = str(descriptors_list) if descriptors_list else None

        if rating_value:
            cursor.execute("""
                INSERT INTO content_ratings (app_id, rating_system, rating, descriptors)
                VALUES (%s, %s, %s, %s)
            """, (app_id, system.lower(), rating_value, descriptors))

    print(f"  🔞 Inserted {len(ratings)} content ratings")

def upsert_reviews(cursor, app_id, reviews):
    """Insert or update review statistics"""
    sql = """
    INSERT INTO reviews (
        app_id, total_reviews, total_positive, total_negative, review_score_desc
    ) VALUES (
        %s, %s, %s, %s, %s
    )
    ON DUPLICATE KEY UPDATE
        total_reviews = VALUES(total_reviews),
        total_positive = VALUES(total_positive),
        total_negative = VALUES(total_negative),
        review_score_desc = VALUES(review_score_desc)
    """

    cursor.execute(sql, (
        app_id,
        reviews.get("total_reviews", 0),
        reviews.get("total_positive", 0),
        reviews.get("total_negative", 0),
        reviews.get("review_score_desc", "")
    ))

def upsert_pricing(cursor, app_id, pricing):
    """Insert or update pricing information"""
    if not pricing:
        return

    sql = """
    INSERT INTO pricing (
        app_id, currency, initial_price, final_price, discount_percent
    ) VALUES (
        %s, %s, %s, %s, %s
    )
    ON DUPLICATE KEY UPDATE
        initial_price = VALUES(initial_price),
        final_price = VALUES(final_price),
        discount_percent = VALUES(discount_percent),
        updated_at = CURRENT_TIMESTAMP
    """

    cursor.execute(sql, (
        app_id,
        pricing.get("currency", "USD"),
        pricing.get("initial_price", 0),
        pricing.get("final_price", 0),
        pricing.get("discount_percent", 0)
    ))

def upsert_categories(cursor, app_id, categories):
    """Insert or update categories"""
    for cat in categories:
        # Insert category if not exists
        cursor.execute("""
            INSERT IGNORE INTO categories (steam_category_id, name)
            VALUES (%s, %s)
        """, (cat.get("id"), cat.get("description")))

        # Link app to category
        cursor.execute("""
            SELECT id FROM categories WHERE name = %s
        """, (cat.get("description"),))

        result = cursor.fetchone()
        if result:
            cat_id = result[0] if isinstance(result, tuple) else result.get('id')
            cursor.execute("""
                INSERT IGNORE INTO app_categories (app_id, category_id)
                VALUES (%s, %s)
            """, (app_id, cat_id))

def upsert_genres(cursor, app_id, genres):
    """Insert or update genres"""
    for genre in genres:
        # Insert genre if not exists
        cursor.execute("""
            INSERT IGNORE INTO genres (steam_genre_id, name)
            VALUES (%s, %s)
        """, (genre.get("id"), genre.get("description")))

        # Link app to genre
        cursor.execute("""
            SELECT id FROM genres WHERE name = %s
        """, (genre.get("description"),))

        result = cursor.fetchone()
        if result:
            genre_id = result[0] if isinstance(result, tuple) else result.get('id')
            cursor.execute("""
                INSERT IGNORE INTO app_genres (app_id, genre_id)
                VALUES (%s, %s)
            """, (app_id, genre_id))

def upsert_screenshots(cursor, app_id, screenshots):
    """Insert or update screenshots"""
    # Delete existing screenshots for this app
    cursor.execute("DELETE FROM screenshots WHERE app_id = %s", (app_id,))

    for ss in screenshots:
        cursor.execute("""
            INSERT INTO screenshots (app_id, steam_screenshot_id, path_thumbnail, path_full)
            VALUES (%s, %s, %s, %s)
        """, (
            app_id,
            ss.get("id"),
            ss.get("path_thumbnail"),
            ss.get("path_full")
        ))

def upsert_movies(cursor, app_id, movies):
    """Insert or update movies"""
    cursor.execute("DELETE FROM movies WHERE app_id = %s", (app_id,))

    for movie in movies:
        cursor.execute("""
            INSERT INTO movies (
                app_id, steam_movie_id, name, thumbnail,
                dash_av1, dash_h264, hls_h264,
                webm_480, mp4_480, highlight
            )
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
        """, (
            app_id,
            movie.get("id"),
            movie.get("name"),
            movie.get("thumbnail"),
            movie.get("dash_av1"),
            movie.get("dash_h264"),
            movie.get("hls_h264"),
            (movie.get("webm") or {}).get("480"),
            (movie.get("mp4") or {}).get("480"),
            int(bool(movie.get("highlight", False)))
        ))

def upsert_sys_requirements(cursor, app_id, platform, min_html, rec_html, min_kv, rec_kv):
    """Insert or update system requirements"""
    sql = """
    INSERT INTO sys_requirements (
        app_id, platform,
        min_html, rec_html,
        min_cpu, min_ram, min_gpu, min_storage, min_directx,
        rec_cpu, rec_ram, rec_gpu, rec_storage, rec_directx
    ) VALUES (
        %s, %s,
        %s, %s,
        %s, %s, %s, %s, %s,
        %s, %s, %s, %s, %s
    )
    ON DUPLICATE KEY UPDATE
        min_html = VALUES(min_html),
        rec_html = VALUES(rec_html),
        min_cpu = VALUES(min_cpu),
        min_ram = VALUES(min_ram),
        min_gpu = VALUES(min_gpu),
        min_storage = VALUES(min_storage),
        min_directx = VALUES(min_directx),
        rec_cpu = VALUES(rec_cpu),
        rec_ram = VALUES(rec_ram),
        rec_gpu = VALUES(rec_gpu),
        rec_storage = VALUES(rec_storage),
        rec_directx = VALUES(rec_directx)
    """

    params = (
        app_id, platform,
        min_html, rec_html,
        min_kv.get("cpu"), min_kv.get("ram"), min_kv.get("gpu"),
        min_kv.get("storage"), min_kv.get("directx"),
        rec_kv.get("cpu"), rec_kv.get("ram"), rec_kv.get("gpu"),
        rec_kv.get("storage"), rec_kv.get("directx")
    )

    cursor.execute(sql, params)

def upsert_raw_json(cursor, app_id, captured_at, raw_json):
    """Insert or update raw JSON data"""
    sql = """
    INSERT INTO raw_ingest (app_id, captured_at, raw_json)
    VALUES (%s, %s, %s)
    ON DUPLICATE KEY UPDATE
        captured_at = VALUES(captured_at),
        raw_json = VALUES(raw_json)
    """

    cursor.execute(sql, (app_id, captured_at, json.dumps(raw_json)))

# -------------------------
# Main Scraping Function
# -------------------------
def scrape_app(app_id, cursor, conn):
    """Scrape single app and insert into database"""

    print(f"\n{'='*60}")
    print(f"Fetching App ID: {app_id}")
    print(f"{'='*60}")

    # Fetch app details
    url = f"https://store.steampowered.com/api/appdetails?appids={app_id}"
    try:
        response = requests.get(url, timeout=15)
        data = response.json()

        if not data.get(str(app_id), {}).get("success"):
            print(f"❌ App ID {app_id} - Not successful or not found")
            log_invalid_app_id(app_id)
            return False

        game_data = data[str(app_id)]["data"]
        captured_at = datetime.now()

    except Exception as e:
        print(f"❌ Error fetching App ID {app_id}: {e}")
        return False

    try:
        # 1. Insert app data FIRST (required for foreign keys)
        print("  📝 Inserting app data with NEW FIELDS...")
        upsert_app(cursor, app_id, game_data, captured_at)

        # 2. Insert content ratings (NEW)
        print("  🔞 Inserting content ratings...")
        upsert_content_ratings(cursor, app_id, game_data)

        # 3. Insert raw JSON
        print("  📦 Storing raw JSON...")
        upsert_raw_json(cursor, app_id, captured_at, data)

        # 4. Fetch and insert reviews
        print("  ⭐ Fetching reviews...")
        reviews = get_steam_reviews(app_id)
        upsert_reviews(cursor, app_id, reviews)
        time.sleep(0.3)

        # 5. Fetch and insert pricing
        print("  💰 Fetching pricing...")
        pricing = get_pricing_info(app_id)
        upsert_pricing(cursor, app_id, pricing)
        time.sleep(0.3)

        # 6. Insert categories
        categories = game_data.get("categories", [])
        if categories:
            print(f"  🏷️  Inserting {len(categories)} categories...")
            upsert_categories(cursor, app_id, categories)

        # 7. Insert genres
        genres = game_data.get("genres", [])
        if genres:
            print(f"  🎮 Inserting {len(genres)} genres...")
            upsert_genres(cursor, app_id, genres)

        # 8. Insert screenshots
        screenshots = game_data.get("screenshots", [])
        if screenshots:
            print(f"  📸 Inserting {len(screenshots)} screenshots...")
            upsert_screenshots(cursor, app_id, screenshots)

        # 9. Insert movies
        movies = game_data.get("movies", [])
        if movies:
            print(f"  🎬 Inserting {len(movies)} movies...")
            upsert_movies(cursor, app_id, movies)

        # 10. Insert system requirements for each platform
        for json_key, platform in PLATFORM_MAP.items():
            req_block = game_data.get(json_key)
            if not isinstance(req_block, dict):
                continue

            min_html = req_block.get("minimum") or None
            rec_html = req_block.get("recommended") or None

            if not (min_html or rec_html):
                continue

            empty = {"cpu": None, "ram": None, "gpu": None, "storage": None, "directx": None}
            min_kv = html_to_kv(min_html) if min_html else empty
            rec_kv = html_to_kv(rec_html) if rec_html else empty

            print(f"  💻 Inserting {platform.upper()} system requirements...")
            upsert_sys_requirements(cursor, app_id, platform, min_html, rec_html, min_kv, rec_kv)

        # Commit after each app
        conn.commit()
        print(f"✅ Successfully scraped App ID {app_id}")
        return True

    except Exception as e:
        print(f"❌ Error processing App ID {app_id}: {e}")
        import traceback
        traceback.print_exc()
        conn.rollback()
        return False


INVALID_APP_FILE = "invalid_app_ids.txt"

def log_invalid_app_id(app_id):
    """Append invalid app IDs to a text file (one per line, no duplicates)"""
    try:
        if os.path.exists(INVALID_APP_FILE):
            with open(INVALID_APP_FILE, "r", encoding="utf-8") as f:
                existing = set(line.strip() for line in f if line.strip())
        else:
            existing = set()

        if str(app_id) not in existing:
            with open(INVALID_APP_FILE, "a", encoding="utf-8") as f:
                f.write(f"{app_id}\n")

    except Exception as e:
        print(f"⚠️ Failed to log invalid app ID {app_id}: {e}")


# -------------------------
# Main Execution
# -------------------------
def main():
    print("=" * 60)
    print("Enhanced Steam Game Scraper - Direct to Database")
    print("With Metacritic, Achievements, Controller Support & More")
    print("=" * 60)

    APPID_FOLDER = "missing_folder"  # Change to your folder
    POST_PROCESS_SCRIPT = "game_scorer.py"

    # Pick only NON-completed files
    txt_files = sorted(
        f for f in os.listdir(APPID_FOLDER)
        if f.endswith(".txt") and "-completed" not in f
    )

    if not txt_files:
        print(f"\n✅ No pending AppID files found in {APPID_FOLDER}")
        return

    print(f"\n📂 Found {len(txt_files)} pending AppID files")

    # Connect to database
    print("\n🔌 Connecting to database...")
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)
        print("✅ Connected to database successfully")
    except Exception as e:
        print(f"❌ Database connection failed: {e}")
        return

    try:
        for txt_file in txt_files:
            file_path = os.path.join(APPID_FOLDER, txt_file)

            print(f"\n📄 Processing file: {txt_file}")

            with open(file_path, "r", encoding="utf-8") as f:
                app_ids = [line.strip() for line in f if line.strip().isdigit()]

            print(f"📋 Loaded {len(app_ids)} app IDs from file")

            for app_id in app_ids:
                app_id = int(app_id)

                try:
                    success = scrape_app(app_id, cursor, conn)

                    if success:
                        conn.commit()
                    else:
                        conn.rollback()

                    time.sleep(1)

                except Exception as e:
                    print(f"🔥 Error processing app {app_id}: {e}")
                    conn.rollback()
                    time.sleep(2)

            # Rename file with date stamp
            date_stamp = datetime.now().strftime("%Y-%m-%d")
            completed_name = txt_file.replace(
                ".txt", f"-{date_stamp}-completed.txt"
            )
            completed_path = os.path.join(APPID_FOLDER, completed_name)

            os.rename(file_path, completed_path)
            print(f"✅ File marked completed: {completed_name}")

            # Run post-processing script
            if os.path.exists(POST_PROCESS_SCRIPT):
                print("🚀 Running game_scorer.py ...")
                try:
                    result = subprocess.run(
                        [sys.executable, POST_PROCESS_SCRIPT],
                        check=True,
                        capture_output=True,
                        text=True
                    )

                    print("✅ game_scorer.py completed successfully")
                    if result.stdout:
                        print(result.stdout)

                except subprocess.CalledProcessError as e:
                    print("❌ game_scorer.py failed")
                    print(e.stderr)

    except KeyboardInterrupt:
        print("\n🛑 Interrupted by user")

    finally:
        cursor.close()
        conn.close()
        print("\n🏁 Scraping session finished")


if __name__ == "__main__":
    main()
