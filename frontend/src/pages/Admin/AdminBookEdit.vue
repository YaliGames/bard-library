<template>
    <section class="container mx-auto px-4 py-4 max-w-7xl">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">{{ isNew ? '新建图书' : '编辑图书' }}</h2>
            <div class="flex items-center">
                <el-button @click="back">
                    <span class="material-symbols-outlined mr-1 text-lg">arrow_back</span> 返回
                </el-button>
                <el-button type="primary" @click="save">
                    <span class="material-symbols-outlined mr-1 text-lg">save</span> 保存
                </el-button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
                <el-card shadow="never">
                    <template #header>
                        <div class="font-medium">封面</div>
                    </template>
                    <div class="flex items-start gap-6">
                        <CoverEditor :model-value="(form.cover_file_id as any) || null"
                            :book-id="isNew ? undefined : Number(idParam)"
                            @update:modelValue="v => form.cover_file_id = v || undefined" />
                    </div>
                </el-card>
                <el-card shadow="never" v-if="!isNew" class="mt-6">
                    <template #header>
                        <div class="font-medium">文件</div>
                    </template>
                    <div class="border border-dashed rounded p-4">
                        <div class="mb-2 text-gray-600">追加文件到当前书</div>
                        <el-upload
                            drag
                            action="#"
                            :auto-upload="false"
                            :on-change="onFilePicked"
                            :on-remove="onFileRemoved"
                            :show-file-list="true"
                            :limit="1"
                            accept=".epub,.pdf,.txt,.zip">
                            <i class="el-icon--upload" />
                            <div class="el-upload__text">
                                拖拽文件到此处，或 <em>点击选择</em>
                            </div>
                            <template #tip>
                                <div class="el-upload__tip">单次仅限 1 个文件</div>
                            </template>
                        </el-upload>
                        <div class="mt-2">
                            <el-button type="primary" :disabled="!pickedFile" @click="uploadToCurrent" :loading="uploading">上传</el-button>
                        </div>
                    </div>
                    <el-table :data="files" class="mt-3" border>
                        <el-table-column label="#" width="40" prop="id" align="center"/>
                        <el-table-column label="格式" align="center">
                            <template #default="{ row }">{{ (row.format || 'file').toUpperCase() }}</template>
                        </el-table-column>
                        <el-table-column label="操作" width="240" align="center">
                            <template #default="{ row }">
                                <el-button size="small" @click="downloadFile(row.id)">下载</el-button>
                                <el-button size="small" v-if="(row.format || '').toLowerCase() === 'txt'"
                                    @click="editChapters(row.id)">编辑章节</el-button>
                                <el-button size="small" type="danger" plain @click="removeFile(row.id)">删除</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-card>
            </div>
            <!-- 基本信息 -->
            <el-card class="lg:col-span-2" shadow="never">
                <template #header>
                    <div class="flex items-center justify-between">
                        <div class="font-medium">基本信息</div>
                        <div class="flex items-center gap-2">
                            <el-button size="small" @click="openMetaDialog">
                                <span class="material-symbols-outlined mr-1 text-lg">travel_explore</span> 从网络刮削
                            </el-button>
                        </div>
                    </div>
                </template>
                <div class="flex items-start gap-6">
                    <el-form label-width="100px" label-position="left" class="max-w-3xl flex-1">
                        <el-form-item label="标题" required>
                            <el-input v-model="form.title" placeholder="请输入标题" />
                        </el-form-item>
                        <el-form-item label="副标题">
                            <el-input v-model="form.subtitle" placeholder="请输入副标题" />
                        </el-form-item>
                        <el-form-item label="作者">
                            <el-select v-model="authorValues" multiple filterable allow-create default-first-option
                                :reserve-keyword="false" placeholder="选择或新建作者" style="width: 100%">
                                <el-option v-for="a in authorsAll" :key="a.id" :label="a.name" :value="a.id" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="标签">
                            <el-select v-model="tagValues" multiple filterable allow-create default-first-option
                                :reserve-keyword="false" placeholder="选择或新建标签" style="width: 100%">
                                <el-option v-for="t in tagsAll" :key="t.id" :label="t.name" :value="t.id" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="丛书">
                            <el-select v-model="seriesValue" clearable filterable allow-create default-first-option
                                :reserve-keyword="false" placeholder="选择或新建丛书" style="width:100%">
                                <el-option v-for="s in series" :key="s.id" :label="s.name" :value="s.id" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="编号">
                            <el-input v-model.number="seriesIndexInput" type="number" placeholder="请输入丛书编号" min="1"
                                step="1" />
                        </el-form-item>
                        <el-form-item label="评分">
                            <el-slider v-model="form.rating" :min="0" :max="5" :step="0.1" show-input />
                        </el-form-item>
                        <el-form-item label="语言">
                            <el-input v-model="form.language" placeholder="请输入语言" />
                        </el-form-item>
                        <el-form-item label="出版社">
                            <el-input v-model="form.publisher" placeholder="请输入出版社" />
                        </el-form-item>
                        <el-form-item label="出版日期">
                            <el-date-picker v-model="form.published_at" type="date" placeholder="选择出版日期" />
                        </el-form-item>
                        <el-form-item label="ISBN10">
                            <el-input v-model="form.isbn10" />
                        </el-form-item>
                        <el-form-item label="ISBN13">
                            <el-input v-model="form.isbn13" />
                        </el-form-item>
                        <el-form-item label="简介">
                            <el-input type="textarea" :rows="6" v-model="form.description" placeholder="简要介绍" />
                        </el-form-item>
                    </el-form>
                </div>
            </el-card>

            <!-- 右侧列占位（可放其他设置） -->
            <div></div>
        </div>

        <MetadataSearchDialog v-model="metaDialogVisible"
            :default-provider="'douban'"
            :default-query="defaultMetaQuery"
            title="从平台搜索元数据"
            @apply="onMetaApply" />
    </section>
