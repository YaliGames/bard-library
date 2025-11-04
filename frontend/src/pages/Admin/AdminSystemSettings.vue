<template>
  <div
    class="flex flex-col md:flex-row max-w-7xl mx-auto mt-4 md:mt-8 px-4 md:px-6 md:space-x-10 gap-4 md:gap-0"
  >
    <!-- 左侧菜单 -->
    <aside class="w-full md:w-64 space-y-4 text-sm">
      <div>
        <h2 class="text-xl font-semibold mb-4">系统设置</h2>
        <div v-if="loading" class="space-y-1">
          <!-- 骨架屏 -->
          <div
            v-for="i in 4"
            :key="i"
            class="flex items-center px-3 py-2 rounded-md bg-gray-100 animate-pulse"
          >
            <div class="w-5 h-5 bg-gray-300 rounded mr-2"></div>
            <div class="h-4 bg-gray-300 rounded w-20"></div>
          </div>
        </div>
        <div v-else class="space-y-1">
          <div
            v-for="m in menu"
            :key="m.id"
            @click="active = m.id"
            :class="[
              'flex items-center px-3 py-2 rounded-md cursor-pointer',
              active === m.id
                ? 'bg-gray-200 text-blue-700 font-medium'
                : 'hover:bg-gray-200 text-gray-700',
            ]"
          >
            <span class="material-symbols-outlined mr-2 text-lg">{{ m.icon }}</span>
            <span>{{ m.label }}</span>
          </div>
        </div>
      </div>
    </aside>

    <!-- 右侧内容 -->
    <section class="flex-1 space-y-6">
      <div class="bg-white shadow-sm rounded-xl p-6">
        <!-- 说明 -->
        <div v-if="active === 'intro'">
          <h2 class="text-xl font-semibold mb-4">系统设置</h2>
          <div class="text-sm text-gray-600">
            此页面用于管理系统全设置，修改后将立即对所有用户生效。
          </div>
        </div>

        <!-- 分类设置（根据当前选中的分类渲染） -->
        <div v-if="active !== 'intro' && active !== 'reset'">
          <h2 class="text-xl font-semibold mb-4">{{ categories[active]?.label || '设置' }}</h2>
          <template v-if="loading">
            <SettingsItem v-for="i in 4" :key="i" :title="' '" :description="''" :loading="true" />
          </template>
          <template v-else>
            <SettingsItem
              v-for="(def, key) in currentCategoryItems"
              :key="String(key)"
              :title="labelOf(String(key), def)"
              :description="def.description || ''"
            >
              <template v-if="def.type === 'bool'">
                <el-switch v-model="valuesReactive[key]" />
              </template>
              <template v-else-if="def.type === 'int'">
                <el-input-number v-model="valuesReactive[key]" :min="0" :step="1" />
              </template>
              <template v-else-if="def.type === 'size'">
                <div class="w-full max-w-xl">
                  <el-input v-model="sizeInputs[key]" placeholder="例如 100MB 或 1GB">
                    <template #append>{{ hintSize(String(key), def) }}</template>
                  </el-input>
                  <div v-if="sizeInputs[key]" class="text-xs text-gray-400 mt-1">
                    解析后：{{ parsedSizeText(String(key)) }}
                  </div>
                </div>
              </template>
              <template v-else-if="def.type === 'json'">
                <el-input
                  class="w-full max-w-xl"
                  type="textarea"
                  :rows="6"
                  v-model="jsonInputs[key]"
                />
              </template>
              <template v-else>
                <el-input class="w-full max-w-xl" v-model="valuesReactive[key]" />
              </template>
            </SettingsItem>
            <div class="flex gap-2 mt-4 items-center justify-end">
              <el-button type="primary" :loading="saving" :disabled="loading" @click="save">
                保存
              </el-button>
            </div>
          </template>
        </div>

        <!-- 重置说明 -->
        <div v-if="active === 'reset'">
          <h2 class="text-xl font-semibold mb-4">重置</h2>
          <p class="text-sm text-gray-600 mb-4">将当前未保存的改动丢弃，并从服务器重新加载设置。</p>
          <el-button :disabled="loading" @click="reload">重置为服务器值</el-button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { systemSettingsApi, type SettingDef, type CategoryDef } from '@/api/systemSettings'
import { parseSizeToBytes, formatBytes } from '@/utils/systemSettings'
import SettingsItem from '@/components/Settings/SettingsItem.vue'

const loading = ref(true)
const saving = ref(false)

const valuesReactive = reactive<Record<string, any>>({})
const categories = reactive<Record<string, CategoryDef>>({})
const sizeInputs = reactive<Record<string, string>>({})
const jsonInputs = reactive<Record<string, string>>({})

// 左侧菜单：动态从 categories 生成
const active = ref<string>('intro')

