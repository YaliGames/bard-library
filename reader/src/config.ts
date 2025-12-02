/**
 * Global configuration for the reader application
 * 
 * This file contains default settings that can be customized for different deployments.
 * To customize, create a `config.local.ts` file and export a partial config object.
 */

export interface AppConfig {
    /** Default backend API base URL */
    defaultBackendUrl: string

    /** Allow users to customize the backend URL in settings */
    allowCustomBackendUrl: boolean

    /** Enable shelf filtering in book list */
    enableShelfFilter: boolean

    /** Enable tag filtering in book list */
    enableTagFilter: boolean

    /** Enable author filtering in book list */
    enableAuthorFilter: boolean

    /** Enable read state filtering in book list */
    enableReadStateFilter: boolean

    /** Enable rating filtering in book list */
    enableRatingFilter: boolean

    /** Enable advanced filters (publisher, language, ISBN, etc.) */
    enableAdvancedFilters: boolean

    /** Default items per page in book list */
    defaultPerPage: number

    /** Enable offline mode features */
    enableOfflineMode: boolean
}

const defaultConfig: AppConfig = {
    // Backend configuration
    defaultBackendUrl: '/',
    allowCustomBackendUrl: false,

    // Filter configuration
    enableShelfFilter: false,
    enableTagFilter: true,
    enableAuthorFilter: true,
    enableReadStateFilter: true,
    enableRatingFilter: true,
    enableAdvancedFilters: true,

    // Pagination
    defaultPerPage: 20,

    // Features
    enableOfflineMode: true,
}

// Try to import local config
let localConfigOverrides: Partial<AppConfig> = {}
const localConfigModules = import.meta.glob('./config.local.ts', { eager: true })
if (Object.keys(localConfigModules).length > 0) {
    const localModule = localConfigModules['./config.local.ts'] as { config?: Partial<AppConfig> }
    localConfigOverrides = localModule.config || {}
} else {
    // Local config doesn't exist or failed to load, use defaults
    console.log('[Config] No local config found, using defaults')
}

// Merge default config with local config if it exists
export const config: AppConfig = {
    ...defaultConfig,
    ...localConfigOverrides,
}

// Export individual config values for convenience
export const {
    defaultBackendUrl,
    allowCustomBackendUrl,
    enableShelfFilter,
    enableTagFilter,
    enableAuthorFilter,
    enableReadStateFilter,
    enableRatingFilter,
    enableAdvancedFilters,
    defaultPerPage,
    enableOfflineMode,
} = config
