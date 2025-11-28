# TXT阅读器迁移文档

## 概述

本文档描述了从frontend项目的TXT阅读器迁移到bard-library-reader项目的完整方案。目标是创建一个更优雅、响应式的阅读器，避免frontend中存在的大量props传递和重复代码问题。

## 当前问题分析

### Frontend TXT阅读器的架构问题

1. **组件过大**：`TxtReader.vue` 超过1000行代码，承担了太多职责
2. **Props传递复杂**：大量数据通过props在组件间传递，如：
   ```vue
   <MobileSearchDrawer
     ref="mobileSearchDrawerRef"
     :visible="mobileSearchVisible"
     :current-chapter-content="content"
     :current-chapter-index="currentChapterIndex"
     :chapters="chapters"
     :sentences="sentences"
     class="md:hidden"
     @close="handleMobileSearchClose"
     @jump-to-result="handleJumpToSearchResult"
     @search-chapter="handleChapterSearch"
     @search-global="handleGlobalSearch"
   />
   ```
3. **移动端/PC端分离**：使用不同的组件和逻辑处理
4. **状态管理分散**：状态散布在主组件中，难以维护

## 新架构设计

### 核心原则

1. **单一职责**：每个组件只负责一个功能
2. **组合式API**：使用Vue 3的组合式API组织逻辑
3. **响应式设计**：一套代码适配所有设备
4. **状态集中管理**：使用Pinia进行状态管理
5. **事件驱动**：通过事件总线或provide/inject减少props

### 架构层次

```
┌─────────────────────────────────────┐
│          TxtReader.vue              │  主容器组件
│  (轻量级，负责布局和协调)          │
├─────────────────────────────────────┤
│  ┌─────────────────────────────────┐ │
│  │     ReaderProvider.vue         │ │  上下文提供者
│  │  (状态管理，事件处理)          │ │
│  └─────────────────────────────────┘ │
├─────────────────────────────────────┤
│  ┌─────────────────────────────────┐ │
│  │   ReaderLayout.vue             │ │  布局组件
│  │  (响应式布局，侧边栏等)        │ │
│  └─────────────────────────────────┘ │
├─────────────────────────────────────┤
│  ┌─────────────────────────────────┐ │
│  │   ReaderContent.vue            │ │  内容显示
│  │  (文本渲染，选区处理)          │ │
│  └─────────────────────────────────┘ │
├─────────────────────────────────────┤
│  ┌─────────────────────────────────┐ │
│  │   ReaderControls.vue           │ │  控制面板
│  │  (设置，导航，搜索等)          │ │
│  └─────────────────────────────────┘ │
└─────────────────────────────────────┘
```

### 状态管理设计

#### 使用Pinia Store

```typescript
// stores/reader.ts
export const useReaderStore = defineStore('reader', () => {
  // 基础状态
  const fileId = ref<number>(0)
  const bookId = ref<number>(0)
  const chapters = ref<Chapter[]>([])
  const currentChapterIndex = ref<number | null>(null)
  const content = ref('')
  const sentences = ref<string[]>([])

  // UI状态
  const isLoading = ref(false)
  const searchVisible = ref(false)
  const settingsVisible = ref(false)

  // 阅读设置
  const settings = ref({
    fontSize: 16,
    lineHeight: 1.7,
    contentWidth: 720,
    theme: 'light' as 'light' | 'sepia' | 'dark'
  })

  // 搜索状态
  const searchState = ref({
    keyword: '',
    caseSensitive: false,
    wholeWord: false,
    results: [] as SearchResult[]
  })

  // 书签和高亮
  const bookmarks = ref<Bookmark[]>([])
  const markRanges = ref(new Map<number, HighlightRange[]>())

  // Actions
  const loadChapters = async () => { /* ... */ }
  const openChapter = async (index: number) => { /* ... */ }
  const performSearch = async () => { /* ... */ }

  return {
    // 状态
    fileId, bookId, chapters, currentChapterIndex, content, sentences,
    isLoading, searchVisible, settingsVisible, settings, searchState,
    bookmarks, markRanges,

    // Actions
    loadChapters, openChapter, performSearch
  }
})
```

