<template>
  <header
    class="relative bg-gradient-to-r from-blue-600 to-indigo-700 text-white py-28 px-6 md:px-12 overflow-hidden"
  >
    <div class="absolute inset-0 bg-black opacity-40"></div>
    <div class="container mx-auto relative z-10 grid grid-cols-1 lg:grid-cols-3">
      <div class="justify-self-center mb-10 lg:mb-0">
        <img src="@/images/logo-white.svg" class="w-64 mx-auto lg:mx-0" />
      </div>
      <div class="lg:col-span-2 text-left">
        <h1
          class="text-5xl md:text-6xl font-extrabold mb-6 leading-tight tracking-wide text-shadow-lg"
        >
          你的线上图书馆
        </h1>
        <p class="text-xl md:text-2xl mb-8 leading-relaxed opacity-80">
          管理多格式图书，享受便捷的在线阅读体验，随时随地开始您的阅读旅程。
        </p>
        <router-link
          to="/login"
          class="bg-yellow-500 text-black py-3 px-8 rounded-lg text-xl font-semibold hover:bg-yellow-600 transition duration-300 transform hover:scale-105"
        >
          立即开始
        </router-link>
      </div>
    </div>
  </header>

  <!-- 功能说明 Section -->
  <section class="py-16 bg-gray-50">
    <div class="container mx-auto text-center">
      <h2 class="text-3xl font-semibold mb-10">核心功能</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-10">
        <!-- 功能卡片 -->
        <div class="bg-white shadow-lg p-6 rounded-lg hover:shadow-xl transition duration-300">
          <span class="material-symbols-outlined text-4xl text-blue-500 mb-4">book_2</span>
          <h3 class="text-2xl font-semibold mb-4">多格式图书管理</h3>
          <p class="text-gray-600">
            支持多种格式的图书管理，无论是电子书、PDF，还是文本文件，都能轻松导入和组织。
          </p>
        </div>
        <div class="bg-white shadow-lg p-6 rounded-lg hover:shadow-xl transition duration-300">
          <span class="material-symbols-outlined text-4xl text-blue-500 mb-4">auto_stories</span>
          <h3 class="text-2xl font-semibold mb-4">在线阅读</h3>
          <p class="text-gray-600">
            无缝的在线阅读体验，自动保存进度，让你随时继续阅读，流畅无干扰。
          </p>
        </div>
        <div class="bg-white shadow-lg p-6 rounded-lg hover:shadow-xl transition duration-300">
          <span class="material-symbols-outlined text-4xl text-blue-500 mb-4">file_download</span>
          <h3 class="text-2xl font-semibold mb-4">下载功能</h3>
          <p class="text-gray-600">支持将图书下载到本地，随时阅读，无需依赖网络。</p>
        </div>
        <div class="bg-white shadow-lg p-6 rounded-lg hover:shadow-xl transition duration-300">
          <span class="material-symbols-outlined text-4xl text-blue-500 mb-4">tag</span>
          <h3 class="text-2xl font-semibold mb-4">元数据刮削</h3>
          <p class="text-gray-600">自动获取书籍的元数据，让你的图书库更加完整和智能。</p>
        </div>
        <div class="bg-white shadow-lg p-6 rounded-lg hover:shadow-xl transition duration-300">
          <span class="material-symbols-outlined text-4xl text-blue-500 mb-4">manage_accounts</span>
          <h3 class="text-2xl font-semibold mb-4">用户管理</h3>
          <p class="text-gray-600">支持用户注册、登录、管理个人书架以及查看借阅历史。</p>
        </div>
        <div class="bg-white shadow-lg p-6 rounded-lg hover:shadow-xl transition duration-300">
          <span class="material-symbols-outlined text-4xl text-blue-500 mb-4">rss_feed</span>
          <h3 class="text-2xl font-semibold mb-4">RSS 订阅</h3>
          <p class="text-gray-600">通过 RSS 订阅功能，获取最新的图书更新和平台新闻。</p>
        </div>
      </div>
    </div>
  </section>

  <!-- 文档入口 Section -->
  <section class="py-16 bg-white">
    <div class="container mx-auto text-center">
      <h2 class="text-3xl font-semibold mb-6">了解如何使用</h2>
      <p class="text-lg text-gray-700 mb-8">
        Bard Library 是一个开源的在线图书管理平台，阅读文档以了解更多信息。
      </p>
      <a
        href="https://bard.yaligames.com/docs/"
        target="_blank"
        class="bg-blue-600 text-white py-3 px-8 rounded-lg text-xl font-semibold hover:bg-blue-700 transition duration-300 transform hover:scale-105"
      >
        查看文档
      </a>
    </div>
  </section>
</template>
<script setup lang="ts"></script>
