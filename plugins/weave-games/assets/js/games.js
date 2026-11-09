/**
 * ============================================================================
 * Weave Games – Interaktives Skript & Space Orbit Canvas Arcade Game
 * ============================================================================
 * Features:
 * - Wellenbasiertes Spawning: Gegner formieren sich in harmonischen Wellen
 *   und springen nicht erratisch von ganz links nach ganz rechts.
 * - Cursor- & Maus-Steuerung: Raumschiff folgt flüssig dem Mauszeiger / Touch.
 * - Tastatur-Steuerung: Pfeiltasten & A/D mit erhöhter Reaktionsgeschwindigkeit.
 * - 3 Schild-Leben mit Unverwundbarkeits-Blinken nach Treffer.
 * - Partikeleffekte bei Zerstörung & Highscore-Tracking.
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

    let bullets = [];
    let enemies = [];
    let particles = [];
    let score = 0;
    let waveNumber = 1;
    let gameOver = false;
    let keys = {};
    let isMouseControlled = false;

    // Harmonischer Wellenfokus (wandert kontinuierlich und sanft über den Bildschirm)
    let waveFocusX = width / 2;
    let waveDirection = 1;
    let spawnTimer = null;

    // Maus- und Touch-Steuerung
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
        if (!gameOver) {
            shoot();
        } else {
            restartGame();
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
            if (!gameOver) shoot();
            else restartGame();
            e.preventDefault();
        }
    }, { passive: false });

    // Tastatur-Steuerung
    window.addEventListener('keydown', (e) => {
        keys[e.key] = true;
        isMouseControlled = false;

        if ((e.key === ' ' || e.key === 'Spacebar') && !gameOver) {
            shoot();
            e.preventDefault();
        }
        if (gameOver && (e.key === 'Enter' || e.key === ' ')) {
            restartGame();
            e.preventDefault();
        }
    });

    window.addEventListener('keyup', (e) => {
        keys[e.key] = false;
    });

    function shoot() {
        if (gameOver) return;
        // Doppelter Laserstrahl
        bullets.push({ x: player.x + 6, y: player.y, w: 4, h: 12, speed: 10 });
        bullets.push({ x: player.x + player.w - 10, y: player.y, w: 4, h: 12, speed: 10 });
    }

    function createExplosion(x, y, color = '#f97316', count = 12) {
        for (let i = 0; i < count; i++) {
            const angle = Math.random() * Math.PI * 2;
            const spd = Math.random() * 3 + 1;
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

    // Wellengesteuertes Spawning: Keine Sprünge über 800px!
    function spawnWave() {
        if (gameOver) return;

        // Der Schwerpunkt wandert stufenweise um max. 50-90 Pixel pro Takt
        waveFocusX += (Math.random() * 60 + 35) * waveDirection;
        if (waveFocusX > width - 160) {
            waveFocusX = width - 160;
            waveDirection = -1;
        } else if (waveFocusX < 160) {
            waveFocusX = 160;
            waveDirection = 1;
        }

        // Entscheide: Einzelflieger oder kleines 2er/3er Geschwader in direkter Nachbarschaft
        const pattern = Math.random();
        
        if (pattern < 0.6) {
            // Einzelschiff nahe dem Wellenfokus (±60px)
            const sx = Math.max(25, Math.min(width - 55, waveFocusX + (Math.random() * 120 - 60)));
            enemies.push({
                x: sx,
                y: -25,
                w: 28,
                h: 22,
                speed: 1.8 + Math.random() * 0.7,
                type: 'scout'
            });
        } else if (pattern < 0.85) {
            // 2er Patrouille nebeneinander
            const base = Math.max(40, Math.min(width - 100, waveFocusX));
            enemies.push({ x: base - 25, y: -25, w: 26, h: 22, speed: 1.8, type: 'wingman' });
            enemies.push({ x: base + 25, y: -25, w: 26, h: 22, speed: 1.8, type: 'wingman' });
        } else {
            // 3er V-Formation
            const center = Math.max(50, Math.min(width - 110, waveFocusX));
            enemies.push({ x: center, y: -45, w: 30, h: 24, speed: 2.0, type: 'leader' });
            enemies.push({ x: center - 35, y: -20, w: 24, h: 20, speed: 2.0, type: 'wingman' });
            enemies.push({ x: center + 35, y: -20, w: 24, h: 20, speed: 2.0, type: 'wingman' });
        }
    }

    function startSpawner() {
        if (spawnTimer) clearInterval(spawnTimer);
        spawnTimer = setInterval(spawnWave, 1100);
    }

    function restartGame() {
        player.x = width / 2 - 18;
        player.targetX = width / 2 - 18;
        player.lives = 3;
        player.invulnerableFrames = 0;
        bullets = [];
        enemies = [];
        particles = [];
        score = 0;
        gameOver = false;
        waveFocusX = width / 2;
        waveDirection = 1;
        startSpawner();
        requestAnimationFrame(loop);
    }

    function update() {
        if (gameOver) return;

        if (player.invulnerableFrames > 0) {
            player.invulnerableFrames--;
        }

        // Steuerung verarbeiten
        if (isMouseControlled) {
            // Weiches Nachziehen zum Mauszeiger
            player.x += (player.targetX - player.x) * 0.25;
        } else {
            // Tastatur (Pfeiltasten oder A / D)
            if ((keys['ArrowLeft'] || keys['a'] || keys['A']) && player.x > 10) {
                player.x -= player.speed;
                player.targetX = player.x;
            }
            if ((keys['ArrowRight'] || keys['d'] || keys['D']) && player.x < width - player.w - 10) {
                player.x += player.speed;
                player.targetX = player.x;
            }
        }

        // Geschosse bewegen
        for (let bi = bullets.length - 1; bi >= 0; bi--) {
            const b = bullets[bi];
            b.y -= b.speed;
            if (b.y < -15) {
                bullets.splice(bi, 1);
            }
        }

        // Gegner bewegen & Kollisionen
        for (let ei = enemies.length - 1; ei >= 0; ei--) {
            const e = enemies[ei];
            e.y += e.speed;

            // Treffer am Spieler
            if (player.invulnerableFrames === 0 &&
                e.x < player.x + player.w && e.x + e.w > player.x &&
                e.y < player.y + player.h && e.y + e.h > player.y) {
                
                player.lives--;
                createExplosion(player.x + player.w / 2, player.y + player.h / 2, '#38bdf8', 20);
                enemies.splice(ei, 1);

                if (player.lives <= 0) {
                    gameOver = true;
                    clearInterval(spawnTimer);
                    break;
                } else {
                    player.invulnerableFrames = 75; // ~1.2 Sekunden Schutzzeit
                }
                continue;
            }

            // Treffer durch Geschosse
            let enemyHit = false;
            for (let bi = bullets.length - 1; bi >= 0; bi--) {
                const b = bullets[bi];
                if (b.x < e.x + e.w && b.x + b.w > e.x &&
                    b.y < e.y + e.h && b.y + b.h > e.y) {
                    
                    createExplosion(e.x + e.w / 2, e.y + e.h / 2, '#f97316', 14);
                    enemies.splice(ei, 1);
                    bullets.splice(bi, 1);
                    score += (e.type === 'leader') ? 250 : 100;
                    enemyHit = true;
                    break;
                }
            }

            if (!enemyHit && e.y > height + 25) {
                enemies.splice(ei, 1);
            }
        }

        // Partikel aktualisieren
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
        // Weltraum-Hintergrund
        ctx.fillStyle = '#020617';
        ctx.fillRect(0, 0, width, height);

        // Sternenfeld
        ctx.fillStyle = 'rgba(255,255,255,0.45)';
        for (let i = 0; i < 35; i++) {
            const sx = (i * 97) % width;
            const sy = (i * 53 + Date.now() / 40) % height;
            ctx.fillRect(sx, sy, 2, 2);
        }

        // Sanfte Wellen-Indikator-Linie am oberen Rand (Atmosphäre)
        const waveGrad = ctx.createRadialGradient(waveFocusX, 0, 5, waveFocusX, 0, 140);
        waveGrad.addColorStop(0, 'rgba(56, 189, 248, 0.12)');
        waveGrad.addColorStop(1, 'transparent');
        ctx.fillStyle = waveGrad;
        ctx.fillRect(0, 0, width, 60);

        // Partikel zeichnen
        particles.forEach(p => {
            const alpha = p.life / p.maxLife;
            ctx.fillStyle = p.color;
            ctx.globalAlpha = alpha;
            ctx.fillRect(p.x, p.y, 3, 3);
            ctx.globalAlpha = 1.0;
        });

        // Geschosse zeichnen (Cyan glow)
        ctx.fillStyle = '#38bdf8';
        bullets.forEach(b => {
            ctx.fillRect(b.x, b.y, b.w, b.h);
        });

        // Gegner zeichnen (Raumschiffe mit Flügeln)
        enemies.forEach(e => {
            ctx.fillStyle = (e.type === 'leader') ? '#e11d48' : '#f97316';
            ctx.beginPath();
            ctx.moveTo(e.x + e.w / 2, e.y + e.h);
            ctx.lineTo(e.x, e.y);
            ctx.lineTo(e.x + e.w / 2, e.y + 6);
            ctx.lineTo(e.x + e.w, e.y);
            ctx.closePath();
            ctx.fill();

            // Cockpit
            ctx.fillStyle = '#fef08a';
            ctx.fillRect(e.x + e.w / 2 - 2, e.y + e.h / 2 - 2, 4, 4);
        });

        // Spieler zeichnen (mit Blink-Effekt bei Treffer)
        if (player.invulnerableFrames % 6 < 3) {
            ctx.fillStyle = '#0284c7';
            // Flügel
            ctx.beginPath();
            ctx.moveTo(player.x + player.w / 2, player.y);
            ctx.lineTo(player.x, player.y + player.h);
            ctx.lineTo(player.x + player.w / 2, player.y + player.h - 6);
            ctx.lineTo(player.x + player.w, player.y + player.h);
            ctx.closePath();
            ctx.fill();

            // Rumpf
            ctx.fillStyle = '#38bdf8';
            ctx.fillRect(player.x + player.w / 2 - 4, player.y + 4, 8, player.h - 8);

            // Cockpit
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(player.x + player.w / 2 - 2, player.y + 8, 4, 6);

            // Triebwerks-Glühen
            ctx.fillStyle = '#fbbf24';
            ctx.fillRect(player.x + player.w / 2 - 3, player.y + player.h - 2, 6, 4 + Math.random() * 4);
        }

        // HUD: Score & Leben
        ctx.fillStyle = '#f8fafc';
        ctx.font = 'bold 15px sans-serif';
        ctx.textAlign = 'left';
        ctx.fillText('PUNKTE: ' + score, 18, 28);

        // Leben anzeigen (Herzen / Schilde)
        let hearts = '';
        for (let l = 0; l < player.lives; l++) hearts += '❤️ ';
        ctx.textAlign = 'right';
        ctx.fillText('SCHILD: ' + hearts, width - 18, 28);

        // Game Over Screen
        if (gameOver) {
            ctx.fillStyle = 'rgba(2, 6, 23, 0.85)';
            ctx.fillRect(0, 0, width, height);

            ctx.fillStyle = '#ef4444';
            ctx.font = 'bold 32px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('MISSION FEHLGESCHLAGEN', width / 2, height / 2 - 20);

            ctx.fillStyle = '#f8fafc';
            ctx.font = '18px sans-serif';
            ctx.fillText('Erreichter Punktestand: ' + score, width / 2, height / 2 + 15);

            ctx.fillStyle = '#38bdf8';
            ctx.font = '14px sans-serif';
            ctx.fillText('Klicke oder drücke LEERTASTE / ENTER für Neustart', width / 2, height / 2 + 55);
        }
    }

    function loop() {
        update();
        draw();
        if (!gameOver) {
            requestAnimationFrame(loop);
        }
    }

    startSpawner();
    requestAnimationFrame(loop);
}