#### 使用Provide/Inject模式

```vue
<!-- ReaderProvider.vue -->
<template>
  <div class="reader-provider">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { provide } from 'vue'
import { useReaderStore } from '@/stores/reader'

const store = useReaderStore()

// 提供store和常用方法
provide('readerStore', store)
provide('readerActions', {
  openChapter: store.openChapter,
  toggleSearch: () => store.searchVisible = !store.searchVisible,
  // ... 其他方法
})
</script>
```

### 组件设计

#### 1. TxtReader.vue (主容器)

```vue
<template>
  <ReaderProvider>
    <ReaderLayout>
      <template #sidebar>
        <ReaderNavigation />
      </template>

      <template #content>
        <ReaderContent />
      </template>

      <template #controls>
        <ReaderControls />
      </template>
    </ReaderLayout>
  </ReaderProvider>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import ReaderProvider from './components/ReaderProvider.vue'
import ReaderLayout from './components/ReaderLayout.vue'
import ReaderNavigation from './components/ReaderNavigation.vue'
import ReaderContent from './components/ReaderContent.vue'
import ReaderControls from './components/ReaderControls.vue'
import { useReaderStore } from '@/stores/reader'

const route = useRoute()
const store = useReaderStore()

onMounted(async () => {
  const fileId = Number(route.params.id)
  store.fileId = fileId
  await store.loadChapters()
})
</script>
```

#### 2. ReaderLayout.vue (响应式布局)

```vue
<template>
  <div class="reader-layout">
    <!-- 桌面端：三栏布局 -->
    <div class="hidden md:flex">
      <aside class="sidebar">
        <slot name="sidebar" />
      </aside>
      <main class="content">
        <slot name="content" />
      </main>
      <aside class="controls">
        <slot name="controls" />
      </aside>
    </div>

    <!-- 移动端：全屏布局 -->
    <div class="md:hidden">
      <main class="mobile-content">
        <slot name="content" />
      </main>

      <!-- 移动端浮层 -->
      <ReaderMobileOverlay v-if="showMobileOverlay">
        <slot name="sidebar" />
      </ReaderMobileOverlay>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useReaderStore } from '@/stores/reader'

const store = useReaderStore()
const showMobileOverlay = computed(() =>
  store.searchVisible || store.settingsVisible
)
</script>
```

#### 3. ReaderContent.vue (内容显示)

```vue
<template>
  <div class="reader-content" :style="contentStyle">
    <template v-if="store.isLoading">
      <ReaderSkeleton />
    </template>

    <template v-else-if="!store.content">
      <ReaderEmpty />
    </template>

    <template v-else>
      <TxtContentRenderer
        :content="store.content"
        :sentences="store.sentences"
        :mark-ranges="store.markRanges"
        :search-highlight="store.searchState"
        @selection="handleSelection"
        @mark-click="handleMarkClick"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue'
import { useReaderStore } from '@/stores/reader'

const store = useReaderStore()

const contentStyle = computed(() => ({
  '--font-size': `${store.settings.fontSize}px`,
  '--line-height': store.settings.lineHeight,
  '--content-width': `${store.settings.contentWidth}px`,
  '--theme-bg': themeColors[store.settings.theme].bg,
  '--theme-fg': themeColors[store.settings.theme].fg
}))
</script>
```

## 迁移计划

### 第一阶段：核心架构搭建

1. **创建基础Store** (`stores/reader.ts`)
   - 定义所有状态和基本actions
   - 实现章节加载逻辑

2. **创建Provider组件** (`components/ReaderProvider.vue`)
   - 使用provide/inject模式
   - 提供store和常用方法

3. **创建Layout组件** (`components/ReaderLayout.vue`)
   - 实现响应式布局
   - 处理移动端和桌面端差异

### 第二阶段：内容显示功能