</template>
<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { booksApi } from '@/api/books'
import { filesApi } from '@/api/files'
import { authorsApi } from '@/api/authors'
import { tagsApi } from '@/api/tags'
import { seriesApi } from '@/api/series'
import type { Book, FileRec, Author, Tag, Series } from '@/api/types'
import { importsApi } from '@/api/imports'
import { ElMessage, ElMessageBox } from 'element-plus'
import CoverEditor from '@/components/CoverEditor.vue'
import MetadataSearchDialog from '@/components/Metadata/MetadataSearchDialog.vue'
import type { MetaRecord } from '@/types/metadata'
import { metadataApi } from '@/api/metadata'
import { coversApi } from '@/api/covers'

const route = useRoute()
const router = useRouter()
const idParam = route.params.id as string
const isNew = computed(() => idParam === 'new')

const form = ref<Partial<Book>>({ title: '' })
const files = ref<FileRec[]>([])
const series = ref<Series[]>([])
const seriesValue = ref<number | string | null>(null)
const seriesIndexInput = ref<number | null>(null)

// 作者/标签：多选 + 可创建
const authorsAll = ref<Author[]>([])
const tagsAll = ref<Tag[]>([])
const authorValues = ref<(number | string)[]>([])
const tagValues = ref<(number | string)[]>([])
const pickedFile = ref<File | null>(null)
const uploading = ref(false)
const metaDialogVisible = ref(false)
const pendingCoverUrl = ref<string | null>(null)
const pendingCoverProvider = ref<string | null>(null)
const defaultMetaQuery = computed(() => {
    const t = (form.value.title || '').trim()
    const authorNames: string[] = []
    for (const v of authorValues.value) {
        if (typeof v === 'number') {
            const found = authorsAll.value.find(a => a.id === v)
            if (found?.name) authorNames.push(found.name)
        } else if (typeof v === 'string' && v.trim()) {
            authorNames.push(v.trim())
        }
    }
    const a = authorNames.join(' ')
    return [t, a].filter(Boolean).join(' ').trim()
})

