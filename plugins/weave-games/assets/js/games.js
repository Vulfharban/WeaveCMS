/**
 * ============================================================================
 * Weave Games – Space Orbit Arcade: 3 Level Campaign & Boss-Showdown
 * ============================================================================
 * Features:
 * - 3 Kampagnen-Level mit steigender Herausforderung:
 *   - Level 1: Sektor Alpha (Wellen + Boss: Viper Frigate)
 *   - Level 2: Sektor Beta  (Wellen + Boss: Dreadnought Cruiser)
 *   - Level 3: Sektor Omega (Wellen + Final Boss: Titan Mothership)
 * - Nach dem 3. Boss hat der Spieler gewonnen (Sieg-Screen, kein Endlosspiel)!
 * - Gegnerische Plasmageschosse bei Bosskämpfen.
 * - Boss-Lebensbalken im HUD, Wellenfortschritt und Schild-Leben (❤️❤️❤️).
 * - Direkte Cursor/Maus- und Touch-Steuerung sowie Tastatur (Pfeiltasten / A & D).
 * ============================================================================
 */

function initSpaceOrbitGame() {
    const intro = document.getElementById('spaceOrbitIntro');
    const canvas = document.getElementById('spaceOrbitCanvas');
    if (!canvas) return;

    if (intro) intro.style.display = 'none';
    canvas.style.display = 'block';

    const ctx = canvas.getContext('2d');
    let width = canvas.width = 800;
    let height = canvas.height = 360;

    // Kampagnen-Definition
    const LEVELS = {
        1: {
            name: "Sektor Alpha (Orbit)",
            minionsNeeded: 10,
            bgColor: "#020617",
            boss: {
                name: "Viper Frigate",
                title: "BOSS: VIPER FRIGATE",
                maxHp: 16,
                w: 90,
                h: 40,
                speed: 2.2,
                shootCooldown: 65,
                color: "#e11d48",
                bulletSpeed: 4.5
            }
        },
        2: {
            name: "Sektor Beta (Asteroidenfeld)",
            minionsNeeded: 14,
            bgColor: "#09090b",
            boss: {
                name: "Dreadnought Cruiser",
                title: "BOSS: DREADNOUGHT CRUISER",
                maxHp: 28,
                w: 130,
                h: 48,
                speed: 2.5,
                shootCooldown: 50,
                color: "#9333ea",
                bulletSpeed: 5.2
            }
        },
        3: {
            name: "Sektor Omega (Mutterschiff-Basis)",
            minionsNeeded: 18,
            bgColor: "#0f051d",
            boss: {
                name: "Titan Mothership",
                title: "FINAL BOSS: TITAN MOTHERSHIP",
                maxHp: 45,
                w: 180,
                h: 58,
                speed: 2.0,
                shootCooldown: 40,
                color: "#f59e0b",
                bulletSpeed: 5.8
            }
        }
    };

    // Spieler-Status
    let player = {
        x: width / 2 - 18,
        y: height - 45,
        w: 36,
        h: 24,
        speed: 9.5,
        targetX: width / 2 - 18,
        lives: 3,
        invulnerableFrames: 0
    };

    // Spiel-Zustand
    let currentLevel = 1;
    let levelState = 'waves'; // 'waves', 'boss_warning', 'boss', 'level_cleared', 'victory'
    let minionsKilled = 0;
    let stateTimer = 0;
    let activeBoss = null;

    let bullets = [];
    let enemyBullets = [];
    let enemies = [];
    let particles = [];
    let score = 0;
    let gameOver = false;
    let victory = false;
    let keys = {};
    let isMouseControlled = false;

    // Harmonischer Wellenfokus
    let waveFocusX = width / 2;
    let waveDirection = 1;
    let spawnTimer = null;

    // Maus- & Touch-Steuerung
    function setTargetFromPointer(clientX) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const canvasX = (clientX - rect.left) * scaleX;
        player.targetX = Math.max(10, Math.min(width - player.w - 10, canvasX - player.w / 2));
        isMouseControlled = true;
    }

    canvas.addEventListener('mousemove', (e) => {
        setTargetFromPointer(e.clientX);
    });

    canvas.addEventListener('mousedown', (e) => {
        if (!gameOver && !victory) {
            shoot();
        } else {
            restartCampaign();
        }
    });

    canvas.addEventListener('touchmove', (e) => {
        if (e.touches && e.touches[0]) {
            setTargetFromPointer(e.touches[0].clientX);
            e.preventDefault();
        }
    }, { passive: false });

    canvas.addEventListener('touchstart', (e) => {
        if (e.touches && e.touches[0]) {
            setTargetFromPointer(e.touches[0].clientX);
            if (!gameOver && !victory) shoot();
            else restartCampaign();
            e.preventDefault();
        }
    }, { passive: false });

    // Tastatur-Steuerung
    window.addEventListener('keydown', (e) => {
        keys[e.key] = true;
        isMouseControlled = false;

        if ((e.key === ' ' || e.key === 'Spacebar') && !gameOver && !victory) {
            shoot();
            e.preventDefault();
        }
        if ((gameOver || victory) && (e.key === 'Enter' || e.key === ' ')) {
            restartCampaign();
            e.preventDefault();
        }
    });

    window.addEventListener('keyup', (e) => {
        keys[e.key] = false;
    });

    function shoot() {
        if (gameOver || victory) return;
        // Doppelter Laser
        bullets.push({ x: player.x + 6, y: player.y, w: 4, h: 12, speed: 10 });
        bullets.push({ x: player.x + player.w - 10, y: player.y, w: 4, h: 12, speed: 10 });
    }

    function createExplosion(x, y, color = '#f97316', count = 12) {
        for (let i = 0; i < count; i++) {
            const angle = Math.random() * Math.PI * 2;
            const spd = Math.random() * 3.5 + 1;
            particles.push({
                x: x,
                y: y,
                vx: Math.cos(angle) * spd,
                vy: Math.sin(angle) * spd,
                life: 25,
                maxLife: 25,
                color: color
            });
        }
    }

    function spawnMinion() {
        if (gameOver || victory || levelState !== 'waves') return;

        waveFocusX += (Math.random() * 60 + 35) * waveDirection;
        if (waveFocusX > width - 160) {
            waveFocusX = width - 160;
            waveDirection = -1;
        } else if (waveFocusX < 160) {
            waveFocusX = 160;
            waveDirection = 1;
        }

        const pattern = Math.random();
        if (pattern < 0.6) {
            const sx = Math.max(25, Math.min(width - 55, waveFocusX + (Math.random() * 120 - 60)));
            enemies.push({
                x: sx,
                y: -25,
                w: 28,
                h: 22,
                speed: 1.8 + (currentLevel * 0.25),
                type: 'scout'
            });
        } else {
            const base = Math.max(40, Math.min(width - 100, waveFocusX));
            enemies.push({ x: base - 25, y: -25, w: 26, h: 22, speed: 1.9, type: 'wingman' });
            enemies.push({ x: base + 25, y: -25, w: 26, h: 22, speed: 1.9, type: 'wingman' });
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
            y: 40,
            w: cfg.w,
            h: cfg.h,
            vx: cfg.speed,
            shootCooldown: cfg.shootCooldown,
            timer: 0,
            bulletSpeed: cfg.bulletSpeed,
            color: cfg.color
        };
    }

    function advanceToNextLevel() {
        if (currentLevel < 3) {
            currentLevel++;
            levelState = 'waves';
            minionsKilled = 0;
            // Belohnung: 1 Schild-Leben auffrischen
            if (player.lives < 3) player.lives++;
            createExplosion(player.x + player.w / 2, player.y + player.h / 2, '#22c55e', 24);
        } else {
            // Alle 3 Level geschafft -> Sieg!
            victory = true;
            levelState = 'victory';
            if (spawnTimer) clearInterval(spawnTimer);
            createExplosion(width / 2, height / 2, '#eab308', 50);
        }
    }

    function restartCampaign() {
        currentLevel = 1;
        levelState = 'waves';
        minionsKilled = 0;
        stateTimer = 0;
        activeBoss = null;

        player.x = width / 2 - 18;
        player.targetX = width / 2 - 18;
        player.lives = 3;
        player.invulnerableFrames = 0;

        bullets = [];
        enemyBullets = [];
        enemies = [];
        particles = [];
        score = 0;
        gameOver = false;
        victory = false;
        waveFocusX = width / 2;
        waveDirection = 1;

        if (spawnTimer) clearInterval(spawnTimer);
        spawnTimer = setInterval(spawnMinion, 1100);
        requestAnimationFrame(loop);
    }

    function update() {
        if (gameOver || victory) return;

        const currentLvlCfg = LEVELS[currentLevel];

        if (player.invulnerableFrames > 0) {
            player.invulnerableFrames--;
        }

        // Steuerung
        if (isMouseControlled) {
            player.x += (player.targetX - player.x) * 0.25;
        } else {
            if ((keys['ArrowLeft'] || keys['a'] || keys['A']) && player.x > 10) {
                player.x -= player.speed;
                player.targetX = player.x;
            }
            if ((keys['ArrowRight'] || keys['d'] || keys['D']) && player.x < width - player.w - 10) {
                player.x += player.speed;
                player.targetX = player.x;
            }
        }

        // Spieler-Geschosse
        for (let bi = bullets.length - 1; bi >= 0; bi--) {
            const b = bullets[bi];
            b.y -= b.speed;
            if (b.y < -15) {
                bullets.splice(bi, 1);
            }
        }

        // Gegnerische Plasmageschosse (vom Boss)
        for (let ebi = enemyBullets.length - 1; ebi >= 0; ebi--) {
            const eb = enemyBullets[ebi];
            eb.x += (eb.vx || 0);
            eb.y += eb.vy;

            // Treffer am Spieler
            if (player.invulnerableFrames === 0 &&
                eb.x < player.x + player.w && eb.x + eb.w > player.x &&
                eb.y < player.y + player.h && eb.y + eb.h > player.y) {
                
                player.lives--;
                createExplosion(player.x + player.w / 2, player.y + player.h / 2, '#38bdf8', 18);
                enemyBullets.splice(ebi, 1);

                if (player.lives <= 0) {
                    gameOver = true;
                    if (spawnTimer) clearInterval(spawnTimer);
                    break;
                } else {
                    player.invulnerableFrames = 75;
                }
                continue;
            }

            if (eb.y > height + 20 || eb.x < -20 || eb.x > width + 20) {
                enemyBullets.splice(ebi, 1);
            }
        }

        // Normale Minion-Wellen Logik
        if (levelState === 'waves') {
            for (let ei = enemies.length - 1; ei >= 0; ei--) {
                const e = enemies[ei];
                e.y += e.speed;

                // Minion rammt Spieler
                if (player.invulnerableFrames === 0 &&
                    e.x < player.x + player.w && e.x + e.w > player.x &&
                    e.y < player.y + player.h && e.y + e.h > player.y) {
                    
                    player.lives--;
                    createExplosion(player.x + player.w / 2, player.y + player.h / 2, '#38bdf8', 18);
                    enemies.splice(ei, 1);

                    if (player.lives <= 0) {
                        gameOver = true;
                        if (spawnTimer) clearInterval(spawnTimer);
                        break;
                    } else {
                        player.invulnerableFrames = 75;
                    }
                    continue;
                }

                // Minion von Laser getroffen
                let enemyHit = false;
                for (let bi = bullets.length - 1; bi >= 0; bi--) {
                    const b = bullets[bi];
                    if (b.x < e.x + e.w && b.x + b.w > e.x &&
                        b.y < e.y + e.h && b.y + b.h > e.y) {
                        
                        createExplosion(e.x + e.w / 2, e.y + e.h / 2, '#f97316', 12);
                        enemies.splice(ei, 1);
                        bullets.splice(bi, 1);
                        score += 100;
                        minionsKilled++;
                        enemyHit = true;
                        break;
                    }
                }

                if (!enemyHit && e.y > height + 25) {
                    enemies.splice(ei, 1);
                }
            }

            // Genug Minions besiegt? -> Boss kündigt sich an!
            if (minionsKilled >= currentLvlCfg.minionsNeeded && enemies.length === 0) {
                levelState = 'boss_warning';
                stateTimer = 90; // ~1.5 Sekunden Warnung
            }
        }

        // Boss-Warnung läuft ab
        if (levelState === 'boss_warning') {
            stateTimer--;
            if (stateTimer <= 0) {
                startBoss(currentLvlCfg.boss);
            }
        }

        // Boss-Kampf
        if (levelState === 'boss' && activeBoss) {
            // Boss hin und her bewegen
            activeBoss.x += activeBoss.vx;
            if (activeBoss.x < 20) {
                activeBoss.x = 20;
                activeBoss.vx = Math.abs(activeBoss.vx);
            } else if (activeBoss.x + activeBoss.w > width - 20) {
                activeBoss.x = width - 20 - activeBoss.w;
                activeBoss.vx = -Math.abs(activeBoss.vx);
            }

            // Boss Angriffe
            activeBoss.timer++;
            if (activeBoss.timer >= activeBoss.shootCooldown) {
                activeBoss.timer = 0;
                const bx = activeBoss.x + activeBoss.w / 2;
                const by = activeBoss.y + activeBoss.h;

                if (currentLevel === 1) {
                    // Boss 1: Einzelschuss
                    enemyBullets.push({ x: bx - 4, y: by, w: 8, h: 14, vx: 0, vy: activeBoss.bulletSpeed, color: '#ef4444' });
                } else if (currentLevel === 2) {
                    // Boss 2: Doppelschuss
                    enemyBullets.push({ x: activeBoss.x + 15, y: by, w: 8, h: 14, vx: 0, vy: activeBoss.bulletSpeed, color: '#a855f7' });
                    enemyBullets.push({ x: activeBoss.x + activeBoss.w - 23, y: by, w: 8, h: 14, vx: 0, vy: activeBoss.bulletSpeed, color: '#a855f7' });
                } else {
                    // Boss 3 (Final Boss): Fächer-Schuss (3 Geschosse)
                    enemyBullets.push({ x: bx - 4, y: by, w: 9, h: 16, vx: -1.6, vy: activeBoss.bulletSpeed, color: '#fbbf24' });
                    enemyBullets.push({ x: bx - 4, y: by, w: 9, h: 16, vx: 0, vy: activeBoss.bulletSpeed + 0.6, color: '#ef4444' });
                    enemyBullets.push({ x: bx - 4, y: by, w: 9, h: 16, vx: 1.6, vy: activeBoss.bulletSpeed, color: '#fbbf24' });
                }
            }

            // Kollision: Spieler-Laser trifft Boss
            for (let bi = bullets.length - 1; bi >= 0; bi--) {
                const b = bullets[bi];
                if (b.x < activeBoss.x + activeBoss.w && b.x + b.w > activeBoss.x &&
                    b.y < activeBoss.y + activeBoss.h && b.y + b.h > activeBoss.y) {
                    
                    bullets.splice(bi, 1);
                    activeBoss.hp--;
                    createExplosion(b.x, b.y, '#f59e0b', 6);
                    score += 50;

                    // Boss besiegt!
                    if (activeBoss.hp <= 0) {
                        createExplosion(activeBoss.x + activeBoss.w / 2, activeBoss.y + activeBoss.h / 2, '#ef4444', 35);
                        score += (currentLevel * 1000);
                        activeBoss = null;
                        levelState = 'level_cleared';
                        stateTimer = 110; // ~1.8 Sekunden Triumph-Pause
                        break;
                    }
                }
            }
        }

        // Level geschafft Pause läuft ab
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
            if (p.life <= 0) {
                particles.splice(pi, 1);
            }
        }
    }

    function draw() {
        const currentLvlCfg = LEVELS[currentLevel] || LEVELS[1];

        // Hintergrund
        ctx.fillStyle = currentLvlCfg.bgColor;
        ctx.fillRect(0, 0, width, height);

        // Sternenfeld
        ctx.fillStyle = 'rgba(255,255,255,0.4)';
        for (let i = 0; i < 35; i++) {
            const sx = (i * 97) % width;
            const sy = (i * 53 + Date.now() / 40) % height;
            ctx.fillRect(sx, sy, 2, 2);
        }

        // Atmosphären-Lichtkegel
        const waveGrad = ctx.createRadialGradient(waveFocusX, 0, 5, waveFocusX, 0, 140);
        waveGrad.addColorStop(0, 'rgba(56, 189, 248, 0.12)');
        waveGrad.addColorStop(1, 'transparent');
        ctx.fillStyle = waveGrad;
        ctx.fillRect(0, 0, width, 60);

        // Partikel
        particles.forEach(p => {
            const alpha = p.life / p.maxLife;
            ctx.fillStyle = p.color;
            ctx.globalAlpha = alpha;
            ctx.fillRect(p.x, p.y, 3, 3);
            ctx.globalAlpha = 1.0;
        });

        // Spieler-Laser
        ctx.fillStyle = '#38bdf8';
        bullets.forEach(b => ctx.fillRect(b.x, b.y, b.w, b.h));

        // Gegner-Laser (rot/lila Plasma)
        enemyBullets.forEach(eb => {
            ctx.fillStyle = eb.color || '#ef4444';
            ctx.fillRect(eb.x, eb.y, eb.w, eb.h);
        });

        // Minions zeichnen
        enemies.forEach(e => {
            ctx.fillStyle = (currentLevel === 2) ? '#c084fc' : ((currentLevel === 3) ? '#f43f5e' : '#f97316');
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
            
            // Haupt-Rumpf des Bosses
            ctx.beginPath();
            ctx.moveTo(activeBoss.x, activeBoss.y);
            ctx.lineTo(activeBoss.x + activeBoss.w, activeBoss.y);
            ctx.lineTo(activeBoss.x + activeBoss.w - 15, activeBoss.y + activeBoss.h - 10);
            ctx.lineTo(activeBoss.x + activeBoss.w / 2, activeBoss.y + activeBoss.h);
            ctx.lineTo(activeBoss.x + 15, activeBoss.y + activeBoss.h - 10);
            ctx.closePath();
            ctx.fill();

            // Glühender Boss-Kern
            const coreColor = (currentLevel === 3) ? '#f59e0b' : '#38bdf8';
            ctx.fillStyle = coreColor;
            ctx.fillRect(activeBoss.x + activeBoss.w / 2 - 8, activeBoss.y + 8, 16, 14);

            // Boss Geschütztürme
            ctx.fillStyle = '#1e293b';
            ctx.fillRect(activeBoss.x + 12, activeBoss.y + activeBoss.h - 8, 8, 8);
            ctx.fillRect(activeBoss.x + activeBoss.w - 20, activeBoss.y + activeBoss.h - 8, 8, 8);

            // Boss Lebensbalken (HUD oben Mitte)
            const barW = 280;
            const barH = 12;
            const barX = width / 2 - barW / 2;
            const barY = 12;
            const hpRatio = Math.max(0, activeBoss.hp / activeBoss.maxHp);

            ctx.fillStyle = 'rgba(15, 23, 42, 0.8)';
            ctx.fillRect(barX - 4, barY - 4, barW + 8, barH + 22);

            ctx.fillStyle = '#334155';
            ctx.fillRect(barX, barY + 12, barW, barH);

            ctx.fillStyle = (hpRatio > 0.4) ? '#ef4444' : '#f59e0b';
            ctx.fillRect(barX, barY + 12, barW * hpRatio, barH);

            ctx.fillStyle = '#f8fafc';
            ctx.font = 'bold 11px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(activeBoss.title + ' [' + activeBoss.hp + '/' + activeBoss.maxHp + ']', width / 2, barY + 8);
        }

        // Spieler zeichnen
        if (player.invulnerableFrames % 6 < 3) {
            ctx.fillStyle = '#0284c7';
            ctx.beginPath();
            ctx.moveTo(player.x + player.w / 2, player.y);
            ctx.lineTo(player.x, player.y + player.h);
            ctx.lineTo(player.x + player.w / 2, player.y + player.h - 6);
            ctx.lineTo(player.x + player.w, player.y + player.h);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#38bdf8';
            ctx.fillRect(player.x + player.w / 2 - 4, player.y + 4, 8, player.h - 8);

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(player.x + player.w / 2 - 2, player.y + 8, 4, 6);

            ctx.fillStyle = '#fbbf24';
            ctx.fillRect(player.x + player.w / 2 - 3, player.y + player.h - 2, 6, 4 + Math.random() * 4);
        }

        // HUD: Level, Punkte & Schilde
        ctx.fillStyle = '#f8fafc';
        ctx.font = 'bold 14px sans-serif';
        ctx.textAlign = 'left';
        ctx.fillText('LEVEL ' + currentLevel + '/3: ' + currentLvlCfg.name, 16, 26);
        ctx.font = '12px sans-serif';
        ctx.fillStyle = '#94a3b8';
        ctx.fillText('PUNKTE: ' + score, 16, 44);

        if (levelState === 'waves') {
            ctx.fillText('FORTSCHRITT: ' + minionsKilled + '/' + currentLvlCfg.minionsNeeded + ' Minions', 16, 60);
        }

        // Leben anzeigen
        let hearts = '';
        for (let l = 0; l < player.lives; l++) hearts += '❤️ ';
        ctx.textAlign = 'right';
        ctx.fillStyle = '#f8fafc';
        ctx.font = 'bold 14px sans-serif';
        ctx.fillText('SCHILD: ' + hearts, width - 16, 26);

        // Banner: Boss-Warnung
        if (levelState === 'boss_warning') {
            ctx.fillStyle = 'rgba(2, 6, 23, 0.6)';
            ctx.fillRect(0, height / 2 - 40, width, 80);

            ctx.fillStyle = (Date.now() % 400 < 200) ? '#ef4444' : '#fbbf24';
            ctx.font = 'bold 24px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('⚠️ ALARM: SEKTOR-BOSS NÄHERT SICH! ⚠️', width / 2, height / 2 + 8);
        }

        // Banner: Level geschafft
        if (levelState === 'level_cleared') {
            ctx.fillStyle = 'rgba(2, 6, 23, 0.7)';
            ctx.fillRect(0, height / 2 - 45, width, 90);

            ctx.fillStyle = '#22c55e';
            ctx.font = 'bold 26px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('✓ SEKTOR ' + currentLevel + ' ERFOLGREICH BEFREIT!', width / 2, height / 2 - 5);

            ctx.fillStyle = '#38bdf8';
            ctx.font = '14px sans-serif';
            ctx.fillText('+1 Schild-Reparatur erhalten! Bereite Sprung vor...', width / 2, height / 2 + 25);
        }

        // Game Over Screen
        if (gameOver) {
            ctx.fillStyle = 'rgba(2, 6, 23, 0.88)';
            ctx.fillRect(0, 0, width, height);

            ctx.fillStyle = '#ef4444';
            ctx.font = 'bold 30px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('MISSION FEHLGESCHLAGEN', width / 2, height / 2 - 25);

            ctx.fillStyle = '#f8fafc';
            ctx.font = '16px sans-serif';
            ctx.fillText('Erreicht in Level ' + currentLevel + ' (' + currentLvlCfg.name + ')', width / 2, height / 2 + 10);
            ctx.fillText('Endpunktestand: ' + score, width / 2, height / 2 + 35);

            ctx.fillStyle = '#38bdf8';
            ctx.font = '14px sans-serif';
            ctx.fillText('Klicke oder drücke LEERTASTE / ENTER für Neustart', width / 2, height / 2 + 70);
        }

        // SIEG SCREEN (Nach dem 3. Boss gewonnen!)
        if (victory) {
            ctx.fillStyle = 'rgba(2, 6, 23, 0.92)';
            ctx.fillRect(0, 0, width, height);

            ctx.fillStyle = '#eab308';
            ctx.font = 'bold 34px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('🏆 GLÜCKWUNSCH! SIEG! 🏆', width / 2, height / 2 - 40);

            ctx.fillStyle = '#22c55e';
            ctx.font = 'bold 20px sans-serif';
            ctx.fillText('Alle 3 Level gemeistert & das Titan Mutterschiff zerstört!', width / 2, height / 2 + 5);

            ctx.fillStyle = '#f8fafc';
            ctx.font = '16px sans-serif';
            ctx.fillText('Galaktischer Highscore: ' + score + ' Punkte', width / 2, height / 2 + 35);

            ctx.fillStyle = '#38bdf8';
            ctx.font = '14px sans-serif';
            ctx.fillText('Klicke oder drücke LEERTASTE / ENTER um erneut zu spielen', width / 2, height / 2 + 75);
        }
    }

    function loop() {
        update();
        draw();
        if (!gameOver && !victory) {
            requestAnimationFrame(loop);
        } else {
            // Einmal fertig zeichnen
            draw();
        }
    }

    if (spawnTimer) clearInterval(spawnTimer);
    spawnTimer = setInterval(spawnMinion, 1100);
    requestAnimationFrame(loop);
}
