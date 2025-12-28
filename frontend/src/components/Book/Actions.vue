<template>
  <div class="flex flex-wrap gap-3 mt-8 items-center">
    <!-- 主阅读/格式选择区域 -->
    <div v-if="txtFiles.length > 0" class="flex flex-row flex-wrap">
      <el-button
        v-if="nonTxtFiles.length === 0"
        type="primary"
        size="large"
        @click="$emit('read', { type: 'txt', file: txtFiles[0] })"
      >
        <span class="material-symbols-outlined mr-1">play_arrow</span>
        <template v-if="continueTarget">继续阅读</template>
        <template v-else>开始阅读</template>
      </el-button>

      <SplitButton
        v-else
        type="primary"
        size="large"
        :icon="continueTarget ? 'play_arrow' : 'play_arrow'"
        :label="continueTarget ? '继续阅读' : '开始阅读'"
        :primary-value="{ type: 'txt', file: txtFiles[0] }"
        @click="$emit('read', $event)"
        @command="handleReadCommand"
      >
        <template #dropdown>
          <el-dropdown-item
            v-for="f in nonTxtFiles"
            :key="f.id"
            :command="{ type: f.format || 'unknown', file: f }"
          >
            <span>{{ getFileFormatLabel(f) }}</span>
          </el-dropdown-item>
        </template>
      </SplitButton>
    </div>

    <template v-else-if="files.length === 1">
      <el-button
        type="primary"
        size="large"
        @click="$emit('read', { type: files[0].format || 'unknown', file: files[0] })"
      >
        <span class="material-symbols-outlined mr-1">{{ getFileIcon(files[0]) }}</span>
        {{ getFileReadText(files[0]) }}
      </el-button>
    </template>

    <SplitButton
      v-else-if="files.length > 1"
      type="primary"
      size="large"
      :icon="getFileIcon(files[0])"
      :label="getFileReadText(files[0])"
      :primary-value="{ type: files[0].format || 'unknown', file: files[0] }"
      @click="$emit('read', $event)"
      @command="handleReadCommand"
    >
      <template #dropdown>
        <el-dropdown-item
          v-for="f in files.slice(1)"
          :key="f.id"
          :command="{ type: f.format || 'unknown', file: f }"
        >
          <span>{{ getFileFormatLabel(f) }}</span>
        </el-dropdown-item>
      </template>
    </SplitButton>

    <template v-else>
      <el-button type="primary" size="large" disabled>
        <span class="material-symbols-outlined mr-1">block</span>
        暂无资源
      </el-button>
    </template>

    <!-- 已读标记 -->
    <div v-if="isLoggedIn">
      <el-button v-if="!isReadMark" type="default" size="large" @click="$emit('toggle-read')">
        标记已读
      </el-button>
      <el-button v-else type="success" plain size="large" @click="$emit('toggle-read')">
        已读
      </el-button>
    </div>

    <!-- 下载 UI -->
    <el-button
      v-if="files.length <= 1"
      size="large"
      :disabled="files.length === 0"
      class="mb-2 md:mb-0"
      @click="$emit('download', files[0]?.id)"
    >
      <span class="material-symbols-outlined mr-1">download</span>
      {{ files.length > 0 ? formatFileInfo(files[0]) : '下载' }}
    </el-button>

    <SplitButton
      v-else
      size="large"
      icon="download"
      :label="formatFileInfo(files[0])"
      :primary-value="files[0].id"
      dropdown-button-class="mb-2 md:mb-0"
      @click="$emit('download', $event)"
      @command="$emit('download', $event)"
    >
      <template #dropdown>
        <el-dropdown-item v-for="f in files" :key="f.id" :command="f.id">
          <span>{{ getFileFormatLabel(f) }}</span>
          <span v-if="f.size" class="text-gray-500 ml-1">{{ humanSize(f.size) }}</span>
        </el-dropdown-item>
      </template>
    </SplitButton>

    <!-- 发送到 -->
    <Share :files="files" :book-id="bookId" :user="user" />

    <slot name="buttons" />
  </div>
</template>

<script setup lang="ts">
import type { FileRec } from '@/api/types'
import { computed } from 'vue'
import Share from './Share.vue'
import SplitButton from '@/components/Common/SplitButton.vue'
import {
  humanSize,
  getFileIcon,
  getFileReadText,
  formatFileInfo,
  getFileFormatLabel,
} from '@/utils/file'

const props = defineProps<{
  files: FileRec[]
  continueTarget: any
  isLoggedIn: boolean
  isReadMark: number | boolean
  bookId: number
  user?: any
}>()

const emit = defineEmits<{
  read: [payload: { type: string; file: FileRec }]
  download: [fileId: number]
  'toggle-read': []
}>()

const txtFiles = computed(() => props.files.filter(f => (f.format || '').toLowerCase() === 'txt'))
const nonTxtFiles = computed(() =>
  props.files.filter(f => (f.format || '').toLowerCase() !== 'txt'),
)

function handleReadCommand(command: { type: string; file: FileRec }) {
  emit('read', command)
}
</script>
