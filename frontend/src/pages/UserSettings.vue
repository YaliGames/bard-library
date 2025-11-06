<template>
  <div
    class="flex flex-col md:flex-row max-w-7xl mx-auto mt-4 md:mt-8 px-4 md:px-6 md:space-x-10 gap-4 md:gap-0"
  >
    <!-- 左侧菜单 -->
    <aside class="w-full md:w-64 space-y-4 text-sm">
      <div>
        <h2 class="text-xl font-semibold mb-4">用户设置</h2>
        <div class="space-y-1">
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
      <!-- 页面显示设置 -->
      <div class="bg-white shadow-sm rounded-xl p-6">
        <!-- 说明 -->
        <div v-if="active === 'display'">
          <h2 class="text-xl font-semibold mb-4">页面显示设置</h2>
          <SettingsItem
            :loading="loading"
            title="书库页显示阅读进度标签"
            description="书库页面中，在每本图书封面的右上角上显示阅读进度标签。"
          >
            <el-switch v-model="local.bookList.showReadTag" />
          </SettingsItem>
          <SettingsItem
            :loading="loading"
            title="书库页显示标为已读按钮"
            description="书库页面中，在每本图书的标题下方显示快捷标记为已读/未读的按钮。"
          >
            <el-switch v-model="local.bookList.showMarkReadButton" />
          </SettingsItem>
          <SettingsItem
            :loading="loading"
            title="图书详情页显示阅读进度标签"
            description="图书详情页面中，在图书封面的右上角上显示阅读进度标签。"
          >
            <el-switch v-model="local.bookDetail.showReadTag" />
          </SettingsItem>
          <div class="mt-4 flex items-center justify-end">
            <el-button type="primary" :disabled="loading" :loading="saving" @click="onSave">
              保存
            </el-button>
          </div>
        </div>
        <!-- 阅读偏好设置 -->
        <div v-if="active === 'reading'">
          <h2 class="text-xl font-semibold mb-4">阅读偏好设置</h2>
          <SettingsItem
            :loading="loading"
            title="TXT阅读器目录自动滚动"
            description="在TXT阅读器中，每次切换章节时，目录面板自动滚动到当前所切换章节的对应位置。"
          >
            <el-switch v-model="local.txtReader.autoScrollCategory" />
          </SettingsItem>
          <div class="mt-4 flex items-center justify-end">
            <el-button type="primary" :disabled="loading" :loading="saving" @click="onSave">
              保存
            </el-button>
          </div>
        </div>

        <!-- 全局偏好设置 -->
        <div v-if="active === 'global'">
          <h2 class="text-xl font-semibold mb-4">全局偏好设置</h2>
          <SettingsItem
            :loading="loading"
            title="默认展开筛选条件"
            description="进入书库时默认展开筛选条件面板。"
          >
            <el-switch v-model="local.preferences.expandFilterMenu" />
          </SettingsItem>
          <div class="mt-4 flex items-center justify-end">
            <el-button type="primary" :disabled="loading" :loading="saving" @click="onSave">
              保存
            </el-button>
          </div>
        </div>

        <!-- 重置 -->
        <div v-if="active === 'reset'">
          <h2 class="text-xl font-semibold mb-4">重置所有设置</h2>
          <p class="text-sm text-gray-600 mb-4">
            如果你遇到问题，或者想要恢复默认设置，可以点击下面的按钮重置所有设置。
            <br />
            重置后需要重新配置你的偏好设置，且不可撤销，请谨慎操作。
          </p>
          <el-button
            type="danger"
            :disabled="loading"
            @click="
              () => {
                resetAll()
              }
            "
          >
            重置所有设置
          </el-button>
        </div>
      </div>
    </section>
  </div>
</template>
<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue'
import { ElMessageBox, ElMessage } from 'element-plus'
import { settingsApi, type UserSettings } from '@/api/settings'
import { useSettingsStore, defaultSettings } from '@/stores/settings'
import SettingsItem from '@/components/Settings/SettingsItem.vue'

const settingsStore = useSettingsStore()
const userSettings = settingsStore.settings
const setAll = settingsStore.setAll
const local = reactive<UserSettings>(JSON.parse(JSON.stringify(defaultSettings)))
const saving = ref(false)
const loading = ref(true)

// 左侧菜单：JSON化
type MenuId = 'display' | 'reading' | 'global' | 'reset'
const active = ref<MenuId>('display')
const menu: Array<{ id: MenuId; label: string; icon: string }> = [
  { id: 'display', label: '页面显示', icon: 'monitor' },
  { id: 'reading', label: '阅读偏好', icon: 'menu_book' },
  { id: 'global', label: '全局偏好', icon: 'settings' },
  { id: 'reset', label: '重置', icon: 'restart_alt' },
]

onMounted(async () => {
  // 远端拉取，用于首次登录或设备切换
  try {
    const remote = await settingsApi.get()
    setAll(remote)
  } catch {
    // 忽略错误，沿用本地默认
  } finally {
    // 将 store 当前值拷贝到本地编辑副本
    Object.assign(local, JSON.parse(JSON.stringify(userSettings)))
    loading.value = false
  }
})

async function onSave() {
  saving.value = true
  try {
    const merged = { ...userSettings, ...local }
    const remote = await settingsApi.update(merged)
    setAll(remote)
    ElMessage.success('已保存')
  } catch (e: any) {
    ElMessage.warning(e?.message || '保存失败')
  } finally {
    saving.value = false
  }
}

async function resetAll() {
  try {
    await ElMessageBox.confirm('确定要重置所有设置吗？此操作不可撤销。', '重置确认', {
      type: 'warning',
      confirmButtonText: '重置',
      cancelButtonText: '取消',
    })
  } catch {
    return
  }
  // 同步远端 & 本地状态
  try {
    await settingsApi.update(defaultSettings)
  } catch {}
  setAll(defaultSettings)
  Object.assign(local, JSON.parse(JSON.stringify(defaultSettings)))
}
</script>
