import { ref, onMounted } from 'vue'
import { getSystemName } from '@/utils/publicSettings'

const systemName = ref('Bard Library')
let isLoaded = false

/**
 * 获取系统名称的 composable
 */
export function useSystemName() {
  onMounted(async () => {
    if (!isLoaded) {
      try {
        systemName.value = await getSystemName()
        isLoaded = true
      } catch (error) {
        console.error('Failed to load system name:', error)
      }
    }
  })

  return {
    systemName,
  }
}
