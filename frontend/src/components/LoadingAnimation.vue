<template>
  <div class="book-loading-container">
    <canvas ref="canvasRef"></canvas>
    <div class="loading-text">Loading...</div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'

interface Props {
  bookSize?: number // 书本大小缩放比例，默认为 1
}

const props = withDefaults(defineProps<Props>(), {
  bookSize: 1,
})

const canvasRef = ref<HTMLCanvasElement | null>(null)

let ctx: CanvasRenderingContext2D | null = null
let width = 0
let height = 0
let particles: Particle[] = []
let frame = 0
let animationId: number | null = null

const colors = {
  bg: '#1a0b2e',
  bookCover: '#5d4037',
  page: '#fdfbf7',
  pageShadow: '#e0dcd3',
  magic: ['#ffd700', '#ffecb3', '#ffa500'],
  text: '#e6e1d8',
}

class Particle {
  x: number
  y: number
  vx: number
  vy: number
  life: number
  opacity: number
  size: number
  type: string
  color: string
  angle: number
  spin: number

  constructor(x: number, y: number) {
    this.x = x
    this.y = y
    this.vx = (Math.random() - 0.5) * 2
    this.vy = -Math.random() * 2 - 1
    this.life = 100
    this.opacity = 1
    this.size = Math.random() * 3 + 1
    this.type = Math.random() > 0.7 ? 'note' : 'dust'
    this.color = colors.magic[Math.floor(Math.random() * colors.magic.length)]
    this.angle = Math.random() * Math.PI * 2
    this.spin = (Math.random() - 0.5) * 0.2
  }

  update() {
    this.x += this.vx
    this.y += this.vy
    this.life--
    this.opacity = this.life / 100
    this.angle += this.spin
    this.x += Math.sin(this.life * 0.1) * 0.5
  }

  draw(ctx: CanvasRenderingContext2D) {
    ctx.save()
    ctx.translate(this.x, this.y)
    ctx.rotate(this.angle)
    ctx.globalAlpha = this.opacity
    ctx.fillStyle = this.color

    if (this.type === 'note') {
      ctx.beginPath()
      ctx.arc(0, 0, this.size, 0, Math.PI * 2)
      ctx.fill()
      ctx.fillRect(this.size - 1, -this.size * 3, 2, this.size * 3)
      ctx.fillRect(this.size - 1, -this.size * 3, this.size * 1.5, 4)
    } else {
      ctx.beginPath()
      ctx.moveTo(0, -this.size)
      ctx.lineTo(this.size, 0)
      ctx.lineTo(0, this.size)
      ctx.lineTo(-this.size, 0)
      ctx.closePath()
      ctx.fill()
    }
    ctx.restore()
  }
}

class Book {
  width: number
  height: number
  state: string
  waitTimer: number
  waitDuration: number
  rawProgress: number
  flipProgress: number
  flipSpeed: number
  hoverOffset: number
  curveAmount: number
  textMargin: number
  scale: number

  constructor(scale = 1) {
    this.scale = scale
    this.width = 120 * scale
    this.height = 160 * scale
    this.state = 'flipping'
    this.waitTimer = 0
    this.waitDuration = 50
    this.rawProgress = 0
    this.flipProgress = 0
    this.flipSpeed = 0.02
    this.hoverOffset = 0
    this.curveAmount = 15 * scale
    this.textMargin = 10 * scale
  }

  easeInOutCubic(x: number) {
    return x < 0.5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2
  }

  update() {
    this.hoverOffset = Math.sin(frame * 0.05) * 10 * this.scale

    if (this.state === 'waiting') {
      this.waitTimer++
      if (this.waitTimer >= this.waitDuration) {
        this.state = 'flipping'
        this.rawProgress = 0
      }
    } else {
      this.rawProgress += this.flipSpeed
      this.flipProgress = this.easeInOutCubic(this.rawProgress)

      if (this.rawProgress > 0.45 && this.rawProgress < 0.55) {
        if (Math.random() > 0.3) {
          particles.push(new Particle(width / 2, height / 2 + this.hoverOffset))
        }
      }

      if (this.rawProgress >= 1) {
        this.rawProgress = 1
        this.flipProgress = 0
        this.state = 'waiting'
        this.waitTimer = 0
        for (let i = 0; i < 5; i++) {
          particles.push(new Particle(width / 2 - this.width / 2, height / 2 + this.hoverOffset))
        }
      }
    }
  }