1. **创建Content组件** (`components/ReaderContent.vue`)
   - 文本渲染
   - 选区处理
   - 基础导航

2. **迁移TxtContentRenderer**
   - 从frontend复制并简化
   - 移除不必要的props

### 第三阶段：导航和控制功能

1. **创建Navigation组件** (`components/ReaderNavigation.vue`)
   - 章节列表
   - 书签管理
   - 缓存状态

2. **创建Controls组件** (`components/ReaderControls.vue`)
   - 阅读设置
   - 搜索面板
   - 工具栏

### 第四阶段：高级功能

1. **搜索功能**
   - 章节内搜索
   - 全文搜索（需要缓存）

2. **书签和高亮**
   - 选区高亮
   - 书签管理
   - 笔记功能

3. **缓存管理**
   - 离线缓存
   - 缓存统计

### 第五阶段：优化和测试

1. **性能优化**
   - 虚拟滚动（长文本）
   - 懒加载
   - 内存管理

2. **用户体验**
   - 手势支持
   - 键盘快捷键
   - 无障碍支持

3. **兼容性测试**
   - 多设备测试
   - 浏览器兼容性

## 技术决策

### 1. 状态管理：Pinia vs 组合式API

选择Pinia而不是纯组合式API的原因：
- 更好的TypeScript支持
- 跨组件状态共享
- 插件生态系统
- 调试工具支持

### 2. 组件通信：Provide/Inject vs Props

选择Provide/Inject的原因：
- 减少props drilling
- 更灵活的组件组合
- 更好的可维护性
- 支持动态注入

### 3. 响应式设计：统一组件 vs 分离组件

选择统一组件的原因：
- 代码复用
- 维护成本低
- 功能一致性
- 测试简化

## 风险评估

### 高风险项

1. **状态管理复杂度**
   - 风险：Pinia store可能变得过于庞大
   - 缓解：按功能模块拆分store，使用组合式store

2. **性能问题**
   - 风险：长文本渲染性能
   - 缓解：实现虚拟滚动，文本分块加载

3. **移动端适配**
   - 风险：响应式布局复杂
   - 缓解：使用CSS Grid/Flexbox，充分测试

### 中风险项

1. **向后兼容**
   - 风险：API接口变更影响现有功能
   - 缓解：保持API兼容性，渐进式迁移

2. **用户习惯**
   - 风险：UI变更影响用户体验
   - 缓解：保持核心交互一致，A/B测试

## 验收标准

### 功能完整性
- [ ] 章节导航和内容显示
- [ ] 基础阅读设置（字体、主题）
- [ ] 章节内搜索
- [ ] 书签和高亮功能
- [ ] 缓存管理
- [ ] 进度保存

### 性能指标
- [ ] 首屏加载时间 < 2s
- [ ] 章节切换时间 < 500ms
- [ ] 搜索响应时间 < 200ms
- [ ] 内存使用 < 100MB（长文本）

### 用户体验
- [ ] 响应式布局适配所有设备
- [ ] 无障碍支持（键盘导航、屏幕阅读器）
- [ ] 离线缓存功能正常
- [ ] 手势操作流畅

### 代码质量
- [ ] 组件平均大小 < 200行
- [ ] 测试覆盖率 > 80%
- [ ] TypeScript严格模式
- [ ] ESLint规则通过

## 实施时间表

- **第一阶段**：1-2周（核心架构）
- **第二阶段**：1周（内容显示）
- **第三阶段**：2周（导航和控制）
- **第四阶段**：2周（高级功能）
- **第五阶段**：1周（优化和测试）

总计：7-8周

## 后续规划

1. **PDF阅读器迁移**：基于相同架构实现PDF阅读
2. **EPUB阅读器迁移**：扩展架构支持EPUB格式
3. **协同阅读**：添加多人阅读和讨论功能
4. **AI辅助**：智能摘要、翻译、语音播报等功能</content>
<parameter name="filePath">D:\Github\bard-library\bard-library-reader\docs\TXT_READER_MIGRATION.md