async function load() {
    // 丛书列表总是需要
    series.value = await seriesApi.list()
    // 加载作者/标签全集（不分页）
    try {
        const [as, ts] = await Promise.all([
            authorsApi.list(),
            tagsApi.list(),
        ])
        authorsAll.value = as
        tagsAll.value = ts
    } catch { }
    if (isNew.value) return
    const id = Number(idParam)
    const b = await booksApi.get(id)
    form.value = { ...b }
    // 后端返回 published_at 为 'YYYY-MM-DD' 字符串，转换为 Date 供 el-date-picker 使用
    if (typeof (form.value as any).published_at === 'string' && (form.value as any).published_at) {
        const d = parseYmdToDate((form.value as any).published_at as unknown as string)
        if (d) (form.value as any).published_at = d as any
    }
    files.value = await booksApi.files(id)
    authorValues.value = (b.authors || []).map(a => a.id)
    tagValues.value = (b.tags || []).map(t => t.id)
    seriesValue.value = b.series_id ?? null
    seriesIndexInput.value = (typeof b.series_index === 'number' && b.series_index >= 1) ? b.series_index : null
}

async function save() {
    try {
        const payload: any = { ...form.value, author_values: authorValues.value, tag_values: tagValues.value }
        // 统一将 published_at 转为 YYYY-MM-DD 字符串
        if (payload.published_at instanceof Date) {
            payload.published_at = formatDateYmd(payload.published_at)
        } else if (typeof payload.published_at === 'string' && payload.published_at) {
            const d = parseYmdToDate(payload.published_at)
            payload.published_at = d ? formatDateYmd(d) : null
        }
        // 丛书：允许为空；允许选择已有（数字ID）或新建（字符串）
        payload.series_value = seriesValue.value ?? null
        // 丛书编号：必须是整数且 >=1，其他情况置为 null
        payload.series_index = (Number.isInteger(seriesIndexInput.value) && (seriesIndexInput.value as number) >= 1)
            ? seriesIndexInput.value
            : null
        if (isNew.value) {
            const created = await booksApi.create(payload)
            // 如果有待设置封面，创建成功后优先使用原始链接导入，失败则回退到代理链接
            if (pendingCoverUrl.value) {
                let imported = false
                try {
                    const r = await coversApi.fromUrl(created.id, { url: pendingCoverUrl.value })
                    form.value.cover_file_id = r?.file_id as any
                    imported = true
                } catch {}
                if (!imported && pendingCoverProvider.value) {
                    try {
                        const proxyUrl = metadataApi.coverAbsoluteUrl(pendingCoverProvider.value, pendingCoverUrl.value)
                        const r2 = await coversApi.fromUrl(created.id, { url: proxyUrl })
                        form.value.cover_file_id = r2?.file_id as any
                    } catch {}
                }
            }
            router.replace({ name: 'admin-book-edit', params: { id: created.id } })
        } else {
            await booksApi.update(Number(idParam), payload)
            // 若编辑已有书且存在待设置封面，则立即导入
            if (pendingCoverUrl.value) {
                let imported = false
                try {
                    const r = await coversApi.fromUrl(Number(idParam), { url: pendingCoverUrl.value })
                    form.value.cover_file_id = r?.file_id as any
                    imported = true
                } catch {}
                if (!imported && pendingCoverProvider.value) {
                    try {
                        const proxyUrl = metadataApi.coverAbsoluteUrl(pendingCoverProvider.value, pendingCoverUrl.value)
                        const r2 = await coversApi.fromUrl(Number(idParam), { url: proxyUrl })
                        form.value.cover_file_id = r2?.file_id as any
                    } catch {}
                }
            }
        }
        ElMessage.success('保存成功')
    } catch (e: any) {
        ElMessage.error(e?.message || '保存失败')
    }
}

async function removeFile(fid: number) {
    try {
        await ElMessageBox.confirm('仅删除记录，不会删除物理文件。确认？', '删除确认', { type: 'warning', confirmButtonText: '删除', cancelButtonText: '取消' })
    } catch {
        return
    }
    try {
        await filesApi.remove(fid)
        if (!isNew.value) { files.value = await booksApi.files(Number(idParam)) }
        ElMessage.success('已删除')
    } catch (e: any) {
        ElMessage.error(e?.message || '删除失败')
    }
}