  draw(ctx: CanvasRenderingContext2D) {
    const centerX = width / 2
    const centerY = height / 2 + this.hoverOffset

    ctx.save()
    ctx.translate(centerX, centerY)

    ctx.shadowColor = 'rgba(0,0,0,0.5)'
    ctx.shadowBlur = 30 * this.scale
    ctx.shadowOffsetY = (40 - this.hoverOffset) * this.scale

    this.drawPage(ctx, -this.width, 0, this.width, this.height, false)
    this.drawPage(ctx, 0, 0, this.width, this.height, true)

    if (this.state === 'flipping') {
      this.drawFlippingPage(ctx)
    }

    ctx.fillStyle = colors.bookCover
    ctx.fillRect(-2 * this.scale, -this.height / 2, 4 * this.scale, this.height)

    ctx.restore()
  }

  drawPage(
    ctx: CanvasRenderingContext2D,
    xOffset: number,
    y: number,
    w: number,
    h: number,
    isRightSide: boolean,
  ) {
    ctx.fillStyle = colors.page
    ctx.beginPath()

    const curve = this.curveAmount

    if (isRightSide) {
      ctx.moveTo(xOffset, -h / 2)
      ctx.lineTo(xOffset + w, -h / 2 - curve)
      ctx.lineTo(xOffset + w, h / 2 - curve)
      ctx.lineTo(xOffset, h / 2)
    } else {
      ctx.moveTo(xOffset + w, -h / 2)
      ctx.lineTo(xOffset, -h / 2 - curve)
      ctx.lineTo(xOffset, h / 2 - curve)
      ctx.lineTo(xOffset + w, h / 2)
    }
    ctx.closePath()
    ctx.fill()

    ctx.strokeStyle = colors.pageShadow
    ctx.lineWidth = 1 * this.scale
    ctx.stroke()

    ctx.fillStyle = colors.text
    const textLines = 6
    const marginX = this.textMargin

    for (let i = 0; i < textLines; i++) {
      const lineBaseY = -h / 2 + 30 * this.scale + i * 15 * this.scale

      ctx.beginPath()

      if (isRightSide) {
        const xStart = xOffset + marginX
        const xEnd = xOffset + w - marginX

        const yStartOffset = -curve * (marginX / w)
        const yEndOffset = -curve * ((w - marginX) / w)

        const yStart = lineBaseY + yStartOffset
        const yEnd = lineBaseY + yEndOffset

        ctx.moveTo(xStart, yStart)
        ctx.lineTo(xEnd, yEnd)
        ctx.lineTo(xEnd, yEnd + 2 * this.scale)
        ctx.lineTo(xStart, yStart + 2 * this.scale)
      } else {
        const xStart = xOffset + marginX
        const xEnd = xOffset + w - marginX

        const yStartOffset = -curve * (1 - marginX / w)
        const yEndOffset = -curve * (marginX / w)

        const yStart = lineBaseY + yStartOffset
        const yEnd = lineBaseY + yEndOffset

        ctx.moveTo(xStart, yStart)
        ctx.lineTo(xEnd, yEnd)
        ctx.lineTo(xEnd, yEnd + 2 * this.scale)
        ctx.lineTo(xStart, yStart + 2 * this.scale)
      }

      ctx.closePath()
      ctx.fill()
    }
  }

