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
          class="lg:w-1/2 w-full relative h-[650px] flex items-center justify-center perspective-1000"
        >
          <!-- 1. Laptop -->
          <div class="absolute animate-float-slow w-[95%] max-w-[600px] z-10 -translate-y-20">
            <!-- 加深加厚的设备外框 -->
            <div
              class="relative bg-slate-950 border-[2px] border-slate-800 rounded-t-2xl p-2 shadow-2xl flex flex-col group transition-all duration-500"
            >
              <!-- 顶部系统栏 -->
              <div
                class="h-8 flex items-center px-4 bg-slate-900/50 rounded-t-xl mb-1 border-b border-slate-800/50"
              >
                <div class="flex gap-2">
                  <div class="w-3 h-3 rounded-full bg-red-500/20 border border-red-500/40"></div>
                  <div
                    class="w-3 h-3 rounded-full bg-amber-500/20 border border-amber-500/40"
                  ></div>
                  <div
                    class="w-3 h-3 rounded-full bg-emerald-500/20 border border-emerald-500/40"
                  ></div>
                </div>
              </div>

              <!-- 应用内容 -->
              <div
                class="flex-1 bg-slate-900 rounded-lg overflow-hidden flex border border-indigo-500/30"
              >
                <!-- 侧边栏 -->
                <div
                  class="w-16 bg-slate-950 border-r border-slate-800 flex flex-col items-center py-6 gap-6"
                >
                  <div
                    class="w-8 h-8 rounded-lg bg-indigo-500 shadow-lg shadow-indigo-500/50"
                  ></div>
                  <div class="w-6 h-6 rounded bg-slate-800"></div>
                </div>

                <div
                  class="flex-1 p-6 flex flex-col gap-6 bg-gradient-to-br from-slate-900 to-slate-800 overflow-hidden"
                >
                  <!-- 上部：左封面右信息 -->
                  <div class="flex gap-6">
                    <div
                      class="w-32 h-44 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-700 shadow-2xl border border-white/5 shrink-0"
                    ></div>
                    <!-- 封面 -->
                    <div class="flex-1 space-y-4 pt-2">
                      <div class="h-6 w-3/4 bg-slate-400/60 rounded"></div>
                      <!-- 标题 -->
                      <div class="h-3 w-1/4 bg-indigo-400/30 rounded-full"></div>
                      <!-- 元数据 -->
                      <div class="space-y-2">
                        <div class="h-2 w-full bg-slate-700/50 rounded"></div>
                        <div class="h-2 w-5/6 bg-slate-700/50 rounded"></div>
                      </div>
                    </div>
                  </div>
                  <!-- 下部：章节、按钮、进度 -->
                  <div class="flex-1 flex gap-6">
                    <div class="flex-1 space-y-2">
                      <!-- 章节列表 -->
                      <div
                        class="h-8 w-full bg-slate-800/80 rounded border border-slate-700/50"
                      ></div>
                      <div
                        class="h-8 w-full bg-slate-800/80 rounded border border-slate-700/50"
                      ></div>
                      <div
                        class="h-8 w-full bg-slate-800/80 rounded border border-slate-700/50"
                      ></div>
                    </div>
                    <div class="w-48 space-y-4">
                      <div
                        class="h-12 w-full bg-indigo-500 rounded-xl shadow-lg flex items-center justify-center"
                      >
                        <!-- 阅读按钮 -->
                        <div class="w-16 h-2 bg-slate-900/20 rounded-full"></div>
                      </div>
                      <div class="space-y-2">
                        <div class="flex justify-between text-[10px] text-amber-500 font-bold">
                          <span>82%</span>
                        </div>
                        <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                          <div class="h-full w-[82%] bg-amber-500"></div>
                          <!-- 进度条 -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div
              class="h-3 bg-slate-800 border-x border-b border-slate-700 rounded-b-2xl mx-auto w-[99%] shadow-2xl"
            ></div>
          </div>

          <!-- 2. Tablet -->
          <div
            class="absolute -right-10 bottom-24 w-[240px] md:w-[320px] animate-float-medium z-20"
          >
            <div
              class="bg-slate-950 border-[2px] border-slate-800 rounded-[2.5rem] p-3 shadow-2xl aspect-[3/4] flex flex-col transform rotate-[-4deg] hover:rotate-0 transition-all duration-700 group"
            >
              <div class="h-6 flex justify-center items-center">
                <div class="w-1.5 h-1.5 rounded-full bg-slate-800"></div>
              </div>

              <div
                class="flex-1 bg-slate-900 rounded-[1.8rem] overflow-hidden flex flex-col border border-indigo-500/30"
              >
                <!-- 上部：左封面右信息 -->
                <div class="p-5 flex gap-5 border-b border-slate-800/50 bg-slate-900/50">
                  <div
                    class="w-24 h-32 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-700 shadow-xl border border-white/5 shrink-0"
                  ></div>
                  <!-- 封面 -->
                  <div class="flex-1 space-y-3 pt-1">
                    <div class="h-5 w-full bg-slate-400/60 rounded-full"></div>
                    <!-- 标题 -->
                    <div class="h-2.5 w-1/2 bg-indigo-400/30 rounded-full"></div>
                    <div class="h-2 w-full bg-slate-800 rounded"></div>
                    <div class="h-2 w-2/3 bg-slate-800 rounded"></div>
                  </div>
                </div>
                <!-- 下部：章节、按钮、进度 -->
                <div class="flex-1 p-5 flex flex-col gap-4">
                  <div class="space-y-2 flex-1">
                    <!-- 章节列表 -->
                    <div
                      class="h-10 w-full bg-slate-800/80 rounded-lg border border-slate-700/50"
                    ></div>
                    <div
                      class="h-10 w-full bg-slate-800/80 rounded-lg border border-slate-700/50"
                    ></div>
                    <div
                      class="h-10 w-full bg-slate-800/80 rounded-lg border border-slate-700/50"
                    ></div>
                  </div>
                  <div class="space-y-3">
                    <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                      <div class="h-full w-[45%] bg-amber-500"></div>
                      <!-- 进度条 -->
                    </div>
                    <div
                      class="h-12 w-full bg-indigo-500 rounded-[1rem] shadow-lg flex items-center justify-center"
                    >
                      <!-- 阅读按钮 -->
                      <div class="w-20 h-2 bg-slate-900/20 rounded-full"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Mobile -->
          <div class="absolute -left-12 -bottom-4 w-[170px] md:w-[200px] animate-float-fast z-30">
            <div
              class="bg-slate-950 border-[2px] border-slate-800 rounded-[3rem] p-3 shadow-2xl transform rotate-[6deg] hover:rotate-0 transition-all duration-700 group"
            >
              <div
                class="absolute top-5 left-1/2 -translate-x-1/2 w-16 h-4 bg-black rounded-full z-40 border border-white/5"
              ></div>

              <div
                class="bg-slate-900 rounded-[2.5rem] h-[380px] overflow-hidden flex flex-col border border-indigo-500/40"
              >
                <!-- 上部：左封面右信息 (手机端紧凑化) -->
                <div class="p-4 pt-10 flex gap-4 bg-slate-900/80">
                  <div
                    class="w-16 h-24 rounded-md bg-gradient-to-br from-indigo-500 to-indigo-700 shadow-lg border border-white/5 shrink-0"
                  ></div>
                  <!-- 封面 -->
                  <div class="flex-1 space-y-3">
                    <div class="h-4 w-full bg-slate-400/60 rounded-full"></div>
                    <!-- 标题 -->
                    <div class="h-2 w-1/2 bg-indigo-400/30 rounded-full"></div>
                    <div class="h-1.5 w-full bg-slate-800 rounded"></div>
                  </div>
                </div>
                <!-- 下部：章节、进度、按钮 -->
                <div class="flex-1 p-4 flex flex-col gap-4">
                  <div class="space-y-2 flex-1">
                    <!-- 章节列表 -->
                    <div
                      class="h-8 w-full bg-slate-800/80 rounded-md border border-slate-700/50"
                    ></div>
                    <div
                      class="h-8 w-full bg-slate-800/80 rounded-md border border-slate-700/50"
                    ></div>
                  </div>
                  <div class="space-y-3">
                    <div
                      class="flex justify-between items-center text-[8px] text-amber-500 font-bold"
                    >
                      <span>82%</span>
                    </div>
                    <div class="h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                      <div class="h-full w-[82%] bg-amber-500"></div>
                      <!-- 进度条 -->
                    </div>
                    <div
                      class="h-10 w-full bg-indigo-500 rounded-full shadow-lg flex items-center justify-center"
                    >
                      <!-- 阅读按钮 -->
                      <div class="w-12 h-1.5 bg-slate-900/20 rounded-full"></div>
                    </div>
                  </div>
                </div>
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
