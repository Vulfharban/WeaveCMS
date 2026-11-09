/**
 * Weave Games – Interaktives Skript & Space Orbit Canvas Mini-Game
 */

function initSpaceOrbitGame() {
    const intro = document.getElementById('spaceOrbitIntro');
    const canvas = document.getElementById('spaceOrbitCanvas');
    if (!canvas) return;

    if (intro) intro.style.display = 'none';
    canvas.style.display = 'block';

    const ctx = canvas.getContext('2d');
    let width = canvas.width = 800;
    let height = canvas.height = 340;

    let player = { x: width / 2 - 15, y: height - 40, w: 30, h: 20, speed: 6 };
    let bullets = [];
    let enemies = [];
    let score = 0;
    let gameOver = false;
    let keys = {};

    window.addEventListener('keydown', (e) => {
        keys[e.key] = true;
        if (e.key === ' ' && !gameOver) {
            bullets.push({ x: player.x + player.w / 2 - 2, y: player.y, w: 4, h: 10, speed: 8 });
            e.preventDefault();
        }
    });

    window.addEventListener('keyup', (e) => {
        keys[e.key] = false;
    });

    // Enemy spawner
    function spawnEnemy() {
        if (!gameOver) {
            enemies.push({
                x: Math.random() * (width - 30),
                y: -20,
                w: 24,
                h: 20,
                speed: 1.5 + Math.random() * 2
            });
        }
    }
    const spawnTimer = setInterval(spawnEnemy, 1200);

    function update() {
        if (gameOver) return;

        // Player move
        if (keys['ArrowLeft'] && player.x > 0) player.x -= player.speed;
        if (keys['ArrowRight'] && player.x < width - player.w) player.x += player.speed;

        // Bullets move
        bullets.forEach((b, bi) => {
            b.y -= b.speed;
            if (b.y < -10) bullets.splice(bi, 1);
        });

        // Enemies move & collide
        enemies.forEach((e, ei) => {
            e.y += e.speed;

            // Player collision
            if (e.x < player.x + player.w && e.x + e.w > player.x && e.y < player.y + player.h && e.y + e.h > player.y) {
                gameOver = true;
                clearInterval(spawnTimer);
            }

            // Bullet collision
            bullets.forEach((b, bi) => {
                if (b.x < e.x + e.w && b.x + b.w > e.x && b.y < e.y + e.h && b.y + b.h > e.y) {
                    enemies.splice(ei, 1);
                    bullets.splice(bi, 1);
                    score += 100;
                }
            });

            if (e.y > height + 20) enemies.splice(ei, 1);
        });
    }

    function draw() {
        ctx.fillStyle = '#020617';
        ctx.fillRect(0, 0, width, height);

        // Stars
        ctx.fillStyle = 'rgba(255,255,255,0.4)';
        for (let i = 0; i < 30; i++) {
            ctx.fillRect((i * 97) % width, (i * 53 + Date.now() / 50) % height, 2, 2);
        }

        if (gameOver) {
            ctx.fillStyle = '#ef4444';
            ctx.font = '28px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('GAME OVER - Score: ' + score, width / 2, height / 2);
            ctx.fillStyle = '#94a3b8';
            ctx.font = '14px sans-serif';
            ctx.fillText('Klicke erneut auf Start um zu spielen', width / 2, height / 2 + 35);
            return;
        }

        // Draw Player Ship (Cyan triangle)
        ctx.fillStyle = '#38bdf8';
        ctx.beginPath();
        ctx.moveTo(player.x + player.w / 2, player.y);
        ctx.lineTo(player.x, player.y + player.h);
        ctx.lineTo(player.x + player.w, player.y + player.h);
        ctx.closePath();
        ctx.fill();

        // Draw Bullets (Glowing blue)
        ctx.fillStyle = '#67e8f9';
        bullets.forEach(b => ctx.fillRect(b.x, b.y, b.w, b.h));

        // Draw Enemies (Orange squares)
        ctx.fillStyle = '#f97316';
        enemies.forEach(e => {
            ctx.fillRect(e.x, e.y, e.w, e.h);
        });

        // Score
        ctx.fillStyle = '#f8fafc';
        ctx.font = 'bold 14px sans-serif';
        ctx.textAlign = 'left';
        ctx.fillText('SCORE: ' + score, 15, 25);
    }

    function loop() {
        update();
        draw();
        if (!gameOver) {
            requestAnimationFrame(loop);
        }
    }

    loop();
}

