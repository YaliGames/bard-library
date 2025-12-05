<template>
  <div class="relative w-full text-slate-100 font-sans selection:bg-amber-500 selection:text-white">
    <div class="fixed inset-0 -z-50 pointer-events-none">
      <div class="absolute inset-0 bg-gradient-to-b from-blue-900 via-[#1e1b4b] to-[#020617]"></div>

      <div
        class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-purple-500/10 blur-[120px] rounded-full mix-blend-screen"
      ></div>

      <div
        class="absolute top-[10%] left-[-10%] w-[40vw] h-[40vw] bg-blue-400/10 rounded-full blur-[100px] animate-pulse-slow"
      ></div>
      <div
        class="absolute bottom-[10%] right-[-10%] w-[40vw] h-[40vw] bg-amber-600/10 rounded-full blur-[120px] animate-pulse-slow delay-1000"
      ></div>

      <div
        v-for="(star, index) in stars"
        :key="index"
        class="absolute rounded-full bg-white opacity-0 animate-twinkle"
        :style="{
          top: star.top,
          left: star.left,
          width: star.size,
          height: star.size,
          animationDelay: star.delay,
          animationDuration: star.duration,
        }"
      ></div>
    </div>

    <header class="relative mt-28 pb-20 px-6 md:px-12">
      <div
        class="container mx-auto relative z-10 flex flex-col lg:flex-row items-center justify-center gap-8 lg:gap-16"
      >
        <div class="flex-shrink-0 mb-10 lg:mb-0">
          <div
            class="absolute -inset-8 bg-amber-400/20 rounded-full blur-2xl opacity-40 group-hover:opacity-60 transition duration-700"
          ></div>
          <img
            src="/src/images/logo-white.svg"
            class="relative w-48 md:w-64 drop-shadow-2xl transform transition hover:scale-105 duration-500"
          />
        </div>
        <div class="text-center lg:text-left lg:flex-1 lg:max-w-3xl">
          <h1
            class="font-serif text-5xl md:text-6xl font-bold mb-6 leading-tight tracking-wide text-white drop-shadow-md"
          >
            构建你的
            <br />
            <span
              class="text-transparent bg-clip-text bg-gradient-to-r from-amber-200 via-yellow-100 to-amber-300"
            >
              无界数字图书馆
            </span>
          </h1>
          <p
            class="text-lg md:text-xl mb-10 text-blue-100 leading-relaxed mx-auto lg:mx-0 font-light border-l-2 border-amber-400/30"
          >
            Bard Library 赋予您管理海量知识资产的能力。
            支持全格式收纳与多端同步，为您打造一座永不打烊的私人知识殿堂。
          </p>

          <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
            <router-link
              to="/login"
              class="px-8 py-3 bg-gradient-to-r from-amber-500 to-yellow-600 text-slate-900 rounded shadow-[0_0_20px_rgba(245,158,11,0.3)] font-serif font-bold hover:shadow-[0_0_30px_rgba(245,158,11,0.5)] transition-all duration-300 transform hover:-translate-y-1"
            >
              立即开启
            </router-link>
            <a
              href="https://bard.yaligames.com/docs/"
              target="_blank"
              class="px-8 py-3 border border-blue-300/30 bg-blue-900/40 text-blue-100 rounded hover:bg-blue-800/60 hover:border-blue-300/50 transition-all duration-300 backdrop-blur-sm"
            >
              查阅文档
            </a>
          </div>
        </div>
      </div>
    </header>

    <section class="py-20 relative">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16">
          <h2 class="font-serif text-3xl md:text-4xl text-white font-semibold tracking-wider mb-4">
            核心功能
          </h2>
          <div
            class="w-24 h-1 bg-gradient-to-r from-transparent via-indigo-500 to-transparent mx-auto"
          ></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
          <div
            v-for="(feature, idx) in features"
            :key="idx"
            class="group bg-slate-800/40 backdrop-blur-md border border-white/5 p-8 rounded-lg hover:bg-slate-800/60 hover:border-indigo-500/30 transition-all duration-300"
          >
            <div
              class="mb-6 inline-block p-3 rounded-lg bg-gradient-to-br from-slate-700 to-slate-800 border border-white/5 shadow-inner group-hover:from-indigo-900 group-hover:to-slate-800 transition-colors"
            >
              <span
                class="material-symbols-outlined text-3xl text-indigo-300 group-hover:text-amber-300 transition-colors"
              >
                {{ feature.icon }}
              </span>
            </div>
            <h3 class="font-serif text-xl font-bold mb-3 text-slate-100">{{ feature.title }}</h3>
            <p class="text-slate-400 text-sm leading-relaxed">
              {{ feature.desc }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="py-20 bg-gradient-to-b from-transparent to-slate-900/80">
      <div class="container mx-auto px-6 flex flex-col lg:flex-row items-center gap-16">
        <div class="lg:w-1/2 text-center lg:text-left">
          <h2 class="font-serif text-3xl md:text-4xl font-bold mb-6 text-white mb-0">
            全域阅读体验
          </h2>
          <div class="w-24 h-1 bg-gradient-to-r from-indigo-500 to-transparent my-4"></div>
          <p class="text-slate-300 text-lg mb-8 leading-relaxed">
            无论是在宽阔的桌面显示器，还是便携的平板与手机，Bard Library
            都能提供一致且优雅的阅读界面。
            <br />
            您的阅读进度、高亮笔记将在所有设备间实时同步，让知识的获取不再受限于物理空间。
          </p>
          <ul class="space-y-4 text-slate-400 text-left inline-block">
            <li class="flex items-center gap-3">
              <span class="material-symbols-outlined text-amber-500">check_circle</span>
              响应式 Web 界面，无需安装客户端
            </li>
            <li class="flex items-center gap-3">
              <span class="material-symbols-outlined text-amber-500">check_circle</span>
              针对墨水屏 (E-Ink) 设备的对比度优化
            </li>
            <li class="flex items-center gap-3">
              <span class="material-symbols-outlined text-amber-500">check_circle</span>
              支持离线缓存，断网亦可畅读
            </li>
          </ul>
        </div>

        <div
          class="lg:w-1/2 w-full relative h-[500px] flex items-center justify-center perspective-1000"
        >
          <!-- 1. Laptop -->
          <div class="absolute animate-float-slow w-[90%] max-w-[500px] z-10">
            <div
              class="relative bg-slate-900/95 backdrop-blur-xl border border-indigo-400/40 rounded-t-xl p-2 shadow-2xl shadow-indigo-900/50 aspect-[16/10] flex flex-col group transition-all duration-500 hover:shadow-indigo-500/20"
            >
              <div
                class="flex-1 bg-slate-800/80 rounded-lg overflow-hidden relative border border-white/5 flex"
              >
                <div
                  class="w-16 border-r border-white/5 bg-slate-900/50 flex flex-col items-center py-4 gap-4"
                >
                  <div
                    class="w-6 h-6 rounded bg-indigo-500/80 shadow-[0_0_10px_rgba(99,102,241,0.5)]"
                  ></div>
                  <div class="w-8 h-[1px] bg-white/10 my-1"></div>
                  <div
                    class="w-5 h-5 rounded bg-slate-700/50 hover:bg-slate-600 transition-colors"
                  ></div>
                  <div class="w-5 h-5 rounded bg-slate-700/50"></div>
                  <div class="w-5 h-5 rounded bg-slate-700/50"></div>
                  <div class="mt-auto w-5 h-5 rounded-full bg-slate-600/50"></div>
                </div>

                <div class="flex-1 flex flex-col">
                  <div
                    class="h-10 border-b border-white/5 flex items-center justify-between px-4 bg-slate-800/50"
                  >
                    <div class="h-2 w-24 bg-slate-700/50 rounded-full"></div>
                    <div class="flex gap-2">
                      <div class="w-20 h-6 bg-slate-900/50 rounded-md border border-white/5"></div>
                    </div>
                  </div>

                  <div class="flex-1 p-5 flex gap-6 overflow-hidden">
                    <div class="w-1/3 flex flex-col gap-3">
                      <div
                        class="aspect-[2/3] w-full rounded-md bg-gradient-to-br from-slate-700 to-slate-800 border border-white/10 shadow-lg relative overflow-hidden group-hover:border-indigo-400/50 transition-colors"
                      >
                        <div
                          class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"
                        ></div>
                        <div class="absolute bottom-3 left-3 w-1/2 h-2 bg-white/20 rounded"></div>
                      </div>
                      <div class="h-2 w-3/4 bg-slate-700/50 rounded"></div>
                      <div class="h-2 w-1/2 bg-slate-700/50 rounded"></div>
                      <div class="mt-2 flex gap-2">
                        <div
                          class="h-6 flex-1 bg-amber-500 border border-amber-600/30 rounded text-xs flex items-center justify-center text-amber-500"
                        ></div>
                      </div>
                    </div>
                    <div class="flex-1 space-y-3 pt-2">
                      <div class="h-4 w-1/3 bg-slate-600/50 rounded mb-4"></div>
                      <div class="space-y-2 opacity-60">
                        <div class="h-2 w-full bg-slate-500 rounded"></div>
                        <div class="h-2 w-full bg-slate-500 rounded"></div>
                        <div class="h-2 w-5/6 bg-slate-500 rounded"></div>
                        <div class="h-2 w-full bg-slate-500 rounded"></div>
                        <div class="h-2 w-4/5 bg-slate-500 rounded"></div>
                      </div>
                      <div class="space-y-2 opacity-40 mt-4">
                        <div class="h-2 w-full bg-slate-500 rounded"></div>
                        <div class="h-2 w-2/3 bg-slate-500 rounded"></div>
                      </div>
                    </div>
                  </div>
                </div>

                <div
                  class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/5 to-transparent pointer-events-none"
                ></div>
              </div>
            </div>
            <div
              class="h-3 bg-slate-800 border-t border-indigo-500/20 rounded-b-lg mx-auto w-[98%] shadow-[0_20px_40px_rgba(0,0,0,0.6)] relative"
            >
              <div
                class="absolute top-0 left-1/2 -translate-x-1/2 w-16 h-1 bg-slate-700 rounded-b-sm"
              ></div>
            </div>
          </div>

          <!-- 2. Tablet -->
          <div class="absolute -right-8 bottom-16 w-[180px] md:w-[220px] animate-float-medium z-20">
            <div
              class="bg-slate-900/95 backdrop-blur-xl border border-indigo-400/40 rounded-xl p-2 shadow-[0_20px_50px_rgba(30,27,75,0.5)] aspect-[3/4] flex flex-col transform rotate-[-6deg] hover:rotate-0 transition-transform duration-500 group"
            >
              <div class="w-1 h-1 rounded-full bg-slate-600 mx-auto mb-2 opacity-50"></div>
              <div
                class="flex-1 bg-slate-800/80 rounded-lg overflow-hidden relative border border-white/5 flex flex-col"
              >
                <div class="h-8 border-b border-white/5 flex items-center px-3 gap-2 bg-slate-800">
                  <div class="w-4 h-4 rounded bg-indigo-500/50"></div>
                  <div class="flex-1 h-2 bg-slate-700/50 rounded-full"></div>
                </div>
                <div class="flex-1 flex">
                  <div
                    class="w-8 border-r border-white/5 bg-slate-900/30 flex flex-col items-center py-3 gap-3"
                  >
                    <div class="w-4 h-4 rounded-full bg-slate-700/50"></div>
                    <div
                      class="w-4 h-4 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.6)]"
                    ></div>
                  </div>
                  <div class="flex-1 p-3 grid grid-cols-2 gap-2 content-start">
                    <div
                      class="aspect-[2/3] rounded bg-gradient-to-br from-indigo-900 to-slate-800 border border-indigo-500/30 group-hover:border-amber-500/50 transition-colors"
                    ></div>
                    <div class="aspect-[2/3] rounded bg-slate-700/30 border border-white/5"></div>
                    <div class="aspect-[2/3] rounded bg-slate-700/30 border border-white/5"></div>
                    <div class="aspect-[2/3] rounded bg-slate-700/30 border border-white/5"></div>
                  </div>
                </div>
                <div
                  class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/5 to-transparent pointer-events-none"
                ></div>
              </div>
            </div>
          </div>

          <!-- 3. Mobile -->
          <div class="absolute -left-4 -bottom-2 w-[140px] md:w-[160px] animate-float-fast z-30">
            <div
              class="bg-slate-900/95 backdrop-blur-xl border border-indigo-400/40 rounded-[2.5rem] p-2 shadow-[0_15px_40px_rgba(0,0,0,0.8)] transform rotate-[8deg] hover:rotate-0 transition-transform duration-500 group"
            >
              <div
                class="absolute top-4 left-1/2 -translate-x-1/2 w-16 h-4 bg-black rounded-full z-40 flex items-center justify-center gap-2 shadow-sm pointer-events-none"
              >
                <div class="w-1.5 h-1.5 rounded-full bg-indigo-900/30"></div>
                <div class="w-1 h-1 rounded-full bg-indigo-800/20"></div>
              </div>

              <div
                class="bg-slate-800 rounded-[2rem] h-[280px] overflow-hidden relative border border-white/5 flex flex-col"
              >
                <div
                  class="h-[110px] bg-gradient-to-b from-indigo-950 to-slate-800 flex items-end p-3 gap-3 relative"
                >
                  <div
                    class="w-10 h-14 bg-slate-700 rounded shadow-lg border border-white/10 flex-shrink-0 group-hover:border-amber-500/50 transition-colors relative overflow-hidden"
                  >
                    <div
                      class="absolute inset-0 bg-gradient-to-tr from-black/40 to-transparent"
                    ></div>
                  </div>

                  <div class="flex-1 pb-1 space-y-1.5">
                    <div class="h-2 w-full bg-slate-500/50 rounded"></div>
                    <div class="h-1.5 w-2/3 bg-slate-600/30 rounded"></div>
                  </div>
                </div>

                <div class="flex-1 p-4 bg-slate-800 flex flex-col gap-3">
                  <div class="flex items-center gap-2">
                    <div class="h-1 flex-1 bg-slate-700 rounded-full overflow-hidden">
                      <div
                        class="h-full w-[65%] bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.8)]"
                      ></div>
                    </div>
                    <div class="text-[0.5rem] text-slate-500 font-serif">65%</div>
                  </div>

                  <div class="space-y-2 opacity-70">
                    <div class="h-1.5 w-full bg-slate-500 rounded"></div>
                    <div class="h-1.5 w-full bg-slate-500 rounded"></div>
                    <div class="h-1.5 w-11/12 bg-slate-500 rounded"></div>
                    <div class="h-1.5 w-full bg-slate-500 rounded"></div>
                    <div class="h-1.5 w-4/5 bg-slate-500 rounded"></div>
                  </div>
                </div>

                <div
                  class="absolute bottom-2 left-1/2 -translate-x-1/2 w-1/3 h-1 bg-white/10 rounded-full"
                ></div>

                <div
                  class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/5 to-transparent pointer-events-none"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="py-16 border-t border-white/5 relative bg-slate-950">
      <div class="container mx-auto px-6 text-center">
        <span class="material-symbols-outlined text-5xl text-slate-600 mb-6">code_blocks</span>
        <h2 class="text-2xl font-serif font-bold mb-4 text-slate-200">开源与社区驱动</h2>
        <p class="text-slate-400 max-w-2xl mx-auto mb-8">
          Bard Library 遵循开源协议，代码透明且安全。你可以参与贡献，或部署属于自己的独立实例。
        </p>
        <div class="flex justify-center gap-6">
          <a
            href="https://github.com/yaligames/bard-library"
            target="_blank"
            class="text-slate-500 hover:text-white transition-colors flex items-center gap-2"
          >
            <span class="font-bold">GitHub</span>
            Repository
          </a>
          <span class="text-slate-700">|</span>
          <a
            href="https://bard.yaligames.com/docs/"
            target="_blank"
            class="text-slate-500 hover:text-white transition-colors"
          >
            文档中心
          </a>
        </div>
        <div class="mt-12 text-xs text-slate-700">
          &copy; {{ new Date().getFullYear() }} Bard Library. All Rights Reserved.
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const features = [
  {
    icon: 'book_2',
    title: '多格式资源管理',
    desc: '打破格式壁垒，统一管理 EPUB, PDF, TXT 等多种文档资源，构建标准化的数字资产库。',
  },
  {
    icon: 'auto_stories',
    title: '沉浸式在线阅读',
    desc: '内置高性能阅读器，支持流式排版与版式文档，自动同步阅读进度，体验如丝般顺滑。',
  },
  {
    icon: 'file_download',
    title: '离线归档与下载',
    desc: '支持将云端资源完整下载至本地设备，确保数据所有权，实现真正的永久保存。',
  },
  {
    icon: 'tag',
    title: '智能元数据刮削',
    desc: '自动匹配书籍封面、作者简介与出版信息，将杂乱的文件整理为精美的可视化书架。',
  },
  {
    icon: 'manage_accounts',
    title: '多用户权限体系',
    desc: '完善的账户管理系统，支持多用户独立书架与阅读历史，适合家庭或小团队共享。',
  },
  {
    icon: 'rss_feed',
    title: '资讯聚合订阅',
    desc: '集成了 RSS 订阅模块，自动抓取并聚合关注源的最新更新，实现信息的一站式获取。',
  },
]

const stars = ref([])

onMounted(() => {
  const starCount = 50
  const tempStars = []
  for (let i = 0; i < starCount; i++) {
    tempStars.push({
      top: Math.random() * 100 + '%',
      left: Math.random() * 100 + '%',
      size: Math.random() * 3 + 'px',
      delay: Math.random() * 5 + 's',
      duration: Math.random() * 3 + 2 + 's',
    })
  }
  stars.value = tempStars
})
</script>

<style scoped>
.font-serif {
  font-family: serif, Georgia, 'Times New Roman', Times, serif;
}

.font-sans {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

@keyframes twinkle {
  0%,
  100% {
    opacity: 0;
    transform: scale(0.5);
  }

  50% {
    opacity: 0.6;
    transform: scale(1.1);
  }
}

.animate-twinkle {
  animation-name: twinkle;
  animation-timing-function: ease-in-out;
  animation-iteration-count: infinite;
}

@keyframes pulse-slow {
  0%,
  100% {
    transform: scale(1);
    opacity: 0.2;
  }

  50% {
    transform: scale(1.05);
    opacity: 0.35;
  }
}

.animate-pulse-slow {
  animation: pulse-slow 10s infinite ease-in-out;
}

@keyframes float {
  0%,
  100% {
    transform: translateY(0px);
  }

  50% {
    transform: translateY(-20px);
  }
}

@keyframes float-rotate {
  0%,
  100% {
    transform: translateY(0px) rotate(-5deg);
  }

  50% {
    transform: translateY(-15px) rotate(-3deg);
  }
}

@keyframes float-rotate-reverse {
  0%,
  100% {
    transform: translateY(0px) rotate(10deg);
  }

  50% {
    transform: translateY(-10px) rotate(8deg);
  }
}

.perspective-1000 {
  perspective: 1000px;
}

.animate-float-slow {
  animation: float 6s ease-in-out infinite;
}

.animate-float-medium {
  animation: float-rotate 7s ease-in-out infinite;
  animation-delay: 1s;
}

.animate-float-fast {
  animation: float-rotate-reverse 5s ease-in-out infinite;
  animation-delay: 2s;
}
</style>
