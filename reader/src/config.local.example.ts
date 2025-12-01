/**
 * Local configuration override
 * 
 * Copy this file from config.local.example.ts to config.local.ts
 * and customize the values as needed.
 * 
 * This file is gitignored and will not be committed.
 */

import type { AppConfig } from './config'

export const config: Partial<AppConfig> = {
    // Example: Use a different backend URL
    // defaultBackendUrl: 'https://api.example.com',

    // Example: Disable custom backend URL
    // allowCustomBackendUrl: false,

    // Example: Disable shelf filter
    // enableShelfFilter: false,

    // Example: Disable advanced filters
    // enableAdvancedFilters: false,
}