  drawFlippingPage(ctx: CanvasRenderingContext2D) {
    const p = this.flipProgress
    const h = this.height
    const curve = this.curveAmount
    const marginX = this.textMargin

    ctx.fillStyle = colors.page
    ctx.strokeStyle = colors.pageShadow

    if (p <= 0.5) {
      const progress = p * 2
      const w = this.width * (1 - progress)
      const lift = Math.sin(progress * Math.PI) * 20 * this.scale

      const edgeYTop = -h / 2 - curve - lift
      const edgeYBottom = h / 2 - curve - lift

      ctx.beginPath()
      ctx.moveTo(0, -h / 2)
      ctx.lineTo(w, edgeYTop)
      ctx.lineTo(w, edgeYBottom)
      ctx.lineTo(0, h / 2)
      ctx.closePath()
      ctx.fill()
      ctx.stroke()

      ctx.fillStyle = colors.text
      if (w > marginX * 2) {
        for (let i = 0; i < 6; i++) {
          const lineBaseY = -h / 2 + 30 * this.scale + i * 15 * this.scale

          const yStart = lineBaseY
          const yEnd = lineBaseY - curve - lift

          ctx.beginPath()
          ctx.moveTo(marginX, yStart)
          ctx.lineTo(w - marginX, yEnd)
          ctx.lineTo(w - marginX, yEnd + 2 * this.scale)
          ctx.lineTo(marginX, yStart + 2 * this.scale)
          ctx.fill()
        }
      }
    } else {
      const progress = (p - 0.5) * 2
      const w = this.width * progress
      const lift = Math.sin((1 - progress) * Math.PI) * 20 * this.scale

      const edgeYTop = -h / 2 - curve - lift
      const edgeYBottom = h / 2 - curve - lift

      ctx.beginPath()
      ctx.moveTo(0, -h / 2)
      ctx.lineTo(-w, edgeYTop)
      ctx.lineTo(-w, edgeYBottom)
      ctx.lineTo(0, h / 2)
      ctx.closePath()

      const originalFill = ctx.fillStyle
      ctx.fillStyle = '#f0ece3'
      ctx.fill()
      ctx.fillStyle = originalFill
      ctx.stroke()

      ctx.fillStyle = colors.text
      if (w > marginX * 2) {
        for (let i = 0; i < 6; i++) {
          const lineBaseY = -h / 2 + 30 * this.scale + i * 15 * this.scale

          const yRight = lineBaseY
          const yLeft = lineBaseY - curve - lift

          ctx.beginPath()
          ctx.moveTo(-w + marginX, yLeft)
          ctx.lineTo(-marginX, yRight)
          ctx.lineTo(-marginX, yRight + 2 * this.scale)
          ctx.lineTo(-w + marginX, yLeft + 2 * this.scale)
          ctx.fill()
        }
      }
    }
  }
}

let book: Book | null = null

function resize() {
  if (!canvasRef.value) return
  width = window.innerWidth
  height = window.innerHeight
  canvasRef.value.width = width
  canvasRef.value.height = height
}

function animate() {
  if (!ctx || !canvasRef.value) return

  ctx.clearRect(0, 0, width, height)

  if (book) {
    book.update()
    book.draw(ctx)
  }

  for (let i = particles.length - 1; i >= 0; i--) {
    particles[i].update()
    particles[i].draw(ctx)
    if (particles[i].life <= 0) {
      particles.splice(i, 1)
    }
  }

  frame++
  animationId = requestAnimationFrame(animate)
}

onMounted(() => {
  if (!canvasRef.value) return

  ctx = canvasRef.value.getContext('2d')
  if (!ctx) return

  resize()
  window.addEventListener('resize', resize)

  book = new Book(props.bookSize)
  animate()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', resize)
  if (animationId !== null) {
    cancelAnimationFrame(animationId)
  }
})
</script>

<style scoped>
.book-loading-container {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background-color: #1a0b2e;
}

canvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.loading-text {
  position: absolute;
  top: 50%;
  margin-top: 80px;
  color: #deb887;
  font-size: 16px;
  letter-spacing: 4px;
  text-transform: uppercase;
  animation: pulse 2s infinite ease-in-out;
  text-shadow: 0 0 10px rgba(222, 184, 135, 0.5);
  font-family: 'Georgia', serif;
  z-index: 2;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 0.6;
    transform: translateY(0);
  }
  50% {
    opacity: 1;
    transform: translateY(-5px);
  }
}
</style>
