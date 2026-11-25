/**
 * ============================================================================
 * Weave Games – Space Orbit Arcade: 5-Level-Kampagne & Boss-Showdown
 * ============================================================================
 * Features:
 * - 5 abwechslungsreiche Level mit wechselnden Hintergründen:
 *   1. Tiefraum & Nebel-Orbit (Boss: Viper Frigate)
 *   2. Asteroiden-Canyon      (Boss: Dreadnought Cruiser)
 *   3. Planeten-Oberfläche    (Boss: Sandstorm Gunship)
 *   4. Planeten-Basis         (Boss: Fortress Warden)
 *   5. Orbital-Zitadelle      (Final Boss: Titan Mothership)
 * - Dynamische, kurvige Seitenränder (Canyons, Asteroiden, Basis-Wände)
 * - Zerstörbare gegnerische Rand-Geschütztürme an beiden Wänden
 * - Freie 2D-Steuerung (WASD / Pfeiltasten / Maus / Touch in X & Y)
 * - Kein Dauerfeuer per gehaltener Leertaste (Einzelschuss pro Tastendruck + Cooldown)
 * - Einsammelbare Waffen-Upgrades (4 Stufen) & Schild-Reparaturen
 * - Schwerere 2-Phasen-Endbosse mit Enrage-Modus ab 50% HP
 * ============================================================================
 */

