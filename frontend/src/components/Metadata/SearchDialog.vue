<template>
  <el-dialog
    v-model="visible"
    :title="title || '从网络刮削元数据'"
    width="840px"
    :close-on-click-modal="false"
    @closed="onClosed"
  >
    <div class="flex gap-3 items-center mb-3">
      <el-select v-model="providerId" placeholder="选择平台" style="width: 200px" :disabled="busy">
        <el-option v-for="p in providers" :key="p.id" :label="p.name" :value="p.id" />
      </el-select>
      <el-input
        v-model="query"
        placeholder="输入书名/ISBN/作者"
        @keyup.enter="doSearch"
        clearable
        :disabled="busy"
      />
      <el-button type="primary" :loading="loading" :disabled="busy" @click="doSearch">
        搜索
      </el-button>
    </div>

    <div v-if="error" class="text-red-500 text-sm mb-2">{{ error }}</div>

    <div v-if="items.length === 0 && !loading" class="text-gray-500 text-sm">无结果</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-[420px] overflow-auto pr-1">
      <el-card v-for="(b, idx) in items" :key="b.id + '_' + idx" shadow="hover">
        <div class="flex gap-3">
          <img
            v-if="b.cover"
            :src="coverUrlForItem(b)"
            class="w-20 h-28 object-cover rounded border"
          />
          <div class="flex-1 min-w-0">
            <div class="font-medium truncate">{{ b.title }}</div>
            <div class="text-xs text-gray-500 truncate">{{ (b.authors || []).join(' / ') }}</div>
            <div class="text-xs text-gray-500 truncate" v-if="b.publisher">
              出版社：{{ b.publisher }}
            </div>
            <div class="text-xs text-gray-500" v-if="b.rating">
              评分：{{ (b.rating || 0).toFixed(1) }} / 5
            </div>
            <div class="text-xs text-gray-500 truncate" v-if="b.publishedDate">
              出版：{{ b.publishedDate }}
            </div>
            <div class="text-xs text-gray-400 truncate" v-if="b.url">
              <a :href="b.url" target="_blank">来源</a>
            </div>
            <div class="mt-2 flex gap-2">
              <el-button size="small" @click="emitApply(b)" type="primary" :disabled="busy">
                填充
              </el-button>
              <el-button size="small" @click="emitPreview(b)" :disabled="busy">预览</el-button>
            </div>
          </div>
        </div>
      </el-card>
    </div>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="visible = false">关闭</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'
import type { MetaRecord } from '@/types/metadata'
import { metadataApi } from '@/api/metadata'
import { prefetchResourceToken } from '@/utils/signedUrls'

const props = defineProps<{
  modelValue: boolean
  defaultProvider?: string
  title?: string
  defaultQuery?: string
}>()
const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'apply', payload: { item: MetaRecord; provider: string }): void
  (e: 'preview', payload: { item: MetaRecord; provider: string }): void
  (e: 'closed'): void
}>()

type ProviderInfo = { id: string; name: string; description?: string }
const visible = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })
const providers = ref<ProviderInfo[]>([])
const providerId = ref<string>(props.defaultProvider || '')
const query = ref('')
const items = ref<MetaRecord[]>([])
const loading = ref(false)
const applying = ref(false)
const busy = computed(() => loading.value || applying.value)
const error = ref('')

async function doSearch() {
  if (busy.value) return
  items.value = []
  error.value = ''
  if (!providerId.value) {
    error.value = '请选择平台'
    return
  }
  loading.value = true
  try {
    items.value = await metadataApi.search(providerId.value, query.value, 5)
  } catch (e: any) {
    error.value = e?.message || '搜索失败'
  } finally {
    loading.value = false
  }
}

function emitApply(b: MetaRecord) {
  applying.value = true
  emit('apply', { item: b, provider: providerId.value })
}
function emitPreview(b: MetaRecord) {
  applying.value = true
  emit('preview', { item: b, provider: providerId.value })
}
function coverUrlForItem(b: MetaRecord) {
  const p = b?.source?.id || providerId.value
  return b.cover ? metadataApi.coverUrl(p, b.cover) : ''
}

watch(
  () => props.defaultProvider,
  v => {
    if (v) providerId.value = v
  },
)
watch(
  () => visible.value,
  v => {
    if (v && props.defaultQuery) {
      query.value = props.defaultQuery
    }
    if (v) {
      prefetchResourceToken().catch(() => {})
    }
    if (!v) {
      applying.value = false
      loading.value = false
    }
  },
)

async function ensureProvidersLoaded() {
  if (providers.value.length > 0) return
  try {
    providers.value = await metadataApi.listProviders()
    if (!providerId.value && providers.value[0]?.id) providerId.value = providers.value[0].id
  } catch (e: any) {
    error.value = e?.message || '加载平台列表失败'
  }
}

onMounted(() => {
  ensureProvidersLoaded()
})
watch(
  () => visible.value,
  v => {
    if (v) ensureProvidersLoaded()
  },
)

function onClosed() {
  applying.value = false
  loading.value = false
  emit('closed')
}
</script>

<style scoped></style>
