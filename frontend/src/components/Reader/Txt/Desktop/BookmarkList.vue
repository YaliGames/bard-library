<template>
  <div class="h-full">
    <el-empty v-if="!isLoggedIn" description="登录后可查看书签"></el-empty>

    <BookmarkCore
      v-else
      :bookmarks="bookmarks"
      :chapters="chapters"
      @jump-to-bookmark="emit('jump', $event)"
      @remove-bookmark="emit('remove', $event)"
    >
      <template
        #default="{
          hasBookmarks,
          bookmarks: displayBookmarks,
          sortMode,
          setSortMode,
          getBookmarkChapter,
          getBookmarkPreview,
          handleBookmarkClick,
          handleRemoveBookmark,
        }"
      >
        <el-empty v-if="!hasBookmarks" description="当前文件暂无书签"></el-empty>
        <div v-else class="max-h-[60vh] overflow-auto space-y-1">
          <div
            class="sticky top-0 z-10 bg-white/80 backdrop-blur px-2 pt-1 pb-2 flex items-center justify-between"
          >
            <span class="text-xs text-gray-500">排序</span>
            <el-radio-group
              :model-value="sortMode"
              @update:model-value="setSortMode"
              size="small"
            >
              <el-radio-button label="article">章节顺序</el-radio-button>
              <el-radio-button label="created">创建时间</el-radio-button>
            </el-radio-group>
          </div>
          <div
            v-for="b in displayBookmarks"
            :key="b.id"
            class="group relative px-2 py-2 rounded-md cursor-pointer transition hover:bg-gray-100 dark:hover:bg-gray-700"
            @click="handleBookmarkClick(b)"
          >
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
              {{ getBookmarkChapter(b).title }}
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">
              {{ getBookmarkPreview(b) }}
            </div>
            <el-button
              @click.stop="handleRemoveBookmark(b)"
              type="danger"
              circle
              text
              class="!absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <template #icon>
                <span class="material-symbols-outlined text-sm">delete</span>
              </template>
            </el-button>
          </div>
        </div>
      </template>
    </BookmarkCore>
  </div>
</template>

<script setup lang="ts">
import BookmarkCore from '@/components/Reader/Txt/Core/BookmarkCore.vue'
import type { Bookmark } from '@/api/types'

interface Chapter {
  index: number
  title?: string | null
  offset?: number
  length?: number
}

defineProps<{
  bookmarks: Bookmark[]
  chapters: Chapter[]
  isLoggedIn: boolean
}>()

const emit = defineEmits<{
  (e: 'jump', b: Bookmark): void
  (e: 'remove', b: Bookmark): void
}>()
</script>
