<template>
  <div class="flex flex-wrap gap-3 mt-8 items-center">
    <!-- 主阅读/格式选择区域 -->
    <div v-if="txtFiles.length > 0" class="flex flex-row flex-wrap">
      <el-button type="primary" size="large" @click="$emit('read', { type: 'txt', file: txtFiles[0] })" :class="nonTxtFiles.length > 0 ? 'rounded-r-none' : ''">
        <span class="material-symbols-outlined mr-1">play_arrow</span>
        <template v-if="continueTarget">继续阅读</template>
        <template v-else>开始阅读</template>
      </el-button>
      <div v-if="nonTxtFiles.length > 0" class="flex flex-row flex-wrap">
        <el-dropdown trigger="click" class="ml-0">
          <span class="el-dropdown-link">
            <el-button type="primary" size="large" class="rounded-l-none border-l-white hover:border-l-white px-0">
              <span class="material-symbols-outlined">arrow_drop_down</span>
            </el-button>
          </span>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item v-for="f in nonTxtFiles" :key="f.id" @click="$emit('read', { type: f.format, file: f })">
                <span>{{ (f.format || '').toUpperCase() || '文件' }}</span>
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>

    <template v-else-if="files.length === 1">
      <el-button type="primary" size="large" @click="$emit('read', { type: files[0].format, file: files[0] })">
        <span class="material-symbols-outlined mr-1">{{ fileReadIcon(files[0]) }}</span>
        {{ fileReadText(files[0]) }}
      </el-button>
    </template>

    <div v-else-if="files.length > 1" class="flex flex-row flex-wrap">
      <el-button type="primary" size="large" @click="$emit('read', { type: files[0].format, file: files[0] })" class="rounded-r-none">
        <span class="material-symbols-outlined mr-1">{{ fileReadIcon(files[0]) }}</span>
        {{ fileReadText(files[0]) }}
      </el-button>
      <div class="flex flex-row flex-wrap">
        <el-dropdown trigger="click" class="ml-0">
          <span class="el-dropdown-link">
            <el-button type="primary" size="large" class="rounded-l-none border-l-white hover:border-l-white px-0">
              <span class="material-symbols-outlined">arrow_drop_down</span>
            </el-button>
          </span>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item v-for="f in files.slice(1)" :key="f.id" @click="$emit('read', { type: f.format, file: f })">
                <span>{{ (f.format || '').toUpperCase() || '文件' }}</span>
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>

    <template v-else>
      <el-button type="primary" size="large" disabled>
        <span class="material-symbols-outlined mr-1">block</span>
        暂无资源
      </el-button>
    </template>

    <!-- 已读 标记 -->
    <div v-if="isLoggedIn">
      <el-button v-if="!isReadMark" type="default" size="large" @click="$emit('toggle-read')">标记已读</el-button>
      <el-button v-else type="success" plain size="large" @click="$emit('toggle-read')">已读</el-button>
    </div>

    <!-- 下载 UI -->
    <el-button size="large" v-if="files.length <= 1" :disabled="files.length === 0" class="mb-2 md:mb-0" @click="$emit('download', files[0]?.id)">
      <span class="material-symbols-outlined mr-1">download</span>
      {{ files.length > 0 ? (files[0].format || '').toUpperCase() + ' ' + humanSize(files[0].size || 0) : '下载' }}
    </el-button>

    <div class="flex flex-row flex-wrap" v-if="files.length > 1">
      <el-button size="large" class="rounded-r-none md:mb-0" @click="$emit('download', files[0].id)">
        <span class="material-symbols-outlined mr-1">download</span>
        {{ files.length > 0 ? (files[0].format || '').toUpperCase() + ' ' + humanSize(files[0].size || 0) : '下载' }}
      </el-button>
      <el-dropdown trigger="click" class="ml-0">
        <span class="el-dropdown-link">
          <el-button size="large" class="rounded-l-none border-l-0 px-0">
            <span class="material-symbols-outlined">arrow_drop_down</span>
          </el-button>
        </span>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item v-for="f in files" :key="f.id" @click="$emit('download', f.id)">
              <span>{{ (f.format || '').toUpperCase() || '文件' }}</span>
              <span v-if="f.size" class="text-gray-500 ml-1">{{ humanSize(f.size) }}</span>
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>

    <!-- 发送 & 编辑 链接：通过事件或插槽处理 -->
    <div class="flex flex-row">
      <el-button size="large" class="rounded-r-none" :disabled="files.length === 0" @click="$emit('send')">
        发送到
        <span class="material-symbols-outlined ml-1">mail</span>
      </el-button>
      <el-dropdown trigger="click" class="ml-0">
        <span class="el-dropdown-link">
          <el-button size="large" class="rounded-l-none border-l-0 px-0" :disabled="files.length === 0">
            <span class="material-symbols-outlined">arrow_drop_down</span>
          </el-button>
        </span>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item>
              <span class="material-symbols-outlined">aod_tablet</span>
              <span>Kindle</span>
            </el-dropdown-item>
            <el-dropdown-item>
              <span class="material-symbols-outlined">add_to_drive</span>
              <span>GoogleDrive</span>
            </el-dropdown-item>
            <el-dropdown-item>
              <span class="material-symbols-outlined">mail</span>
              <span>邮箱</span>
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>

    <slot name="buttons" />
  </div>
</template>

<script setup lang="ts">
import type { FileRec } from '@/api/types'
import { computed } from 'vue'

const props = defineProps<{
  files: FileRec[]
  continueTarget: any
  isLoggedIn: boolean
  isReadMark: number | boolean
}>()

const files = computed(() => props.files || [])
const txtFiles = computed(() => files.value.filter(f => (f.format || '').toLowerCase() === 'txt'))
const nonTxtFiles = computed(() => files.value.filter(f => (f.format || '').toLowerCase() !== 'txt'))
const continueTarget = computed(() => props.continueTarget)
const isLoggedIn = computed(() => props.isLoggedIn)
const isReadMark = computed(() => props.isReadMark)

function fileReadIcon(f: FileRec) {
  const fmt = (f.format || '').toLowerCase()
  if (fmt === 'epub') return 'auto_stories'
  if (fmt === 'pdf') return 'picture_as_pdf'
  if (fmt === 'txt') return 'menu_book'
  return 'play_arrow'
}

function fileReadText(f: FileRec) {
  const fmt = (f.format || '').toLowerCase()
  if (fmt === 'epub') return 'EPUB 阅读'
  if (fmt === 'pdf') return 'PDF 预览'
  if (fmt === 'txt') return continueTarget.value ? '继续阅读' : '开始阅读'
  return '开始阅读'
}

function humanSize(n: number) {
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let i = 0
  let v = n
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++ }
  return `${v.toFixed(1)} ${units[i]}`
}
</script>
