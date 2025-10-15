<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username']) || !isset($_SESSION['cleared_levels'][5])) {
    $_SESSION['error'] = "Level 5를 클리어해야 접근할 수 있습니다.";
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Injection Training - 성공!</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #1a1a1a;
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
        }
        canvas {
            position: fixed;
            top: 0;
            left: 0;
            z-index: -1;
        }
        .container {
            max-width: 800px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 10px;
        }
        h1 {
            color: #0f0;
        }
        p {
            line-height: 1.6;
        }
        a {
            color: #0f0;
            text-decoration: none;
            margin: 0 10px;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <canvas id="fireworks"></canvas>
    <div class="container">
        <h1>축하합니다! 모든 레벨을 클리어했습니다!</h1>
        <p>당신은 SQL 인젝션 훈련을 성공적으로 완료했습니다!</p>
        <h2>SQL 인젝션의 위험성</h2>
        <p>
            SQL 인젝션은 웹 애플리케이션의 입력 필드를 통해 악의적인 SQL 쿼리를 삽입하여 데이터베이스를 조작하는 공격 기법입니다. 
            이를 통해 공격자는 민감한 데이터(예: 사용자 비밀번호, 개인 정보)를 탈취하거나, 데이터베이스를 수정/삭제하거나, 
            관리자 권한을 획득할 수 있습니다.
        </p>
        <p><strong>위험성:</strong></p>
        <ul style="text-align: left; display: inline-block;">
            <li>데이터 유출: 사용자 정보, 비밀번호, 신용카드 정보 등이 노출될 수 있습니다.</li>
            <li>데이터 손상: 데이터베이스의 데이터가 수정되거나 삭제될 수 있습니다.</li>
            <li>시스템 장악: 관리자 계정 접근으로 서버 전체를 제어할 가능성이 있습니다.</li>
        </ul>
        <p><strong>방지 방법:</strong></p>
        <ul style="text-align: left; display: inline-block;">
            <li>준비된 문장(Prepared Statements) 사용: 입력값을 쿼리에 직접 삽입하지 않고 파라미터로 처리.</li>
            <li>입력값 검증 및 sanitization: 사용자 입력을 철저히 검증하고 특수문자를 이스케이프 처리.</li>
            <li>최소 권한 원칙: 데이터베이스 계정에 필요한 최소한의 권한만 부여.</li>
            <li>에러 메시지 제한: 공격자에게 유용한 정보를 제공하지 않도록 에러 메시지 최소화.</li>
        </ul>
        <p>
            이 훈련을 통해 SQL 인젝션의 위험성을 이해하셨기를 바랍니다. 안전한 코딩을 실천하여 보안 취약점을 방지하세요!
        </p>
        <p>
            <a href="index.php?logout=true">로그아웃</a> | 
            <a href="index.php">메인 페이지로 돌아가기</a>
        </p>
    </div>

    <script>
        const canvas = document.getElementById('fireworks');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        class Particle {
            constructor(x, y, color) {
                this.x = x;
                this.y = y;
                this.color = color;
                this.radius = Math.random() * 2 + 1;
                this.vx = (Math.random() - 0.5) * 8;
                this.vy = (Math.random() - 0.5) * 8;
                this.life = 100;
            }
            update() {
                this.x += this.vx;
                this.y += this.vy;
                this.vy += 0.1; // Gravity
                this.life--;
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.fill();
            }
        }

        class Firework {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = canvas.height;
                this.targetY = Math.random() * canvas.height * 0.5;
                this.vy = -(Math.random() * 5 + 5);
                this.color = `hsl(${Math.random() * 360}, 100%, 50%)`;
                this.exploded = false;
                this.particles = [];
            }
            update() {
                if (!this.exploded) {
                    this.y += this.vy;
                    if (this.y <= this.targetY) {
                        this.explode();
                    }
                } else {
                    this.particles = this.particles.filter(p => p.life > 0);
                    this.particles.forEach(p => p.update());
                }
            }
            draw() {
                if (!this.exploded) {
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, 3, 0, Math.PI * 2);
                    ctx.fillStyle = this.color;
                    ctx.fill();
                } else {
                    this.particles.forEach(p => p.draw());
                }
            }
            explode() {
                this.exploded = true;
                for (let i = 0; i < 50; i++) {
                    this.particles.push(new Particle(this.x, this.y, this.color));
                }
            }
        }

        const fireworks = [];
        function animate() {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            if (Math.random() < 0.05) {
                fireworks.push(new Firework());
            }
            fireworks.forEach((fw, index) => {
                fw.update();
                fw.draw();
                if (fw.exploded && fw.particles.length === 0) {
                    fireworks.splice(index, 1);
                }
            });
            requestAnimationFrame(animate);
        }

        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });

        animate();
    </script>
</body>
</html>