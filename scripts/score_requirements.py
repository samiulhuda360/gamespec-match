"""Standalone scorer for the requirement rows that scripts/steam_scraper.py writes to MySQL.

The Laravel app scores requirements itself (app/Services/Scoring), on the scale the
compatibility check uses, and `php artisan games:import --rescore` re-scores every requirement
from its stored HTML. This script uses its own scale, so its scores are not comparable with the
app's, and it is not part of the app's setup.
"""

# Script is for internal use only

import re
import mysql.connector
import unicodedata

class GameRequirementsScorer:
    def __init__(self, connection):
        """Initialize with database connection and load hardware data"""
        self.connection = connection
        self.cpu_scores = {}
        self.gpu_scores = {}
        self.load_hardware_from_database()

    def load_hardware_from_database(self):
        """Load CPU and GPU benchmark scores from database"""
        print("\n" + "="*80)
        print("Loading hardware data from database...")
        print("="*80)

        cursor = self.connection.cursor(dictionary=True)

        try:
            # Load CPUs
            cursor.execute("""
                SELECT id, manufacturer, name, benchmark_score, generation
                FROM cpus
                WHERE benchmark_score IS NOT NULL
                ORDER BY benchmark_score DESC
            """)

            cpus = cursor.fetchall()
            for cpu in cpus:
                # Create multiple keys for better matching
                name_lower = cpu['name'].lower()
                self.cpu_scores[name_lower] = cpu['benchmark_score'] / 100  # Normalize to 0-1000 scale

                # Add simplified keys (e.g., "i9-14900k" -> "i9-14")
                if 'intel' in cpu['manufacturer'].lower():
                    # Extract i3/i5/i7/i9 and generation
                    match = re.search(r'(i\d)-(\d{2})', name_lower)
                    if match:
                        simple_key = f"{match.group(1)}-{match.group(2)}"
                        if simple_key not in self.cpu_scores:
                            self.cpu_scores[simple_key] = cpu['benchmark_score'] / 100

                elif 'amd' in cpu['manufacturer'].lower():
                    # Extract Ryzen 3/5/7/9 and generation
                    match = re.search(r'ryzen\s+(\d)\s+(\d)', name_lower)
                    if match:
                        simple_key = f"ryzen {match.group(1)} {match.group(2)}"
                        if simple_key not in self.cpu_scores:
                            self.cpu_scores[simple_key] = cpu['benchmark_score'] / 100

            print(f"  ✓ Loaded {len(cpus)} CPUs")

            # Load GPUs
            cursor.execute("""
                SELECT id, manufacturer, name, benchmark_score, vram_gb
                FROM gpus
                WHERE benchmark_score IS NOT NULL
                ORDER BY benchmark_score DESC
            """)

            gpus = cursor.fetchall()
            for gpu in gpus:
                # Create multiple keys for better matching
                name_lower = gpu['name'].lower()
                self.gpu_scores[name_lower] = gpu['benchmark_score'] / 35  # Normalize 3DMark to 0-1000 scale

                # Add simplified keys (e.g., "rtx 4090" from "NVIDIA GeForce RTX 4090")
                if 'rtx' in name_lower:
                    match = re.search(r'rtx\s+(\d{4}(?:\s+ti)?)', name_lower)
                    if match:
                        simple_key = f"rtx {match.group(1)}"
                        if simple_key not in self.gpu_scores:
                            self.gpu_scores[simple_key] = gpu['benchmark_score'] / 35

                elif 'gtx' in name_lower:
                    match = re.search(r'gtx\s+(\d{4}(?:\s+ti)?)', name_lower)
                    if match:
                        simple_key = f"gtx {match.group(1)}"
                        if simple_key not in self.gpu_scores:
                            self.gpu_scores[simple_key] = gpu['benchmark_score'] / 35

                elif 'rx' in name_lower:
                    match = re.search(r'rx\s+(\d{4}(?:\s+xt)?)', name_lower)
                    if match:
                        simple_key = f"rx {match.group(1)}"
                        if simple_key not in self.gpu_scores:
                            self.gpu_scores[simple_key] = gpu['benchmark_score'] / 35

            # Add fallback values for old/integrated graphics
            self.gpu_scores.update({
                'integrated graphics': 30,
                'integrated': 30,
                'no dedicated gpu': 20,
                'no gpu': 20,
                'onboard': 20,
                'intel hd': 40,
                'intel uhd': 45,
                'iris xe': 50,
                'vega': 50,
            })

            print(f"  ✓ Loaded {len(gpus)} GPUs")

            cursor.close()

        except Exception as e:
            print(f"  ✗ Error loading hardware data: {e}")
            raise

    def parse_cpu(self, cpu_text: str) -> float:
        """Parse CPU string and return performance score"""
        if not cpu_text or str(cpu_text).strip() == '':
            return 0

        cpu_text = str(cpu_text).lower().strip()

        # Handle "or higher", "or better"
        cpu_text = re.sub(r'(or higher|or better|\(.*?\))', '', cpu_text)

        # Try exact match first
        if cpu_text in self.cpu_scores:
            return self.cpu_scores[cpu_text]

        # Try to find CPU score by partial matching
        max_score = 0
        for key, score in sorted(self.cpu_scores.items(), key=lambda x: len(x[0]), reverse=True):
            if key in cpu_text:
                max_score = max(max_score, score)

        if max_score > 0:
            return max_score

        # Fallback: GHz
        ghz_match = re.search(r'(\d+\.?\d*)\s*ghz', cpu_text)
        if ghz_match:
            ghz = float(ghz_match.group(1))
            return max(50, min(400, ghz * 80))

        # Fallback: thread count
        thread_match = re.search(r'(\d+)\s*hardware cpu threads', cpu_text)
        if thread_match:
            threads = int(thread_match.group(1))
            return max(100, threads * 60)

        return 100

    def parse_ram(self, ram_text: str) -> float:
        """Parse RAM string and return GB value"""
        if not ram_text or str(ram_text).strip() == '':
            return 0

        ram_text = str(ram_text).lower()

        # Extract GB value
        gb_match = re.search(r'(\d+)\s*gb', ram_text)
        if gb_match:
            return float(gb_match.group(1))

        # Extract MB value and convert
        mb_match = re.search(r'(\d+)\s*mb', ram_text)
        if mb_match:
            return float(mb_match.group(1)) / 1024

        return 2

    def parse_gpu(self, gpu_text: str) -> float:
        """Parse GPU string and return performance score"""
        if not gpu_text or str(gpu_text).strip() == '':
            return 0

        gpu_text = str(gpu_text).lower().strip()

        # Handle integrated graphics explicitly
        if ('integrated graphics' in gpu_text or
            'no dedicated gpu' in gpu_text or
            'no gpu' in gpu_text or
            'onboard' in gpu_text):
            return 20

        # Handle "or better", memory specs
        gpu_text = re.sub(r'(or better|or newer|\d+gb|\(.*?\))', '', gpu_text)

        # Try exact match first
        if gpu_text in self.gpu_scores:
            return self.gpu_scores[gpu_text]

        # Try to find GPU score by partial matching
        max_score = 0
        for key, score in sorted(self.gpu_scores.items(), key=lambda x: len(x[0]), reverse=True):
            if key in gpu_text:
                max_score = max(max_score, score)

        if max_score > 0:
            return max_score

        # Check for VRAM as fallback
        vram_match = re.search(r'(\d+)\s*gb', gpu_text)
        if vram_match:
            vram = int(vram_match.group(1))
            return max(100, min(600, vram * 150))

        # Check for DirectX
        if 'directx 12' in gpu_text or 'dx12' in gpu_text:
            return 300
        elif 'directx 11' in gpu_text or 'dx11' in gpu_text:
            return 200
        elif 'directx 10' in gpu_text or 'dx10' in gpu_text:
            return 150

        return 100

    def parse_storage(self, storage_text: str) -> float:
        """Parse storage string and return GB value"""
        if not storage_text or str(storage_text).strip() == '':
            return 0

        storage_text = str(storage_text).lower()

        # Extract GB value
        gb_match = re.search(r'(\d+)\s*gb', storage_text)
        if gb_match:
            return float(gb_match.group(1))

        return 10

    def calculate_weighted_score(self, cpu: float, ram: float, gpu: float) -> float:
        """Calculate weighted performance score (GPU 50%, CPU 35%, RAM 15%)"""
        # Normalize RAM to 0-1000 scale
        ram_normalized = min(1000, (ram / 32) * 1000)

        # Weighted average
        weighted = (gpu * 0.50) + (cpu * 0.35) + (ram_normalized * 0.15)
        return round(weighted, 2)

    def slugify(self, text: str) -> str:
        """Convert text to URL-friendly slug"""
        if not text:
            return ''

        # Convert to lowercase
        text = str(text).lower()

        # Normalize unicode characters
        text = unicodedata.normalize('NFKD', text)
        text = text.encode('ascii', 'ignore').decode('ascii')

        # Replace special characters with hyphens
        text = re.sub(r'[^\w\s-]', '', text)
        text = re.sub(r'[-\s]+', '-', text)

        # Remove leading/trailing hyphens
        text = text.strip('-')

        # Limit length to 250 characters
        text = text[:250]

        return text if text else 'game'

    def add_slug_column(self):
        """Add slug column to apps table if it doesn't exist"""
        print("\n" + "="*80)
        print("Checking slug column...")
        print("="*80)

        cursor = self.connection.cursor()

        try:
            # Check if slug column exists
            cursor.execute("""
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'apps'
                AND COLUMN_NAME = 'slug'
            """)

            column_exists = cursor.fetchone()[0] > 0

            if not column_exists:
                print("  Adding slug column to apps table...")
                cursor.execute("""
                    ALTER TABLE apps
                    ADD COLUMN slug VARCHAR(300) NULL AFTER name
                """)
                self.connection.commit()
                print("  ✓ Slug column added successfully")
            else:
                print("  ✓ Slug column already exists")

            cursor.close()

        except Exception as e:
            print(f"  ✗ Error checking/adding slug column: {e}")
            self.connection.rollback()
            raise

    def generate_all_slugs(self):
        """Generate slugs for all games in the database"""
        print("\n" + "="*80)
        print("Generating slugs for all games...")
        print("="*80)

        cursor = self.connection.cursor(dictionary=True)

        try:
            # Get all games without slugs or with empty slugs
            cursor.execute("""
                SELECT app_id, name
                FROM apps
                WHERE slug IS NULL OR slug = ''
                ORDER BY app_id
            """)

            games = cursor.fetchall()
            total = len(games)

            if total == 0:
                print("  ✓ All games already have slugs")
                cursor.close()
                return

            print(f"Found {total} games without slugs. Generating...")

            update_cursor = self.connection.cursor()
            updated = 0

            for i, game in enumerate(games, 1):
                try:
                    app_id = game['app_id']
                    name = game['name']

                    # Skip if name is None or empty
                    if not name or name.strip() == '':
                        slug = f'game-{app_id}'
                    else:
                        # Generate slug from name
                        slug = self.slugify(name)

                        if not slug:
                            slug = f'game-{app_id}'

                        # Check for duplicate slugs
                        check_cursor = self.connection.cursor()
                        check_cursor.execute(
                            "SELECT COUNT(*) as count FROM apps WHERE slug = %s AND app_id != %s",
                            (slug, app_id)
                        )
                        result = check_cursor.fetchone()
                        check_cursor.close()

                        if result and result[0] > 0:
                            slug = f'{slug}-{app_id}'

                    # Update the slug
                    update_cursor.execute(
                        "UPDATE apps SET slug = %s WHERE app_id = %s",
                        (slug, app_id)
                    )
                    updated += 1

                    # Show progress every 500 games
                    if i % 500 == 0:
                        self.connection.commit()
                        print(f"  Progress: {i}/{total} games processed ({(i/total*100):.1f}%)...")

                except Exception as e:
                    print(f"  ✗ Error processing app_id {game.get('app_id', 'unknown')}: {e}")
                    continue

            self.connection.commit()
            print(f"\n✓ Successfully generated slugs for {updated} games")

            # Add indexes for better performance
            print("\n" + "="*80)
            print("Adding indexes...")
            print("="*80)

            try:
                # Check if index exists
                cursor.execute("""
                    SELECT COUNT(*) as count
                    FROM information_schema.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'apps'
                    AND INDEX_NAME = 'idx_slug'
                """)

                result = cursor.fetchone()
                if result and result['count'] == 0:
                    update_cursor.execute("CREATE INDEX idx_slug ON apps(slug)")
                    print("  ✓ Added index on slug column")
                else:
                    print("  ✓ Index already exists")

                # Check if unique constraint exists
                cursor.execute("""
                    SELECT COUNT(*) as count
                    FROM information_schema.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'apps'
                    AND INDEX_NAME = 'unique_slug'
                """)

                result = cursor.fetchone()
                if result and result['count'] == 0:
                    update_cursor.execute("ALTER TABLE apps ADD UNIQUE KEY unique_slug (slug)")
                    print("  ✓ Added unique constraint on slug column")
                else:
                    print("  ✓ Unique constraint already exists")

                self.connection.commit()

            except Exception as e:
                print(f"  ✗ Error adding indexes: {e}")
                # Continue even if indexes fail

            update_cursor.close()
            cursor.close()

            # Show some examples
            print("\n" + "="*80)
            print("Sample slugs (first 10):")
            print("="*80)

            cursor = self.connection.cursor(dictionary=True)
            cursor.execute("""
                SELECT app_id, name, slug
                FROM apps
                WHERE slug IS NOT NULL AND slug != ''
                ORDER BY app_id
                LIMIT 10
            """)

            for game in cursor.fetchall():
                name_display = (game['name'][:47] + '...') if len(game['name']) > 50 else game['name']
                print(f"  {game['app_id']:<10} {name_display:<50} → {game['slug']}")

            cursor.close()

        except Exception as e:
            print(f"✗ Error generating slugs: {e}")
            import traceback
            traceback.print_exc()
            self.connection.rollback()
            raise

    def score_requirements_in_database(self):
        """Score all requirements directly in the database"""
        print("\n" + "="*80)
        print("Scoring system requirements in database...")
        print("="*80)

        cursor = self.connection.cursor(dictionary=True)

        try:
            # Get all requirements
            cursor.execute("""
                SELECT id, app_id, platform,
                       min_cpu, min_ram, min_gpu, min_storage,
                       rec_cpu, rec_ram, rec_gpu, rec_storage
                FROM sys_requirements
                ORDER BY id
            """)

            requirements = cursor.fetchall()
            total = len(requirements)

            if total == 0:
                print("✓ No requirements found")
                cursor.close()
                return

            print(f"Found {total} requirements to score. Processing...")

            update_cursor = self.connection.cursor()
            updated = 0

            for i, req in enumerate(requirements, 1):
                try:
                    # Parse minimum requirements
                    min_cpu_score = self.parse_cpu(req['min_cpu'] or '')
                    min_ram_score = self.parse_ram(req['min_ram'] or '')
                    min_gpu_score = self.parse_gpu(req['min_gpu'] or '')
                    min_storage_score = self.parse_storage(req['min_storage'] or '')

                    # Parse recommended requirements
                    rec_cpu_score = self.parse_cpu(req['rec_cpu'] or '')
                    rec_ram_score = self.parse_ram(req['rec_ram'] or '')
                    rec_gpu_score = self.parse_gpu(req['rec_gpu'] or '')
                    rec_storage_score = self.parse_storage(req['rec_storage'] or '')

                    # Calculate overall scores
                    min_overall_score = self.calculate_weighted_score(
                        min_cpu_score, min_ram_score, min_gpu_score
                    )
                    rec_overall_score = self.calculate_weighted_score(
                        rec_cpu_score, rec_ram_score, rec_gpu_score
                    )

                    # Update the database
                    update_cursor.execute("""
                        UPDATE sys_requirements
                        SET min_cpu_score = %s,
                            min_ram_score = %s,
                            min_gpu_score = %s,
                            min_storage_score = %s,
                            min_overall_score = %s,
                            rec_cpu_score = %s,
                            rec_ram_score = %s,
                            rec_gpu_score = %s,
                            rec_storage_score = %s,
                            rec_overall_score = %s
                        WHERE id = %s
                    """, (
                        min_cpu_score, min_ram_score, min_gpu_score, min_storage_score, min_overall_score,
                        rec_cpu_score, rec_ram_score, rec_gpu_score, rec_storage_score, rec_overall_score,
                        req['id']
                    ))

                    updated += 1

                    # Show progress and commit every 100 records
                    if i % 100 == 0:
                        self.connection.commit()
                        print(f"  Progress: {i}/{total} ({(i/total*100):.1f}%)")

                except Exception as e:
                    print(f"  ✗ Error scoring requirement id {req['id']} (app_id {req['app_id']}): {e}")
                    continue

            self.connection.commit()
            print(f"\n✓ Successfully scored {updated} requirements")

            update_cursor.close()
            cursor.close()

        except Exception as e:
            print(f"✗ Error: {e}")
            import traceback
            traceback.print_exc()
            self.connection.rollback()
            raise

    def update_game_metrics_table(self):
        """Update game_metrics table after sys_requirements are updated"""
        print("\n" + "="*80)
        print("Updating game_metrics table...")
        print("="*80)

        cursor = self.connection.cursor()

        try:
            query = """
            INSERT INTO game_metrics (app_id, min_requirement_score, rec_requirement_score,
                                     intensity_tier, has_pc_requirements, has_mac_requirements,
                                     has_linux_requirements)
            SELECT
                sr.app_id,
                ROUND(AVG(COALESCE(sr.min_overall_score, 0)), 2) as min_requirement_score,
                ROUND(AVG(COALESCE(sr.rec_overall_score, 0)), 2) as rec_requirement_score,
                CASE
                    WHEN AVG(COALESCE(sr.rec_overall_score, sr.min_overall_score, 0)) >= 800 THEN 'ultra'
                    WHEN AVG(COALESCE(sr.rec_overall_score, sr.min_overall_score, 0)) >= 600 THEN 'high-end'
                    WHEN AVG(COALESCE(sr.rec_overall_score, sr.min_overall_score, 0)) >= 400 THEN 'medium'
                    ELSE 'light'
                END as intensity_tier,
                MAX(CASE WHEN sr.platform = 'pc' THEN 1 ELSE 0 END) as has_pc_requirements,
                MAX(CASE WHEN sr.platform = 'mac' THEN 1 ELSE 0 END) as has_mac_requirements,
                MAX(CASE WHEN sr.platform = 'linux' THEN 1 ELSE 0 END) as has_linux_requirements
            FROM sys_requirements sr
            WHERE sr.min_overall_score IS NOT NULL OR sr.rec_overall_score IS NOT NULL
            GROUP BY sr.app_id
            ON DUPLICATE KEY UPDATE
                min_requirement_score = VALUES(min_requirement_score),
                rec_requirement_score = VALUES(rec_requirement_score),
                intensity_tier = VALUES(intensity_tier),
                has_pc_requirements = VALUES(has_pc_requirements),
                has_mac_requirements = VALUES(has_mac_requirements),
                has_linux_requirements = VALUES(has_linux_requirements),
                updated_at = CURRENT_TIMESTAMP
            """

            cursor.execute(query)
            affected = cursor.rowcount
            self.connection.commit()

            print(f"✓ Updated {affected} game metrics")

            # Show summary
            cursor.execute("""
                SELECT
                    intensity_tier,
                    COUNT(*) as game_count,
                    ROUND(AVG(min_requirement_score), 2) as avg_min,
                    ROUND(AVG(rec_requirement_score), 2) as avg_rec
                FROM game_metrics
                WHERE min_requirement_score IS NOT NULL OR rec_requirement_score IS NOT NULL
                GROUP BY intensity_tier
                ORDER BY FIELD(intensity_tier, 'light', 'medium', 'high-end', 'ultra')
            """)

            print("\n" + "="*80)
            print("Game Metrics Summary:")
            print("="*80)
            print(f"{'Tier':<15} {'Count':<10} {'Avg Min Score':<20} {'Avg Rec Score':<20}")
            print("-" * 80)

            for row in cursor.fetchall():
                print(f"{row[0]:<15} {row[1]:<10} {row[2]:<20} {row[3]:<20}")

            cursor.close()

        except Exception as e:
            print(f"✗ Error updating game_metrics: {e}")
            import traceback
            traceback.print_exc()
            self.connection.rollback()

# Main execution
if __name__ == "__main__":
    try:
        print("\n" + "="*80)
        print("GAME REQUIREMENTS SCORER & SLUG GENERATOR")
        print("="*80)
        print("Database Connection")
        print("="*80)

        # Database configuration - MODIFY THESE VALUES
        db_config = {
            'host': '127.0.0.1',
            'user': 'root',
            'password': '',  # Add your password here
            'database': 'steamdb'
        }

        connection = mysql.connector.connect(**db_config)
        print("✓ Connected to database")

        # Initialize scorer with connection
        scorer = GameRequirementsScorer(connection)

        # 1. Add slug column if it doesn't exist
        scorer.add_slug_column()

        # 2. Generate slugs for all games
        scorer.generate_all_slugs()

        # 3. Score requirements directly in database
        scorer.score_requirements_in_database()

        # 4. Update game_metrics table
        scorer.update_game_metrics_table()

        connection.close()

        print("\n" + "="*80)
        print("✓ ALL OPERATIONS COMPLETE!")
        print("="*80)

    except Exception as e:
        print(f"\n✗ Error: {e}")
        import traceback
        traceback.print_exc()