// 适配 el-upload 回调签名
function onFilePicked(file: any) {
    // Element Plus UploadUserFile: file.raw 为原始 File 对象
    pickedFile.value = file?.raw ?? null
}

function onFileRemoved() {
    pickedFile.value = null
}

async function uploadToCurrent() {
    if (!pickedFile.value || isNew.value) return
    uploading.value = true
    try {
        const fd = new FormData()
        fd.append('file', pickedFile.value)
        fd.append('book_id', String(idParam))
        await importsApi.upload(fd)
        files.value = await booksApi.files(Number(idParam))
        pickedFile.value = null
        ElMessage.success('上传成功')
    } finally {
        uploading.value = false
    }
}

function back() { router.back() }

onMounted(load)

function downloadFile(fid: number) { window.location.href = `/api/v1/files/${fid}/download` }
function editChapters(fileId: number) { router.push({ name: 'admin-txt-chapters', params: { id: String(fileId) } }) }

function openMetaDialog() { metaDialogVisible.value = true }
async function onMetaApply(payload: { item: MetaRecord; provider: string }) {
    const item = payload.item
    const provider = payload.provider
    // 标题
    if (item.title) form.value.title = item.title
    // 作者：将字符串作为新建值传入
    if (item.authors?.length) {
        authorValues.value = item.authors.slice(0, 10)
    }
    // 标签
    if (item.tags?.length) {
        tagValues.value = item.tags.slice(0, 20)
    }
    // 出版社
    if (item.publisher) form.value.publisher = item.publisher
    // 简介
    if (item.description) form.value.description = item.description
    // 评分：后端/元数据均使用 0.0-5.0，保留一位小数
    if (typeof item.rating === 'number') {
        form.value.rating = Math.max(0, Math.min(5, Math.round(item.rating * 10) / 10))
    }
    // 出版日期
    if (item.publishedDate) {
        const d = parseYmdToDate(item.publishedDate)
        form.value.published_at = (d || item.publishedDate) as any
    }
    // 丛书
    if (item.series) {
        seriesValue.value = item.series
    }
    // ISBN
    const isbn = item.identifiers?.isbn || ''
    if (isbn) {
        const digits = isbn.replace(/[^0-9Xx]/g, '')
        if (digits.length === 13) form.value.isbn13 = digits
        else if (digits.length === 10) form.value.isbn10 = digits
    }
    // 封面：记录待设置URL（优先使用原始链接），并记录 provider；若是编辑已有书，尝试立即导入，失败再回退到代理
    if (item.cover) {
        pendingCoverUrl.value = item.cover
        pendingCoverProvider.value = provider
        if (!isNew.value) {
            let imported = false
            try {
                const r = await coversApi.fromUrl(Number(idParam), { url: pendingCoverUrl.value })
                form.value.cover_file_id = r?.file_id as any
                imported = true
            } catch {}
            if (!imported && pendingCoverProvider.value) {
                try {
                    const proxyUrl = metadataApi.coverAbsoluteUrl(pendingCoverProvider.value, pendingCoverUrl.value)
                    const r2 = await coversApi.fromUrl(Number(idParam), { url: proxyUrl })
                    form.value.cover_file_id = r2?.file_id as any
                } catch {}
            }
        }
    }
    metaDialogVisible.value = false
    ElMessage.success('已填充元数据，请确认并保存')
}

function formatDateYmd(d: Date): string {
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
}

function parseYmdToDate(s: string): Date | null {
    const t = String(s || '').trim()
    if (!t) return null
    // yyyy-mm-dd
    let m = t.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/)
    if (m) {
        const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]))
        return isNaN(d.getTime()) ? null : d
    }
    // yyyy-mm -> use day=1
    m = t.match(/^(\d{4})-(\d{1,2})$/)
    if (m) {
        const d = new Date(Number(m[1]), Number(m[2]) - 1, 1)
        return isNaN(d.getTime()) ? null : d
    }
    // yyyy -> use 01-01
    m = t.match(/^(\d{4})$/)
    if (m) {
        const d = new Date(Number(m[1]), 0, 1)
        return isNaN(d.getTime()) ? null : d
    }
    return null
}
</script>