// 动态菜单：包含特殊项(intro, reset)和后端分类
const menu = computed(() => {
  const items: Array<{ id: string; label: string; icon: string }> = [
    { id: 'intro', label: '说明', icon: 'info' },
  ]
  // 添加后端分类
  for (const key in categories) {
    const cat = categories[key]
    items.push({ id: key, label: cat.label, icon: cat.icon })
  }
  items.push({ id: 'reset', label: '重置', icon: 'restart_alt' })
  return items
})

// 获取当前选中分类的设置项
const currentCategoryItems = computed(() => {
  const activeValue = active.value
  if (activeValue === 'intro' || activeValue === 'reset') return {}
  return categories[activeValue]?.items || {}
})

function labelOf(key: string, def: SettingDef) {
  return def.label || key
}
function hintSize(key: string, def: SettingDef) {
  const dv = def.default_bytes ?? parseSizeToBytes(def.default as any) ?? null
  return dv ? `默认 ${formatBytes(dv)}` : '字节/KB/MB/GB'
}
function parsedSizeText(key: string) {
  const b = parseSizeToBytes(sizeInputs[key])
  return b != null ? formatBytes(b) : '-'
}

async function load() {
  loading.value = true
  try {
    const res = await systemSettingsApi.get()
    // 填充 categories
    Object.keys(categories).forEach(k => delete categories[k])
    Object.assign(categories, res.categories || {})
    // 填充值
    Object.keys(valuesReactive).forEach(k => delete valuesReactive[k])
    Object.assign(valuesReactive, res.values || {})
    // 初始化类型特定输入（从 categories 中获取所有设置项定义）
    for (const catKey in categories) {
      const cat = categories[catKey]
      for (const k in cat.items) {
        const def = cat.items[k]
        if (def.type === 'size') {
          const v = valuesReactive[k]
          sizeInputs[k] = typeof v === 'number' ? formatBytes(v) : v || ''
        } else if (def.type === 'json') {
          const v = valuesReactive[k]
          try {
            jsonInputs[k] = JSON.stringify(v ?? null, null, 2)
          } catch {
            jsonInputs[k] = ''
          }
        }
      }
    }
    // 如果有分类且当前 active 是默认值,自动选中第一个分类
    if (active.value === 'intro' && Object.keys(categories).length > 0) {
      active.value = Object.keys(categories)[0]
    }
  } catch (e: any) {
    ElMessage.error(e?.message || '加载失败')
  } finally {
    loading.value = false
  }
}

function reload() {
  load()
}

async function save() {
  // 组装提交 payload（遍历所有 categories 中的设置项）
  const payload: Record<string, any> = {}

  for (const catKey in categories) {
    const cat = categories[catKey]
    for (const k in cat.items) {
      const def = cat.items[k]
      if (def.type === 'size') {
        const bytes = parseSizeToBytes(sizeInputs[k])
        if (bytes == null || bytes < 0) {
          ElMessage.error(`「${labelOf(k, def)}」格式无效`)
          return
        }
        payload[k] = { type: 'size', value: bytes }
      } else if (def.type === 'json') {
        const t = jsonInputs[k]
        try {
          const v = t ? JSON.parse(t) : null
          payload[k] = { type: 'json', value: v }
        } catch {
          ElMessage.error(`「${labelOf(k, def)}」JSON 语法错误`)
          return
        }
      } else if (def.type === 'bool') {
        payload[k] = { type: 'bool', value: !!valuesReactive[k] }
      } else if (def.type === 'int') {
        const n = Number(valuesReactive[k] ?? 0)
        if (!Number.isFinite(n)) {
          ElMessage.error(`「${labelOf(k, def)}」必须是整数`)
          return
        }
        payload[k] = { type: 'int', value: Math.trunc(n) }
      } else {
        payload[k] = { type: 'string', value: String(valuesReactive[k] ?? '') }
      }
    }
  }

  saving.value = true
  try {
    const res = await systemSettingsApi.update(payload)
    // 更新 categories
    Object.keys(categories).forEach(k => delete categories[k])
    Object.assign(categories, res.categories || {})
    // 更新 values
    Object.keys(valuesReactive).forEach(k => delete valuesReactive[k])
    Object.assign(valuesReactive, res.values || {})
    // 重新初始化类型特定输入
    for (const catKey in categories) {
      const cat = categories[catKey]
      for (const k in cat.items) {
        const def = cat.items[k]
        if (def.type === 'size') {
          const v = valuesReactive[k]
          sizeInputs[k] = typeof v === 'number' ? formatBytes(v) : v || ''
        } else if (def.type === 'json') {
          const v = valuesReactive[k]
          try {
            jsonInputs[k] = JSON.stringify(v ?? null, null, 2)
          } catch {
            jsonInputs[k] = ''
          }
        }
      }
    }
    ElMessage.success('已保存')
  } catch (e: any) {
    ElMessage.error(e?.message || '保存失败')
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<style scoped></style>
