/**
 * SPMB 2026 - SMK Pasundan 2 Bandung
 * Main JavaScript
 */

(function() {
  'use strict';

  // ============================================
  // MOBILE MENU TOGGLE
  // ============================================
  const MobileMenu = {
    toggle: null,
    menu: null,

    init() {
      this.toggle = document.querySelector('.navbar-toggle');
      this.menu = document.querySelector('.navbar-mobile');

      if (!this.toggle || !this.menu) return;

      this.toggle.addEventListener('click', () => this.toggleMenu());
    },

    toggleMenu() {
      this.toggle.classList.toggle('active');
      this.menu.classList.toggle('active');
    }
  };

  // ============================================
  // PARTICLE NETWORK ANIMATION
  // ============================================
  const ParticleNetwork = {
    canvas: null,
    ctx: null,
    particles: [],
    particleCount: 80,

    init() {
      this.canvas = document.getElementById('particles');
      if (!this.canvas) return;

      this.ctx = this.canvas.getContext('2d');
      this.resize();
      this.createParticles();
      this.bindEvents();
      this.animate();
    },

    resize() {
      if (!this.canvas) return;
      this.canvas.width = window.innerWidth;
      this.canvas.height = window.innerHeight;
    },

    createParticles() {
      this.particles = [];
      for (let i = 0; i < this.particleCount; i++) {
        this.particles.push(new Particle(
          Math.random() * this.canvas.width,
          Math.random() * this.canvas.height
        ));
      }
    },

    bindEvents() {
      window.addEventListener('resize', () => this.resize());
    },

    animate() {
      this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

      this.particles.forEach(p => {
        p.update(this.canvas.width, this.canvas.height);
        p.draw(this.ctx);
      });

      this.connectParticles();
      requestAnimationFrame(() => this.animate());
    },

    connectParticles() {
      for (let i = 0; i < this.particles.length; i++) {
        for (let j = i + 1; j < this.particles.length; j++) {
          const dx = this.particles[i].x - this.particles[j].x;
          const dy = this.particles[i].y - this.particles[j].y;
          const dist = Math.sqrt(dx * dx + dy * dy);

          if (dist < 150) {
            this.ctx.beginPath();
            this.ctx.strokeStyle = `rgba(0, 212, 255, ${0.2 - dist / 750})`;
            this.ctx.lineWidth = 0.5;
            this.ctx.moveTo(this.particles[i].x, this.particles[i].y);
            this.ctx.lineTo(this.particles[j].x, this.particles[j].y);
            this.ctx.stroke();
          }
        }
      }
    }
  };

  class Particle {
    constructor(x, y) {
      this.x = x;
      this.y = y;
      this.vx = (Math.random() - 0.5) * 0.5;
      this.vy = (Math.random() - 0.5) * 0.5;
      this.size = Math.random() * 2 + 1;
    }

    update(width, height) {
      this.x += this.vx;
      this.y += this.vy;

      if (this.x < 0 || this.x > width) this.vx *= -1;
      if (this.y < 0 || this.y > height) this.vy *= -1;
    }

    draw(ctx) {
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(0, 212, 255, 0.5)';
      ctx.fill();
    }
  }

  // ============================================
  // TYPING EFFECT
  // ============================================
  const TypingEffect = {
    texts: [
      'Wujudkan Mimpimu',
      'Siap Kerja, Siap Sukses',
      'Pilihan Jurusan Unggulan',
      '#SMKPasundan2Bandung'
    ],
    textIndex: 0,
    charIndex: 0,
    isDeleting: false,
    typingEl: null,

    init() {
      this.typingEl = document.getElementById('typing');
      if (!this.typingEl) return;
      this.type();
    },

    type() {
      const current = this.texts[this.textIndex];

      if (this.isDeleting) {
        this.typingEl.textContent = current.substring(0, this.charIndex - 1);
        this.charIndex--;
      } else {
        this.typingEl.textContent = current.substring(0, this.charIndex + 1);
        this.charIndex++;
      }

      let speed = this.isDeleting ? 50 : 100;

      if (!this.isDeleting && this.charIndex === current.length) {
        speed = 2000;
        this.isDeleting = true;
      } else if (this.isDeleting && this.charIndex === 0) {
        this.isDeleting = false;
        this.textIndex = (this.textIndex + 1) % this.texts.length;
        speed = 500;
      }

      setTimeout(() => this.type(), speed);
    }
  };

  // ============================================
  // COUNTDOWN TIMER
  // ============================================
  const Countdown = {
    targetDate: new Date('2026-06-30T23:59:59').getTime(),
    elements: {
      days: null,
      hours: null,
      minutes: null,
      seconds: null
    },
    interval: null,

    init() {
      this.elements.days = document.getElementById('days');
      this.elements.hours = document.getElementById('hours');
      this.elements.minutes = document.getElementById('minutes');
      this.elements.seconds = document.getElementById('seconds');

      if (!this.elements.days) return;

      this.update();
      this.interval = setInterval(() => this.update(), 1000);
    },

    update() {
      const now = new Date().getTime();
      const diff = this.targetDate - now;

      if (diff > 0) {
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        this.elements.days.textContent = String(days).padStart(2, '0');
        this.elements.hours.textContent = String(hours).padStart(2, '0');
        this.elements.minutes.textContent = String(minutes).padStart(2, '0');
        this.elements.seconds.textContent = String(seconds).padStart(2, '0');
      }
    },

    destroy() {
      if (this.interval) {
        clearInterval(this.interval);
      }
    }
  };

  // ============================================
  // SMOOTH SCROLL
  // ============================================
  const SmoothScroll = {
    init() {
      document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
          e.preventDefault();
          const target = document.querySelector(a.getAttribute('href'));
          if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
          }
        });
      });
    }
  };

  // ============================================
  // INITIALIZE ALL
  // ============================================
  document.addEventListener('DOMContentLoaded', () => {
    MobileMenu.init();
    ParticleNetwork.init();
    TypingEffect.init();
    Countdown.init();
    SmoothScroll.init();
  });

})();
