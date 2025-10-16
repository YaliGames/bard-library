<template>
    <section class="container mx-auto px-4 py-6 max-w-4xl">
        <h2 class="text-2xl font-semibold mb-6">用户设置</h2>
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <div class="text-lg font-medium mb-2">说明</div>
            <div class="text-sm text-gray-600 mb-4">
                该设置用于配置用户的界面和功能偏好，仅适用于当前登录用户。<br />
                <template v-if="isAdmin">
                    管理员用户可以在 <router-link to="/admin/settings" class="text-primary">系统设置</router-link> 页面配置全局设置。
                </template>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <el-skeleton :loading="loading" animated>
                <template #template>
                    <el-skeleton-item variant="text" class="w-40 h-6 mb-2" />
                    <div class="flex flex-col gap-2 p-2">
                        <el-skeleton-item variant="text" class="w-64 h-6" />
                        <el-skeleton-item variant="text" class="w-48 h-6" />
                        <el-skeleton-item variant="text" class="w-32 h-6" />
                    </div>
                </template>
                <template #default>
                    <div class="text-lg font-medium">页面显示设置</div>
                    <div class="flex flex-col gap-2 p-4">
                        <el-switch v-model="local.bookList.showReadTag" active-text="书库页显示阅读进度标签" />
                        <el-switch v-model="local.bookList.showMarkReadButton" active-text="书库页显示“标为已读”按钮" />
                        <el-switch v-model="local.bookDetail.showReadTag" active-text="图书详情页显示阅读进度标签" />
                    </div>
                    <div class="text-lg font-medium">阅读偏好设置</div>
                    <div class="flex flex-col gap-2 p-4">
                        <el-switch v-model="local.txtReader.autoScrollCategory" active-text="目录列表自动滚动到当前章节" />
                    </div>
                    <div class="text-lg font-medium">全局偏好设置</div>
                    <div class="flex flex-col gap-2 p-4">
                        <el-switch v-model="local.preferences.expandFilterMenu" active-text="默认展开筛选菜单" />
                    </div>
                    <el-button type="primary" :disabled="loading" :loading="saving" @click="onSave">保存</el-button>
                    <el-alert v-if="msg" class="inline-flex" :title="msg" :type="ok ? 'success' : 'error'"
                        show-icon />
                </template>
            </el-skeleton>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <div class="text-lg font-medium mb-2">重置所有设置</div>
            <div class="text-sm text-gray-600 mb-4">
                如果你遇到问题，或者想要恢复默认设置，可以点击下面的按钮重置所有设置。<br />
                重置后需要重新配置你的偏好设置，且不可撤销，请谨慎操作。
            </div>
            <el-button type="danger" :disabled="loading" @click="() => { resetAll() }">重置所有设置</el-button>
        </div>
    </section>
</template>
<script setup lang="ts">
import { reactive, ref, computed, onMounted } from 'vue'
import { ElMessageBox } from 'element-plus'
import { settingsApi, type UserSettings } from '@/api/settings'
import { useSettingsStore, defaultSettings } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'

const { state, setAll } = useSettingsStore()
const local = reactive<UserSettings>(JSON.parse(JSON.stringify(defaultSettings)))
const saving = ref(false)
const msg = ref('')
const ok = ref(false)
const loading = ref(true)

const { isRole } = useAuthStore()
const isAdmin = computed(()=> isRole('admin'))


onMounted(async () => {
    // 远端拉取，用于首次登录或设备切换
    try {
        const remote = await settingsApi.get();
        setAll(remote);
    } catch {
        // 忽略错误，沿用本地默认
    } finally {
        // 将 store 当前值拷贝到本地编辑副本
        Object.assign(local, JSON.parse(JSON.stringify(state)))
        loading.value = false
    }
})

async function onSave() {
    saving.value = true
    msg.value = ''
    ok.value = false
    try {
        const merged = { ...state, ...local }
        const remote = await settingsApi.update(merged)
        setAll(remote)
        msg.value = '已保存'
        ok.value = true
    } catch (e: any) {
        msg.value = e?.message || '保存失败'
        ok.value = false
    } finally { saving.value = false }
}

async function resetAll() {
    try {
        await ElMessageBox.confirm('确定要重置所有设置吗？此操作不可撤销。', '重置确认', {
            type: 'warning',
            confirmButtonText: '重置',
            cancelButtonText: '取消',
        })
    } catch { return }
    // 同步远端 & 本地状态
    try {
        await settingsApi.update(defaultSettings)
    } catch {}
    setAll(defaultSettings)
    Object.assign(local, JSON.parse(JSON.stringify(defaultSettings)))
}
</script>
