<template>
    <section class="container mx-auto px-4 py-6 max-w-4xl">
        <h2 class="text-2xl font-semibold mb-6">系统设置</h2>
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <div class="text-lg font-medium mb-2">说明</div>
            <div class="text-sm text-gray-600 mb-4">
                此页面用于管理系统全设置，修改后将立即对所有用户生效。
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
                    <div class="divide-y">
                        <div v-for="(def, key) in schema" :key="String(key)" class="pb-2">
                            <div class="mb-2">
                                <div class="font-medium">{{ labelOf(key, def) }}</div>
                                <div v-if="def.description" class="text-xs text-gray-500 mt-1">
                                    {{ def.description }}
                                </div>
                            </div>
                            <div class="max-w-xl">
                                <template v-if="def.type === 'bool'">
                                    <el-switch v-model="valuesReactive[key]" />
                                </template>
                                <template v-else-if="def.type === 'int'">
                                    <el-input-number v-model="valuesReactive[key]" :min="0" :step="1" />
                                </template>
                                <template v-else-if="def.type === 'size'">
                                    <el-input v-model="sizeInputs[key]" placeholder="例如 100MB 或 1GB">
                                        <template #append>{{ hintSize(key, def) }}</template>
                                    </el-input>
                                    <div v-if="sizeInputs[key]" class="text-xs text-gray-400 mt-1">
                                        解析后：{{ parsedSizeText(key) }}
                                    </div>
                                </template>
                                <template v-else-if="def.type === 'json'">
                                    <el-input type="textarea" :rows="6" v-model="jsonInputs[key]" />
                                </template>
                                <template v-else>
                                    <el-input v-model="valuesReactive[key]" />
                                </template>
                            </div>
                        </div>

                        <div class="flex gap-2 mt-4 items-center">
                            <el-button type="primary" :loading="saving" @click="save">保存</el-button>
                            <el-button @click="reload">重置为服务器值</el-button>
                            <el-alert v-if="message" class="inline-flex" :title="message" :type="messageType" />
                        </div>
                    </div>
                </template>
            </el-skeleton>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { systemSettingsApi, type SettingDef, type SettingsResponse } from '@/api/systemSettings'
import { parseSizeToBytes, formatBytes } from '@/utils/systemSettings'

const loading = ref(true)
const saving = ref(false)
const message = ref('')
const messageType = ref<'success' | 'error' | 'info'>('info')

const valuesReactive = reactive<Record<string, any>>({})
const schema = reactive<Record<string, SettingDef>>({})
const sizeInputs = reactive<Record<string, string>>({})
const jsonInputs = reactive<Record<string, string>>({})

function labelOf(key: string, def: SettingDef) { return def.label || key }
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
        // 填充 schema
        Object.keys(schema).forEach(k => delete schema[k])
        Object.assign(schema, res.schema || {})
        // 填充值
        Object.keys(valuesReactive).forEach(k => delete valuesReactive[k])
        Object.assign(valuesReactive, res.values || {})
        // 初始化类型特定输入
        for (const k in schema) {
            const def = schema[k]
            if (def.type === 'size') {
                const v = valuesReactive[k]
                sizeInputs[k] = typeof v === 'number' ? formatBytes(v) : (v || '')
            } else if (def.type === 'json') {
                const v = valuesReactive[k]
                try { jsonInputs[k] = JSON.stringify(v ?? null, null, 2) } catch { jsonInputs[k] = '' }
            }
        }
    } catch (e: any) {
        message.value = e?.message || '加载失败'
        messageType.value = 'error'
    } finally { loading.value = false }
}

function reload() { load() }

async function save() {
    // 组装提交 payload（仅已知 schema 的键）
    const payload: Record<string, any> = {}
    for (const k in schema) {
        const def = schema[k]
        if (def.type === 'size') {
            const bytes = parseSizeToBytes(sizeInputs[k])
            if (bytes == null || bytes < 0) { ElMessage.error(`「${labelOf(k, def)}」格式无效`); return }
            payload[k] = { type: 'size', value: bytes }
        } else if (def.type === 'json') {
            const t = jsonInputs[k]
            try {
                const v = t ? JSON.parse(t) : null
                payload[k] = { type: 'json', value: v }
            } catch {
                ElMessage.error(`「${labelOf(k, def)}」JSON 语法错误`); return
            }
        } else if (def.type === 'bool') {
            payload[k] = { type: 'bool', value: !!valuesReactive[k] }
        } else if (def.type === 'int') {
            const n = Number(valuesReactive[k] ?? 0)
            if (!Number.isFinite(n)) { ElMessage.error(`「${labelOf(k, def)}」必须是整数`); return }
            payload[k] = { type: 'int', value: Math.trunc(n) }
        } else {
            payload[k] = { type: 'string', value: String(valuesReactive[k] ?? '') }
        }
    }
    saving.value = true
    try {
        const res = await systemSettingsApi.update(payload)
        Object.keys(schema).forEach(k => delete schema[k]); Object.assign(schema, res.schema || {})
        Object.keys(valuesReactive).forEach(k => delete valuesReactive[k]); Object.assign(valuesReactive, res.values || {})
        for (const k in schema) {
            const def = schema[k]
            if (def.type === 'size') {
                const v = valuesReactive[k]
                sizeInputs[k] = typeof v === 'number' ? formatBytes(v) : (v || '')
            } else if (def.type === 'json') {
                const v = valuesReactive[k]
                try { jsonInputs[k] = JSON.stringify(v ?? null, null, 2) } catch { jsonInputs[k] = '' }
            }
        }
        message.value = '已保存'
        messageType.value = 'success'
        ElMessage.success('已保存')
    } catch (e: any) {
        message.value = e?.message || '保存失败'
        messageType.value = 'error'
        ElMessage.error(message.value)
    } finally { saving.value = false }
}

onMounted(load)
</script>

<style scoped></style>
