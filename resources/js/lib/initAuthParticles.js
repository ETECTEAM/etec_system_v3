export function initAuthParticles(containerId = 'particles-js') {
  if (typeof window === 'undefined') {
    return () => {}
  }

  const container = document.getElementById(containerId)

  if (!container) {
    return () => {}
  }

  const activeInstance = window.__authParticlesInstance
  const activeCanvas = container.querySelector('canvas[data-auth-particles="true"]')

  if (
    activeInstance
    && activeInstance.containerId === containerId
    && activeCanvas
  ) {
    return activeInstance.cleanup
  }

  activeInstance?.cleanup?.()

  const existingCanvas = container.querySelector('canvas[data-auth-particles="true"]')
  if (existingCanvas) {
    existingCanvas.remove()
  }

  const canvas = document.createElement('canvas')
  const context = canvas.getContext('2d')

  if (!context) {
    return () => {}
  }

  canvas.dataset.authParticles = 'true'
  canvas.className = 'h-full w-full'
  container.replaceChildren(canvas)

  const particles = []
  const particleCount = 50
  const particleColor = 'rgba(96, 165, 250, 0.9)'
  const lineColor = 'rgba(147, 197, 253, 0.35)'
  const maxDistance = 150
  let animationFrameId = null

  function resize() {
    const { width, height } = container.getBoundingClientRect()
    canvas.width = width
    canvas.height = height
  }

  function createParticle() {
    return {
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height,
      vx: (Math.random() - 0.5) * 0.45,
      vy: (Math.random() - 0.5) * 0.45,
      radius: Math.random() * 2 + 1.2,
    }
  }

  function initializeParticles() {
    particles.length = 0

    for (let index = 0; index < particleCount; index += 1) {
      particles.push(createParticle())
    }
  }

  function updateParticle(particle) {
    particle.x += particle.vx
    particle.y += particle.vy

    if (particle.x <= 0 || particle.x >= canvas.width) {
      particle.vx *= -1
    }

    if (particle.y <= 0 || particle.y >= canvas.height) {
      particle.vy *= -1
    }
  }

  function drawParticle(particle) {
    context.beginPath()
    context.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2)
    context.fillStyle = particleColor
    context.fill()
  }

  function drawConnections() {
    for (let startIndex = 0; startIndex < particles.length; startIndex += 1) {
      for (let endIndex = startIndex + 1; endIndex < particles.length; endIndex += 1) {
        const a = particles[startIndex]
        const b = particles[endIndex]
        const dx = a.x - b.x
        const dy = a.y - b.y
        const distance = Math.sqrt(dx * dx + dy * dy)

        if (distance > maxDistance) {
          continue
        }

        context.beginPath()
        context.moveTo(a.x, a.y)
        context.lineTo(b.x, b.y)
        context.strokeStyle = lineColor
        context.globalAlpha = 1 - distance / maxDistance
        context.lineWidth = 1
        context.stroke()
      }
    }

    context.globalAlpha = 1
  }

  function animate() {
    context.clearRect(0, 0, canvas.width, canvas.height)

    particles.forEach((particle) => {
      updateParticle(particle)
      drawParticle(particle)
    })

    drawConnections()
    animationFrameId = window.requestAnimationFrame(animate)
  }

  resize()
  initializeParticles()
  animate()

  const resizeObserver = new ResizeObserver(() => {
    resize()
    initializeParticles()
  })

  resizeObserver.observe(container)

  const cleanup = () => {
    if (animationFrameId) {
      window.cancelAnimationFrame(animationFrameId)
    }

    resizeObserver.disconnect()
    canvas.remove()

    if (window.__authParticlesInstance?.cleanup === cleanup) {
      delete window.__authParticlesInstance
    }
  }

  window.__authParticlesInstance = {
    cleanup,
    containerId,
  }

  return cleanup
}