function initSpaceOrbitGame() {
    const intro = document.getElementById('spaceOrbitIntro');
    const canvas = document.getElementById('spaceOrbitCanvas');
    if (!canvas) return;

    if (intro) intro.style.display = 'none';
    canvas.style.display = 'block';

    // Verhindere doppelte Event-Listener bei mehrfachem Start
    if (window.__spaceOrbitCleanup) {
        window.__spaceOrbitCleanup();
    }

    const ctx = canvas.getContext('2d');
    const width = (canvas.width = 800);
    const height = (canvas.height = 460);

    const WEAPONS = {
        1: { name: 'Doppel-Laser', color: '#38bdf8', cooldown: 11 },
        2: { name: 'Dreifach-Streulaser', color: '#22d3ee', cooldown: 11 },
        3: { name: 'Plasma-Fächer', color: '#34d399', cooldown: 10 },
        4: { name: 'Hyper-Blaster', color: '#fbbf24', cooldown: 9 }
    };

    const LEVELS = {
        1: {
            name: 'Tiefraum & Nebel-Orbit',
            theme: 'space',
            minionsNeeded: 12,
            scrollSpeed: 1.9,
            wallBase: 36,
            wallAmp: 24,
            turretChance: 0.42,
            bgTop: '#020617',
            bgBot: '#0b1129',
            wallFill: '#1e293b',
            wallEdge: '#475569',
            minionColor: '#f97316',
            boss: {
                name: 'Viper Frigate',
                title: 'BOSS 1: VIPER FRIGATE',
                maxHp: 36,
                w: 104,
                h: 44,
                speed: 2.6,
                shootCooldown: 52,
                color: '#e11d48',
                bulletSpeed: 4.8
            }
        },
        2: {
            name: 'Asteroiden-Canyon',
            theme: 'asteroid',
            minionsNeeded: 15,
            scrollSpeed: 2.2,
            wallBase: 58,
            wallAmp: 38,
            turretChance: 0.52,
            bgTop: '#0c0a09',
            bgBot: '#1c1917',
            wallFill: '#44403c',
            wallEdge: '#78716c',
            minionColor: '#c084fc',
            boss: {
                name: 'Dreadnought Cruiser',
                title: 'BOSS 2: DREADNOUGHT CRUISER',
                maxHp: 56,
                w: 136,
                h: 50,
                speed: 2.9,
                shootCooldown: 44,
                color: '#9333ea',
                bulletSpeed: 5.2
            }
        },
        3: {
            name: 'Planeten-Oberfläche',
            theme: 'planet_surface',
            minionsNeeded: 18,
            scrollSpeed: 2.5,
            wallBase: 66,
            wallAmp: 46,
            turretChance: 0.60,
            bgTop: '#451a03',
            bgBot: '#7c2d12',
            wallFill: '#9a3412',
            wallEdge: '#ea580c',
            minionColor: '#fbbf24',
            boss: {
                name: 'Sandstorm Gunship',
                title: 'BOSS 3: SANDSTORM GUNSHIP',
                maxHp: 78,
                w: 148,
                h: 54,
                speed: 3.1,
                shootCooldown: 38,
                color: '#d97706',
                bulletSpeed: 5.6
            }
        },
        4: {
            name: 'Planeten-Basis (Bunkergraben)',
            theme: 'planet_base',
            minionsNeeded: 20,
            scrollSpeed: 2.7,
            wallBase: 74,
            wallAmp: 40,
            turretChance: 0.68,
            bgTop: '#0f172a',
            bgBot: '#1e293b',
            wallFill: '#334155',
            wallEdge: '#94a3b8',
            minionColor: '#38bdf8',
            boss: {
                name: 'Fortress Warden',
                title: 'BOSS 4: FORTRESS WARDEN',
                maxHp: 102,
                w: 168,
                h: 58,
                speed: 2.7,
                shootCooldown: 34,
                color: '#0284c7',
                bulletSpeed: 6.0
            }
        },
        5: {
            name: 'Orbital-Zitadelle (Kern)',
            theme: 'citadel',
            minionsNeeded: 24,
            scrollSpeed: 3.0,
            wallBase: 68,
            wallAmp: 48,
            turretChance: 0.74,
            bgTop: '#1a0414',
            bgBot: '#3b0724',
            wallFill: '#4c0519',
            wallEdge: '#f43f5e',
            minionColor: '#fb7185',
            boss: {
                name: 'Titan Mothership',
                title: 'FINAL BOSS 5: TITAN MOTHERSHIP',
                maxHp: 140,
                w: 196,
                h: 64,
                speed: 2.9,
                shootCooldown: 28,
                color: '#f59e0b',
                bulletSpeed: 6.4
            }
        }
    };

    const TOTAL_LEVELS = 5;

    let player = {
        x: width / 2 - 18,
        y: height - 64,
        w: 36,
        h: 26,
        speed: 6.8,
        targetX: width / 2 - 18,
        targetY: height - 64,
        lives: 4,
        maxLives: 5,
        invulnerableFrames: 0,
        weaponLevel: 1,
        shotCooldown: 0
    };

    let currentLevel = 1;
    let levelState = 'waves'; // 'waves', 'boss_warning', 'boss', 'level_cleared', 'victory'
    let minionsKilled = 0;
    let stateTimer = 0;
    let activeBoss = null;

    let worldScrollY = 0;
    let frameCount = 0;
    let nextTurretScroll = 140;

    let bullets = [];
    let enemyBullets = [];
    let enemies = [];
    let edgeTurrets = [];
    let powerups = [];
    let particles = [];
    let score = 0;
    let gameOver = false;
    let victory = false;
    let keys = {};
    let spacePressedLatch = false;
    let isMouseControlled = false;
    let rafId = null;
    let spawnTimer = null;

    let waveFocusX = width / 2;
    let waveDirection = 1;

    // Dynamische, nicht schnurgerade Seitenränder (abhängig von Welt-Scroll-Y)
    function getCorridorBoundsAtScreenY(screenY) {
        const cfg = LEVELS[currentLevel] || LEVELS[1];
        const wy = worldScrollY + (height - screenY);
        const bossFactor = (levelState === 'boss') ? 0.55 : 1.0;

        const leftWave =
            Math.sin(wy * 0.0085 + currentLevel * 1.1) * (cfg.wallAmp * 0.62) +
            Math.sin(wy * 0.021 + 0.7) * (cfg.wallAmp * 0.38);
        const rightWave =
            Math.cos(wy * 0.0078 + currentLevel * 1.7) * (cfg.wallAmp * 0.62) +
            Math.sin(wy * 0.019 + 2.3) * (cfg.wallAmp * 0.38);

        let leftX = Math.max(14, (cfg.wallBase + leftWave) * bossFactor);
        let rightX = width - Math.max(14, (cfg.wallBase + rightWave) * bossFactor);

        // Sicherheits-Mindestbreite des Flugkorridors (mind. 340px)
        const minGap = 340;
        if (rightX - leftX < minGap) {
            const mid = (leftX + rightX) / 2;
            leftX = Math.max(14, mid - minGap / 2);
            rightX = Math.min(width - 14, mid + minGap / 2);
        }
        return { leftX, rightX };
    }

    function setTargetFromPointer(clientX, clientY) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const canvasX = (clientX - rect.left) * scaleX;
        const canvasY = (clientY - rect.top) * scaleY;
        player.targetX = canvasX - player.w / 2;
        player.targetY = Math.max(58, Math.min(height - player.h - 10, canvasY - player.h / 2));
        isMouseControlled = true;
    }

    function onMouseMove(e) {
        setTargetFromPointer(e.clientX, e.clientY);
    }

    function onMouseDown() {
        if (!gameOver && !victory) {
            shoot();
        } else {
            restartCampaign();
        }
    }

    function onTouchMove(e) {
        if (e.touches && e.touches[0]) {
            setTargetFromPointer(e.touches[0].clientX, e.touches[0].clientY);
            e.preventDefault();
        }
    }

    function onTouchStart(e) {
        if (e.touches && e.touches[0]) {
            setTargetFromPointer(e.touches[0].clientX, e.touches[0].clientY);
            if (!gameOver && !victory) shoot();
            else restartCampaign();
            e.preventDefault();
        }
    }

    // Tastatur-Steuerung: Dauerfeuer per Leertaste explizit unterbunden!
    function onKeyDown(e) {
        const isSpace = (e.key === ' ' || e.key === 'Spacebar' || e.code === 'Space');
        if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key) || isSpace) {
            e.preventDefault();
        }

        if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'w', 'W', 'a', 'A', 's', 'S', 'd', 'D'].includes(e.key)) {
            keys[e.key] = true;
            isMouseControlled = false;
        }

        if (isSpace && !gameOver && !victory) {
            // Nur 1 Schuss pro echtem Tastendruck (kein Key-Repeat / kein Dauerfeuer!)
            if (!e.repeat && !spacePressedLatch) {
                spacePressedLatch = true;
                shoot();
            }
            return;
        }

        if ((gameOver || victory) && (e.key === 'Enter' || (isSpace && !e.repeat))) {
            restartCampaign();
        }
    }

    function onKeyUp(e) {
        keys[e.key] = false;
        if (e.key === ' ' || e.key === 'Spacebar' || e.code === 'Space') {
            spacePressedLatch = false;
        }
    }

    canvas.addEventListener('mousemove', onMouseMove);
    canvas.addEventListener('mousedown', onMouseDown);
    canvas.addEventListener('touchmove', onTouchMove, { passive: false });
    canvas.addEventListener('touchstart', onTouchStart, { passive: false });
    window.addEventListener('keydown', onKeyDown);
    window.addEventListener('keyup', onKeyUp);

    window.__spaceOrbitCleanup = function () {
        if (spawnTimer) clearInterval(spawnTimer);
        if (rafId) cancelAnimationFrame(rafId);
        canvas.removeEventListener('mousemove', onMouseMove);
        canvas.removeEventListener('mousedown', onMouseDown);
        canvas.removeEventListener('touchmove', onTouchMove);
        canvas.removeEventListener('touchstart', onTouchStart);
        window.removeEventListener('keydown', onKeyDown);
        window.removeEventListener('keyup', onKeyUp);
    };

    function shoot() {
        if (gameOver || victory) return;
        if (player.shotCooldown > 0) return;

        const wCfg = WEAPONS[player.weaponLevel] || WEAPONS[1];
        player.shotCooldown = wCfg.cooldown;
        const cx = player.x + player.w / 2;
        const topY = player.y;

        if (player.weaponLevel === 1) {
            bullets.push({ x: player.x + 5, y: topY, w: 4, h: 12, vx: 0, vy: -10.5, dmg: 1, color: wCfg.color });
            bullets.push({ x: player.x + player.w - 9, y: topY, w: 4, h: 12, vx: 0, vy: -10.5, dmg: 1, color: wCfg.color });
        } else if (player.weaponLevel === 2) {
            bullets.push({ x: cx - 2, y: topY - 3, w: 5, h: 14, vx: 0, vy: -11.5, dmg: 1, color: wCfg.color });
            bullets.push({ x: player.x + 3, y: topY + 2, w: 4, h: 12, vx: -1.6, vy: -10.5, dmg: 1, color: wCfg.color });
            bullets.push({ x: player.x + player.w - 7, y: topY + 2, w: 4, h: 12, vx: 1.6, vy: -10.5, dmg: 1, color: wCfg.color });
        } else if (player.weaponLevel === 3) {
            bullets.push({ x: cx - 6, y: topY - 4, w: 5, h: 15, vx: -0.4, vy: -12, dmg: 1.5, color: wCfg.color });
            bullets.push({ x: cx + 1, y: topY - 4, w: 5, h: 15, vx: 0.4, vy: -12, dmg: 1.5, color: wCfg.color });
            bullets.push({ x: player.x + 2, y: topY + 2, w: 4, h: 12, vx: -2.4, vy: -10.8, dmg: 1, color: '#6ee7b7' });
            bullets.push({ x: player.x + player.w - 6, y: topY + 2, w: 4, h: 12, vx: 2.4, vy: -10.8, dmg: 1, color: '#6ee7b7' });
        } else {
            // Stufe 4: Hyper-Blaster (schwerer Mittelstrahl + 4 Seiten-Plasmas)
            bullets.push({ x: cx - 4, y: topY - 6, w: 8, h: 18, vx: 0, vy: -13, dmg: 2.5, color: '#fef08a' });
            bullets.push({ x: cx - 12, y: topY, w: 4, h: 13, vx: -1.4, vy: -11.8, dmg: 1.2, color: wCfg.color });
            bullets.push({ x: cx + 8, y: topY, w: 4, h: 13, vx: 1.4, vy: -11.8, dmg: 1.2, color: wCfg.color });
            bullets.push({ x: player.x, y: topY + 4, w: 4, h: 12, vx: -3.0, vy: -10.8, dmg: 1, color: '#f97316' });
            bullets.push({ x: player.x + player.w - 4, y: topY + 4, w: 4, h: 12, vx: 3.0, vy: -10.8, dmg: 1, color: '#f97316' });
        }
    }

    function createExplosion(x, y, color = '#f97316', count = 12) {
        for (let i = 0; i < count; i++) {
            const angle = Math.random() * Math.PI * 2;
            const spd = Math.random() * 3.8 + 1;
            particles.push({
                x, y,
                vx: Math.cos(angle) * spd,
                vy: Math.sin(angle) * spd,
                life: 24 + Math.floor(Math.random() * 10),
                maxLife: 34,
                color
            });
        }
    }

    function maybeDropPowerup(x, y, bonusChance = 0) {
        const roll = Math.random();
        if (roll < 0.22 + bonusChance) {
            const type = (player.weaponLevel < 4 && Math.random() < 0.72) ? 'weapon' : 'shield';
            powerups.push({
                x: x - 11,
                y: y - 11,
                w: 22,
                h: 22,
                vy: 1.9,
                type
            });
        }
    }

    function damagePlayer() {
        if (player.invulnerableFrames > 0 || gameOver || victory) return;
        player.lives--;
        createExplosion(player.x + player.w / 2, player.y + player.h / 2, '#38bdf8', 20);
        if (player.lives <= 0) {
            gameOver = true;
            if (spawnTimer) clearInterval(spawnTimer);
        } else {
            player.invulnerableFrames = 80;
        }
    }

    function spawnEdgeTurret() {
        const cfg = LEVELS[currentLevel] || LEVELS[1];
        if (Math.random() > cfg.turretChance) return;
        const side = Math.random() < 0.5 ? 'left' : 'right';
        const bounds = getCorridorBoundsAtScreenY(-16);
        const hp = 2 + Math.floor(currentLevel * 0.7);
        edgeTurrets.push({
            side,
            y: -18,
            w: 24,
            h: 22,
            x: side === 'left' ? bounds.leftX - 4 : bounds.rightX - 20,
            hp,
            maxHp: hp,
            shootTimer: 35 + Math.floor(Math.random() * 45)
        });
    }

    function spawnMinion() {
        if (gameOver || victory || levelState !== 'waves') return;

        const bounds = getCorridorBoundsAtScreenY(0);
        const minX = bounds.leftX + 26;
        const maxX = bounds.rightX - 58;

        waveFocusX += (Math.random() * 65 + 35) * waveDirection;
        if (waveFocusX > maxX - 50) {
            waveFocusX = maxX - 50;
            waveDirection = -1;
        } else if (waveFocusX < minX + 50) {
            waveFocusX = minX + 50;
            waveDirection = 1;
        }

        const pattern = Math.random();
        const hp = currentLevel >= 4 ? 2 : 1;
        if (pattern < 0.45) {
            const sx = Math.max(minX, Math.min(maxX, waveFocusX + (Math.random() * 90 - 45)));
            enemies.push({
                x: sx, y: -26, w: 28, h: 22,
                vx: 0, speed: 1.9 + currentLevel * 0.22,
                hp, maxHp: hp,
                shootTimer: currentLevel >= 2 ? 75 + Math.floor(Math.random() * 55) : 9999,
                type: 'scout'
            });
        } else if (pattern < 0.80) {
            const base = Math.max(minX + 30, Math.min(maxX - 30, waveFocusX));
            enemies.push({ x: base - 26, y: -26, w: 26, h: 22, vx: 0, speed: 2.0 + currentLevel * 0.18, hp, maxHp: hp, shootTimer: 110, type: 'wingman' });
            enemies.push({ x: base + 26, y: -26, w: 26, h: 22, vx: 0, speed: 2.0 + currentLevel * 0.18, hp, maxHp: hp, shootTimer: 130, type: 'wingman' });
        } else {
            // Schwerer Zickzack-Jäger
            const sx = Math.max(minX + 20, Math.min(maxX - 20, waveFocusX));
            enemies.push({
                x: sx, y: -28, w: 32, h: 26,
                vx: (Math.random() < 0.5 ? -1 : 1) * (1.5 + currentLevel * 0.25),
                speed: 1.55 + currentLevel * 0.18,
                hp: hp + 1, maxHp: hp + 1,
                shootTimer: 65,
                type: 'striker'
            });
        }
    }

    function startBoss(cfg) {
        levelState = 'boss';
        activeBoss = {
            name: cfg.name,
            title: cfg.title,
            hp: cfg.maxHp,
            maxHp: cfg.maxHp,
            x: width / 2 - cfg.w / 2,
            y: 44,
            baseY: 44,
            w: cfg.w,
            h: cfg.h,
            vx: cfg.speed,
            shootCooldown: cfg.shootCooldown,
            timer: 0,
            specialTimer: 0,
            bulletSpeed: cfg.bulletSpeed,
            color: cfg.color,
            phase: 1
        };
    }

    function advanceToNextLevel() {
        if (currentLevel < TOTAL_LEVELS) {
            currentLevel++;
            levelState = 'waves';
            minionsKilled = 0;
            edgeTurrets = [];
            enemyBullets = [];
            if (player.lives < player.maxLives) player.lives++;
            createExplosion(player.x + player.w / 2, player.y + player.h / 2, '#22c55e', 26);
        } else {
            victory = true;
            levelState = 'victory';
            if (spawnTimer) clearInterval(spawnTimer);
            createExplosion(width / 2, height / 2, '#eab308', 60);
        }
    }

    function restartCampaign() {
        currentLevel = 1;
        levelState = 'waves';
        minionsKilled = 0;
        stateTimer = 0;
        activeBoss = null;
        worldScrollY = 0;
        nextTurretScroll = 140;

        player.x = width / 2 - 18;
        player.y = height - 64;
        player.targetX = player.x;
        player.targetY = player.y;
        player.lives = 4;
        player.invulnerableFrames = 0;
        player.weaponLevel = 1;
        player.shotCooldown = 0;

        bullets = [];
        enemyBullets = [];
        enemies = [];
        edgeTurrets = [];
        powerups = [];
        particles = [];
        score = 0;
        gameOver = false;
        victory = false;
        spacePressedLatch = false;
        waveFocusX = width / 2;
        waveDirection = 1;

        if (spawnTimer) clearInterval(spawnTimer);
        spawnTimer = setInterval(spawnMinion, 1020);
        rafId = requestAnimationFrame(loop);
    }

    function fireAimedBullet(sx, sy, speed, color, size = 8) {
        const px = player.x + player.w / 2;
        const py = player.y + player.h / 2;
        const dx = px - sx;
        const dy = py - sy;
        const dist = Math.hypot(dx, dy) || 1;
        enemyBullets.push({
            x: sx - size / 2,
            y: sy - size / 2,
            w: size,
            h: size + 3,
            vx: (dx / dist) * speed,
            vy: (dy / dist) * speed,
            color
        });
    }

    function update() {
        if (gameOver || victory) return;

        frameCount++;
        const currentLvlCfg = LEVELS[currentLevel] || LEVELS[1];
        worldScrollY += currentLvlCfg.scrollSpeed;

        if (player.invulnerableFrames > 0) player.invulnerableFrames--;
        if (player.shotCooldown > 0) player.shotCooldown--;

        // 2D Steuerung (X & Y frei im Raum fliegen!)
        if (isMouseControlled) {
            player.x += (player.targetX - player.x) * 0.24;
            player.y += (player.targetY - player.y) * 0.24;
        } else {
            if (keys['ArrowLeft'] || keys['a'] || keys['A']) {
                player.x -= player.speed;
                player.targetX = player.x;
            }
            if (keys['ArrowRight'] || keys['d'] || keys['D']) {
                player.x += player.speed;
                player.targetX = player.x;
            }
            if (keys['ArrowUp'] || keys['w'] || keys['W']) {
                player.y -= player.speed;
                player.targetY = player.y;
            }
            if (keys['ArrowDown'] || keys['s'] || keys['S']) {
                player.y += player.speed;
                player.targetY = player.y;
            }
        }

        // Vertikale Grenzen (im Raum bis hoch unter die Boss-Zone fliegbar)
        const topLimit = (levelState === 'boss') ? 130 : 54;
        player.y = Math.max(topLimit, Math.min(height - player.h - 10, player.y));

        // Kollision / Begrenzung an den kurvigen Seitenrändern
        const playerBounds = getCorridorBoundsAtScreenY(player.y + player.h / 2);
        if (player.x < playerBounds.leftX + 2) {
            player.x = playerBounds.leftX + 4;
            damagePlayer();
        } else if (player.x + player.w > playerBounds.rightX - 2) {
            player.x = playerBounds.rightX - player.w - 4;
            damagePlayer();
        }

        // Rand-Geschütztürme entlang der Strecke spawnen
        if (levelState === 'waves' && worldScrollY >= nextTurretScroll) {
            spawnEdgeTurret();
            nextTurretScroll = worldScrollY + Math.max(135, 250 - currentLevel * 22) + Math.random() * 80;
        }

        // Spieler-Geschosse aktualisieren (inkl. Wand-Kollision)
        for (let bi = bullets.length - 1; bi >= 0; bi--) {
            const b = bullets[bi];
            b.x += (b.vx || 0);
            b.y += b.vy;
            const wb = getCorridorBoundsAtScreenY(b.y);
            if (b.y < -20 || b.x < wb.leftX - 18 || b.x > wb.rightX + 18) {
                bullets.splice(bi, 1);
            }
        }

        // Gegnerische Plasmageschosse
        for (let ebi = enemyBullets.length - 1; ebi >= 0; ebi--) {
            const eb = enemyBullets[ebi];
            eb.x += (eb.vx || 0);
            eb.y += eb.vy;

            if (player.invulnerableFrames === 0 &&
                eb.x < player.x + player.w - 4 && eb.x + eb.w > player.x + 4 &&
                eb.y < player.y + player.h - 4 && eb.y + eb.h > player.y + 4) {
                enemyBullets.splice(ebi, 1);
                damagePlayer();
                if (gameOver) break;
                continue;
            }

            if (eb.y > height + 25 || eb.y < -30 || eb.x < -25 || eb.x > width + 25) {
                enemyBullets.splice(ebi, 1);
            }
        }

        // Rand-Geschütztürme aktualisieren & schießen lassen
        for (let ti = edgeTurrets.length - 1; ti >= 0; ti--) {
            const t = edgeTurrets[ti];
            t.y += currentLvlCfg.scrollSpeed;
            const wb = getCorridorBoundsAtScreenY(t.y + t.h / 2);
            t.x = (t.side === 'left') ? wb.leftX - 4 : wb.rightX - t.w + 4;

            // Schießen wenn sichtbar im oberen/mittleren Bereich
            if (t.y > 24 && t.y < height - 60) {
                t.shootTimer--;
                if (t.shootTimer <= 0) {
                    t.shootTimer = Math.max(58, 115 - currentLevel * 10);
                    fireAimedBullet(t.x + t.w / 2, t.y + t.h / 2, 3.6 + currentLevel * 0.35, '#fb7185', 8);
                }
            }

            // Spieler rammt Turm
            if (player.invulnerableFrames === 0 &&
                t.x < player.x + player.w && t.x + t.w > player.x &&
                t.y < player.y + player.h && t.y + t.h > player.y) {
                damagePlayer();
            }

            // Spieler-Laser trifft Rand-Geschütz
            let destroyed = false;
            for (let bi = bullets.length - 1; bi >= 0; bi--) {
                const b = bullets[bi];
                if (b.x < t.x + t.w && b.x + b.w > t.x &&
                    b.y < t.y + t.h && b.y + b.h > t.y) {
                    bullets.splice(bi, 1);
                    t.hp -= (b.dmg || 1);
                    createExplosion(b.x, b.y, '#fbbf24', 5);
                    if (t.hp <= 0) {
                        createExplosion(t.x + t.w / 2, t.y + t.h / 2, '#f97316', 18);
                        maybeDropPowerup(t.x + t.w / 2, t.y + t.h / 2, 0.28);
                        edgeTurrets.splice(ti, 1);
                        score += 180;
                        destroyed = true;
                        break;
                    }
                }
            }

            if (!destroyed && t.y > height + 30) {
                edgeTurrets.splice(ti, 1);
            }
        }

        // Power-Ups (Waffen-Upgrades & Schild)
        for (let pi = powerups.length - 1; pi >= 0; pi--) {
            const p = powerups[pi];
            p.y += p.vy;
            if (p.x < player.x + player.w && p.x + p.w > player.x &&
                p.y < player.y + player.h && p.y + p.h > player.y) {
                if (p.type === 'weapon') {
                    if (player.weaponLevel < 4) {
                        player.weaponLevel++;
                    } else {
                        score += 350;
                    }
                    createExplosion(p.x + 11, p.y + 11, '#fbbf24', 18);
                } else {
                    if (player.lives < player.maxLives) player.lives++;
                    score += 150;
                    createExplosion(p.x + 11, p.y + 11, '#22c55e', 16);
                }
                powerups.splice(pi, 1);
                continue;
            }
            if (p.y > height + 24) {
                powerups.splice(pi, 1);
            }
        }

        // Minion-Wellen
        for (let ei = enemies.length - 1; ei >= 0; ei--) {
            const e = enemies[ei];
            e.y += e.speed;
            e.x += (e.vx || 0);
            const eb = getCorridorBoundsAtScreenY(e.y + e.h / 2);
            if (e.x < eb.leftX + 8) {
                e.x = eb.leftX + 8;
                e.vx = Math.abs(e.vx || 0);
            } else if (e.x + e.w > eb.rightX - 8) {
                e.x = eb.rightX - e.w - 8;
                e.vx = -Math.abs(e.vx || 0);
            }

            // Minion schießt ab Level 2
            if (e.y > 25 && e.y < height - 110) {
                e.shootTimer--;
                if (e.shootTimer <= 0) {
                    e.shootTimer = Math.max(80, 150 - currentLevel * 14);
                    fireAimedBullet(e.x + e.w / 2, e.y + e.h, 3.6 + currentLevel * 0.25, '#f43f5e', 7);
                }
            }

            // Kollision mit Spieler
            if (player.invulnerableFrames === 0 &&
                e.x < player.x + player.w && e.x + e.w > player.x &&
                e.y < player.y + player.h && e.y + e.h > player.y) {
                enemies.splice(ei, 1);
                damagePlayer();
                if (gameOver) break;
                continue;
            }

            // Treffer durch Spieler-Laser
            let enemyHit = false;
            for (let bi = bullets.length - 1; bi >= 0; bi--) {
                const b = bullets[bi];
                if (b.x < e.x + e.w && b.x + b.w > e.x &&
                    b.y < e.y + e.h && b.y + b.h > e.y) {
                    bullets.splice(bi, 1);
                    e.hp -= (b.dmg || 1);
                    createExplosion(b.x, b.y, '#fbbf24', 5);
                    if (e.hp <= 0) {
                        createExplosion(e.x + e.w / 2, e.y + e.h / 2, '#f97316', 14);
                        maybeDropPowerup(e.x + e.w / 2, e.y + e.h / 2, 0);
                        enemies.splice(ei, 1);
                        score += 120;
                        if (levelState === 'waves') minionsKilled++;
                        enemyHit = true;
                        break;
                    }
                }
            }

            if (!enemyHit && e.y > height + 30) {
                enemies.splice(ei, 1);
            }
        }

        if (levelState === 'waves' && minionsKilled >= currentLvlCfg.minionsNeeded && enemies.length === 0) {
            levelState = 'boss_warning';
            stateTimer = 95;
        }

        if (levelState === 'boss_warning') {
            stateTimer--;
            if (stateTimer <= 0) {
                startBoss(currentLvlCfg.boss);
            }
        }

        // Schwerere 2-Phasen-Endbosse
        if (levelState === 'boss' && activeBoss) {
            const enraged = activeBoss.hp <= activeBoss.maxHp * 0.5;
            activeBoss.phase = enraged ? 2 : 1;
            const speedMult = enraged ? 1.38 : 1.0;

            activeBoss.x += activeBoss.vx * speedMult;
            activeBoss.y = activeBoss.baseY + Math.sin(frameCount * (enraged ? 0.055 : 0.032)) * (enraged ? 26 : 14);

            const bb = getCorridorBoundsAtScreenY(activeBoss.y + activeBoss.h / 2);
            if (activeBoss.x < bb.leftX + 12) {
                activeBoss.x = bb.leftX + 12;
                activeBoss.vx = Math.abs(activeBoss.vx);
            } else if (activeBoss.x + activeBoss.w > bb.rightX - 12) {
                activeBoss.x = bb.rightX - 12 - activeBoss.w;
                activeBoss.vx = -Math.abs(activeBoss.vx);
            }

            // Primär-Angriffe
            activeBoss.timer++;
            const effectiveCd = enraged ? Math.max(16, Math.floor(activeBoss.shootCooldown * 0.72)) : activeBoss.shootCooldown;
            if (activeBoss.timer >= effectiveCd) {
                activeBoss.timer = 0;
                const bx = activeBoss.x + activeBoss.w / 2;
                const by = activeBoss.y + activeBoss.h;

                if (currentLevel === 1) {
                    // Viper Frigate: Gezielter Doppelschuss + Fächer in Phase 2
                    fireAimedBullet(activeBoss.x + 18, by, activeBoss.bulletSpeed, '#ef4444', 8);
                    fireAimedBullet(activeBoss.x + activeBoss.w - 18, by, activeBoss.bulletSpeed, '#ef4444', 8);
                    if (enraged) {
                        enemyBullets.push({ x: bx - 4, y: by, w: 9, h: 15, vx: 0, vy: activeBoss.bulletSpeed + 0.8, color: '#fbbf24' });
                    }
                } else if (currentLevel === 2) {
                    // Dreadnought Cruiser: 3er Fächer + Seitengeschütze
                    for (const vx of [-1.7, 0, 1.7]) {
                        enemyBullets.push({ x: bx - 4, y: by, w: 9, h: 14, vx, vy: activeBoss.bulletSpeed, color: '#a855f7' });
                    }
                    if (enraged) {
                        fireAimedBullet(activeBoss.x + 16, by, activeBoss.bulletSpeed + 0.5, '#f43f5e', 8);
                        fireAimedBullet(activeBoss.x + activeBoss.w - 16, by, activeBoss.bulletSpeed + 0.5, '#f43f5e', 8);
                    }
                } else if (currentLevel === 3) {
                    // Sandstorm Gunship: 4er Salve + gezielter Kern-Schuss
                    for (const vx of [-2.4, -0.8, 0.8, 2.4]) {
                        enemyBullets.push({ x: bx - 4, y: by, w: 9, h: 15, vx, vy: activeBoss.bulletSpeed, color: '#f59e0b' });
                    }
                    fireAimedBullet(bx, by, activeBoss.bulletSpeed + 0.7, '#ef4444', 10);
                } else if (currentLevel === 4) {
                    // Fortress Warden: 5er Fächer + Zwillings-Zielschüsse
                    for (const vx of [-2.8, -1.4, 0, 1.4, 2.8]) {
                        enemyBullets.push({ x: bx - 4, y: by, w: 9, h: 15, vx, vy: activeBoss.bulletSpeed, color: '#38bdf8' });
                    }
                    if (enraged) {
                        fireAimedBullet(activeBoss.x + 20, by, activeBoss.bulletSpeed + 0.8, '#fb7185', 9);
                        fireAimedBullet(activeBoss.x + activeBoss.w - 20, by, activeBoss.bulletSpeed + 0.8, '#fb7185', 9);
                    }
                } else {
                    // Final Boss 5 (Titan Mothership): 5er Plasma-Fächer + Ziel-Salve + Ring-Impuls in Phase 2
                    for (const vx of [-3.0, -1.5, 0, 1.5, 3.0]) {
                        enemyBullets.push({ x: bx - 5, y: by, w: 10, h: 16, vx, vy: activeBoss.bulletSpeed, color: '#fbbf24' });
                    }
                    fireAimedBullet(activeBoss.x + 22, by, activeBoss.bulletSpeed + 0.9, '#ef4444', 9);
                    fireAimedBullet(activeBoss.x + activeBoss.w - 22, by, activeBoss.bulletSpeed + 0.9, '#ef4444', 9);
                }
            }

            // Spezial-Angriff (Eskort-Drohnen ab Level 3 in Phase 2)
            if (currentLevel >= 3 && enraged) {
                activeBoss.specialTimer++;
                if (activeBoss.specialTimer >= 210 && enemies.length < 3) {
                    activeBoss.specialTimer = 0;
                    enemies.push({
                        x: activeBoss.x + activeBoss.w / 2 - 14,
                        y: activeBoss.y + activeBoss.h,
                        w: 26, h: 20,
                        vx: (Math.random() < 0.5 ? -1.4 : 1.4),
                        speed: 2.1, hp: 2, maxHp: 2, shootTimer: 60, type: 'striker'
                    });
                }
            }

            // Spieler-Laser trifft Boss
            for (let bi = bullets.length - 1; bi >= 0; bi--) {
                const b = bullets[bi];
                if (b.x < activeBoss.x + activeBoss.w && b.x + b.w > activeBoss.x &&
                    b.y < activeBoss.y + activeBoss.h && b.y + b.h > activeBoss.y) {
                    bullets.splice(bi, 1);
                    activeBoss.hp -= (b.dmg || 1);
                    createExplosion(b.x, b.y, '#f59e0b', 6);
                    score += 40;

                    if (activeBoss.hp <= 0) {
                        createExplosion(activeBoss.x + activeBoss.w / 2, activeBoss.y + activeBoss.h / 2, '#ef4444', 45);
                        maybeDropPowerup(activeBoss.x + activeBoss.w / 2, activeBoss.y + activeBoss.h / 2, 0.8);
                        score += currentLevel * 1200;
                        activeBoss = null;
                        levelState = 'level_cleared';
                        stateTimer = 115;
                        break;
                    }
                }
            }
        }

        if (levelState === 'level_cleared') {
            stateTimer--;
            if (stateTimer <= 0) {
                advanceToNextLevel();
            }
        }

        // Partikel
        for (let pi = particles.length - 1; pi >= 0; pi--) {
            const p = particles[pi];
            p.x += p.vx;
            p.y += p.vy;
            p.life--;
            if (p.life <= 0) particles.splice(pi, 1);
        }
    }

    function drawBackground(cfg) {
        const grad = ctx.createLinearGradient(0, 0, 0, height);
        grad.addColorStop(0, cfg.bgTop);
        grad.addColorStop(1, cfg.bgBot);
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, width, height);

        if (cfg.theme === 'space') {
            // Nebel + 2-Layer Parallax-Sterne + ferner Planet
            const neb = ctx.createRadialGradient(width * 0.72, (worldScrollY * 0.12) % (height + 200) - 80, 15, width * 0.72, (worldScrollY * 0.12) % (height + 200) - 80, 190);
            neb.addColorStop(0, 'rgba(99, 102, 241, 0.24)');
            neb.addColorStop(1, 'transparent');
            ctx.fillStyle = neb;
            ctx.fillRect(0, 0, width, height);

            ctx.fillStyle = 'rgba(255,255,255,0.55)';
            for (let i = 0; i < 45; i++) {
                const sx = (i * 97) % width;
                const sy = (i * 61 + worldScrollY * (i % 2 === 0 ? 0.45 : 0.9)) % height;
                ctx.fillRect(sx, sy, i % 3 === 0 ? 2.5 : 1.5, i % 3 === 0 ? 2.5 : 1.5);
            }
        } else if (cfg.theme === 'asteroid') {
            // Treibende Hintergrund-Asteroiden & Staub
            ctx.fillStyle = 'rgba(255,255,255,0.28)';
            for (let i = 0; i < 30; i++) {
                const sx = (i * 113) % width;
                const sy = (i * 73 + worldScrollY * 0.5) % height;
                ctx.fillRect(sx, sy, 2, 2);
            }
            ctx.fillStyle = 'rgba(120, 113, 108, 0.22)';
            for (let i = 0; i < 8; i++) {
                const ax = 120 + ((i * 173) % (width - 240));
                const ay = (i * 130 + worldScrollY * 0.65) % (height + 80) - 40;
                ctx.beginPath();
                ctx.arc(ax, ay, 14 + (i % 3) * 7, 0, Math.PI * 2);
                ctx.fill();
            }
        } else if (cfg.theme === 'planet_surface') {
            // Planetenoberfläche: Krater, Dünenlinien & Staubstreifen
            ctx.strokeStyle = 'rgba(251, 146, 60, 0.16)';
            ctx.lineWidth = 2;
            const offset = worldScrollY % 64;
            for (let y = -64 + offset; y < height + 64; y += 64) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.bezierCurveTo(width * 0.3, y + 18, width * 0.7, y - 18, width, y);
                ctx.stroke();
            }
            ctx.fillStyle = 'rgba(67, 20, 7, 0.38)';
            for (let i = 0; i < 7; i++) {
                const cx = 140 + ((i * 149) % (width - 280));
                const cy = (i * 110 + worldScrollY * 0.85) % (height + 90) - 45;
                ctx.beginPath();
                ctx.ellipse(cx, cy, 26 + (i % 3) * 8, 16 + (i % 2) * 6, 0, 0, Math.PI * 2);
                ctx.fill();
            }
        } else if (cfg.theme === 'planet_base') {
            // Planetenbasis: Metall-Deckplatten, Gitter & leuchtende Reaktor-Leitungen
            const step = 48;
            const off = worldScrollY % step;
            ctx.strokeStyle = 'rgba(148, 163, 184, 0.12)';
            ctx.lineWidth = 1;
            for (let x = 0; x < width; x += step) {
                ctx.beginPath();
                ctx.moveTo(x, 0);
                ctx.lineTo(x, height);
                ctx.stroke();
            }
            for (let y = -step + off; y < height + step; y += step) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(width, y);
                ctx.stroke();
            }
            ctx.fillStyle = 'rgba(56, 189, 248, 0.14)';
            ctx.fillRect(width / 2 - 6, 0, 12, height);
        } else {
            // Orbital-Zitadelle: Dunkle Panzerplatten & rote Energieleitungen
            const step = 56;
            const off = worldScrollY % step;
            ctx.strokeStyle = 'rgba(244, 63, 94, 0.18)';
            ctx.lineWidth = 1.5;
            for (let y = -step + off; y < height + step; y += step) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(width, y);
                ctx.stroke();
            }
            ctx.fillStyle = 'rgba(245, 158, 11, 0.14)';
            ctx.fillRect(width * 0.35, 0, 4, height);
            ctx.fillRect(width * 0.65, 0, 4, height);
        }
    }

    function drawCorridorWalls(cfg) {
        const step = 12;

        // Linke Wand
        ctx.beginPath();
        ctx.moveTo(0, 0);
        for (let y = 0; y <= height; y += step) {
            const b = getCorridorBoundsAtScreenY(y);
            ctx.lineTo(b.leftX, y);
        }
        ctx.lineTo(0, height);
        ctx.closePath();
        ctx.fillStyle = cfg.wallFill;
        ctx.fill();

        // Rechte Wand
        ctx.beginPath();
        ctx.moveTo(width, 0);
        for (let y = 0; y <= height; y += step) {
            const b = getCorridorBoundsAtScreenY(y);
            ctx.lineTo(b.rightX, y);
        }
        ctx.lineTo(width, height);
        ctx.closePath();
        ctx.fillStyle = cfg.wallFill;
        ctx.fill();

        // Leuchtende / gepanzerte Kantenlinien
        ctx.strokeStyle = cfg.wallEdge;
        ctx.lineWidth = 3;
        ctx.beginPath();
        for (let y = 0; y <= height; y += step) {
            const b = getCorridorBoundsAtScreenY(y);
            if (y === 0) ctx.moveTo(b.leftX, y);
            else ctx.lineTo(b.leftX, y);
        }
        ctx.stroke();

        ctx.beginPath();
        for (let y = 0; y <= height; y += step) {
            const b = getCorridorBoundsAtScreenY(y);
            if (y === 0) ctx.moveTo(b.rightX, y);
            else ctx.lineTo(b.rightX, y);
        }
        ctx.stroke();
    }

    function draw() {
        const currentLvlCfg = LEVELS[currentLevel] || LEVELS[1];

        drawBackground(currentLvlCfg);
        drawCorridorWalls(currentLvlCfg);

        // Partikel
        particles.forEach(p => {
            ctx.fillStyle = p.color;
            ctx.globalAlpha = Math.max(0, p.life / p.maxLife);
            ctx.fillRect(p.x, p.y, 3.5, 3.5);
            ctx.globalAlpha = 1.0;
        });

        // Rand-Geschütztürme zeichnen
        edgeTurrets.forEach(t => {
            ctx.fillStyle = '#475569';
            ctx.fillRect(t.x, t.y, t.w, t.h);
            ctx.fillStyle = '#ef4444';
            ctx.beginPath();
            ctx.arc(t.x + t.w / 2, t.y + t.h / 2, 6, 0, Math.PI * 2);
            ctx.fill();
            // Mini-HP-Balken
            const ratio = Math.max(0, t.hp / t.maxHp);
            ctx.fillStyle = '#0f172a';
            ctx.fillRect(t.x, t.y - 6, t.w, 4);
            ctx.fillStyle = '#fbbf24';
            ctx.fillRect(t.x, t.y - 6, t.w * ratio, 4);
        });

        // Power-Ups zeichnen
        powerups.forEach(p => {
            ctx.fillStyle = p.type === 'weapon' ? '#f59e0b' : '#10b981';
            ctx.beginPath();
            ctx.arc(p.x + p.w / 2, p.y + p.h / 2, 11, 0, Math.PI * 2);
            ctx.fill();
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 1.5;
            ctx.stroke();
            ctx.fillStyle = '#0f172a';
            ctx.font = 'bold 12px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(p.type === 'weapon' ? 'W' : '+', p.x + p.w / 2, p.y + p.h / 2 + 4);
        });

        // Spieler-Laser
        bullets.forEach(b => {
            ctx.fillStyle = b.color || '#38bdf8';
            ctx.fillRect(b.x, b.y, b.w, b.h);
        });

        // Gegner-Laser
        enemyBullets.forEach(eb => {
            ctx.fillStyle = eb.color || '#ef4444';
            ctx.fillRect(eb.x, eb.y, eb.w, eb.h);
        });

        // Minions
        enemies.forEach(e => {
            ctx.fillStyle = e.type === 'striker' ? '#ef4444' : currentLvlCfg.minionColor;
            ctx.beginPath();
            ctx.moveTo(e.x + e.w / 2, e.y + e.h);
            ctx.lineTo(e.x, e.y);
            ctx.lineTo(e.x + e.w / 2, e.y + 6);
            ctx.lineTo(e.x + e.w, e.y);
            ctx.closePath();
            ctx.fill();
            ctx.fillStyle = '#fef08a';
            ctx.fillRect(e.x + e.w / 2 - 2, e.y + e.h / 2 - 2, 4, 4);
        });

        // Boss zeichnen
        if (levelState === 'boss' && activeBoss) {
            ctx.fillStyle = activeBoss.color;
            ctx.beginPath();
            ctx.moveTo(activeBoss.x, activeBoss.y);
            ctx.lineTo(activeBoss.x + activeBoss.w, activeBoss.y);
            ctx.lineTo(activeBoss.x + activeBoss.w - 16, activeBoss.y + activeBoss.h - 10);
            ctx.lineTo(activeBoss.x + activeBoss.w / 2, activeBoss.y + activeBoss.h);
            ctx.lineTo(activeBoss.x + 16, activeBoss.y + activeBoss.h - 10);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = activeBoss.phase === 2 ? '#ef4444' : '#38bdf8';
            ctx.fillRect(activeBoss.x + activeBoss.w / 2 - 10, activeBoss.y + 10, 20, 16);

            ctx.fillStyle = '#0f172a';
            ctx.fillRect(activeBoss.x + 14, activeBoss.y + activeBoss.h - 10, 10, 10);
            ctx.fillRect(activeBoss.x + activeBoss.w - 24, activeBoss.y + activeBoss.h - 10, 10, 10);

            // Boss-Lebensbalken
            const barW = 300;
            const barH = 12;
            const barX = width / 2 - barW / 2;
            const barY = 10;
            const hpRatio = Math.max(0, activeBoss.hp / activeBoss.maxHp);

            ctx.fillStyle = 'rgba(15, 23, 42, 0.85)';
            ctx.fillRect(barX - 6, barY - 4, barW + 12, barH + 24);
            ctx.fillStyle = '#334155';
            ctx.fillRect(barX, barY + 13, barW, barH);
            ctx.fillStyle = activeBoss.phase === 2 ? '#f59e0b' : '#ef4444';
            ctx.fillRect(barX, barY + 13, barW * hpRatio, barH);

            ctx.fillStyle = '#f8fafc';
            ctx.font = 'bold 11px sans-serif';
            ctx.textAlign = 'center';
            const pTag = activeBoss.phase === 2 ? ' [ENRAGE]' : '';
            ctx.fillText(activeBoss.title + pTag + ' [' + Math.ceil(activeBoss.hp) + '/' + activeBoss.maxHp + ']', width / 2, barY + 9);
        }

        // Spieler-Schiff
        if (player.invulnerableFrames % 6 < 3) {
            ctx.fillStyle = '#0284c7';
            ctx.beginPath();
            ctx.moveTo(player.x + player.w / 2, player.y);
            ctx.lineTo(player.x, player.y + player.h);
            ctx.lineTo(player.x + player.w / 2, player.y + player.h - 6);
            ctx.lineTo(player.x + player.w, player.y + player.h);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = (WEAPONS[player.weaponLevel] || WEAPONS[1]).color;
            ctx.fillRect(player.x + player.w / 2 - 4, player.y + 4, 8, player.h - 8);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(player.x + player.w / 2 - 2, player.y + 8, 4, 6);
            ctx.fillStyle = '#fbbf24';
            ctx.fillRect(player.x + player.w / 2 - 3, player.y + player.h - 2, 6, 4 + Math.random() * 5);
        }

        // HUD oben links
        ctx.fillStyle = 'rgba(2, 6, 23, 0.65)';
        ctx.fillRect(8, 8, 250, 62);
        ctx.fillStyle = '#f8fafc';
        ctx.font = 'bold 13px sans-serif';
        ctx.textAlign = 'left';
        ctx.fillText('LEVEL ' + currentLevel + '/' + TOTAL_LEVELS + ': ' + currentLvlCfg.name, 16, 25);
        ctx.font = '11.5px sans-serif';
        ctx.fillStyle = '#cbd5e1';
        ctx.fillText('PUNKTE: ' + score + ' | WAFFE: ' + WEAPONS[player.weaponLevel].name, 16, 43);
        if (levelState === 'waves') {
            ctx.fillStyle = '#94a3b8';
            ctx.fillText('ZIEL: ' + minionsKilled + '/' + currentLvlCfg.minionsNeeded + ' Gegner (Rand-Türme zerstörbar)', 16, 60);
        }

        // HUD oben rechts (Schilde)
        let hearts = '';
        for (let l = 0; l < player.lives; l++) hearts += '❤️ ';
        ctx.textAlign = 'right';
        ctx.fillStyle = '#f8fafc';
        ctx.font = 'bold 13px sans-serif';
        ctx.fillText('SCHILD: ' + hearts, width - 14, 25);

        // Banner
        if (levelState === 'boss_warning') {
            ctx.fillStyle = 'rgba(2, 6, 23, 0.72)';
            ctx.fillRect(0, height / 2 - 40, width, 80);
            ctx.fillStyle = (Date.now() % 400 < 200) ? '#ef4444' : '#fbbf24';
            ctx.font = 'bold 23px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('⚠️ ALARM: SEKTOR-BOSS NÄHERT SICH! ⚠️', width / 2, height / 2 + 8);
        }

        if (levelState === 'level_cleared') {
            ctx.fillStyle = 'rgba(2, 6, 23, 0.78)';
            ctx.fillRect(0, height / 2 - 45, width, 90);
            ctx.fillStyle = '#22c55e';
            ctx.font = 'bold 25px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('✓ LEVEL ' + currentLevel + ' BEFREIT!', width / 2, height / 2 - 5);
            ctx.fillStyle = '#38bdf8';
            ctx.font = '14px sans-serif';
            ctx.fillText('+1 Schild-Reparatur erhalten! Sprung in nächsten Sektor...', width / 2, height / 2 + 24);
        }

        if (gameOver) {
            ctx.fillStyle = 'rgba(2, 6, 23, 0.88)';
            ctx.fillRect(0, 0, width, height);
            ctx.fillStyle = '#ef4444';
            ctx.font = 'bold 30px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('MISSION FEHLGESCHLAGEN', width / 2, height / 2 - 25);
            ctx.fillStyle = '#f8fafc';
            ctx.font = '16px sans-serif';
            ctx.fillText('Erreicht: Level ' + currentLevel + '/' + TOTAL_LEVELS + ' (' + currentLvlCfg.name + ')', width / 2, height / 2 + 10);
            ctx.fillText('Endpunktestand: ' + score, width / 2, height / 2 + 36);
            ctx.fillStyle = '#38bdf8';
            ctx.font = '14px sans-serif';
            ctx.fillText('Klicke oder drücke LEERTASTE / ENTER für Neustart', width / 2, height / 2 + 72);
        }

        if (victory) {
            ctx.fillStyle = 'rgba(2, 6, 23, 0.92)';
            ctx.fillRect(0, 0, width, height);
            ctx.fillStyle = '#eab308';
            ctx.font = 'bold 34px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('🏆 GLÜCKWUNSCH! SIEG! 🏆', width / 2, height / 2 - 40);
            ctx.fillStyle = '#22c55e';
            ctx.font = 'bold 19px sans-serif';
            ctx.fillText('Alle 5 Level gemeistert & das Titan Mothership zerstört!', width / 2, height / 2 + 5);
            ctx.fillStyle = '#f8fafc';
            ctx.font = '16px sans-serif';
            ctx.fillText('Galaktischer Highscore: ' + score + ' Punkte', width / 2, height / 2 + 36);
            ctx.fillStyle = '#38bdf8';
            ctx.font = '14px sans-serif';
            ctx.fillText('Klicke oder drücke LEERTASTE / ENTER um erneut zu spielen', width / 2, height / 2 + 74);
        }
    }

    function loop() {
        update();
        draw();
        if (!gameOver && !victory) {
            rafId = requestAnimationFrame(loop);
        } else {
            draw();
        }
    }

    if (spawnTimer) clearInterval(spawnTimer);
    spawnTimer = setInterval(spawnMinion, 1020);
    rafId = requestAnimationFrame(loop);
}
