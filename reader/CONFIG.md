# Global Configuration Documentation

This reader application supports global configuration through the `src/config.ts` file.

## Configuration Options

### Backend Configuration
- **`defaultBackendUrl`**: Default backend API base URL (default: `http://localhost:5174` or `VITE_API_BASE_URL` env var)
- **`allowCustomBackendUrl`**: Allow users to customize the backend URL in settings (default: `true`)

### Filter Configuration
- **`enableShelfFilter`**: Enable shelf filtering in book list (default: `true`)
- **`enableTagFilter`**: Enable tag filtering in book list (default: `true`)
- **`enableAuthorFilter`**: Enable author filtering in book list (default: `true`)
- **`enableReadStateFilter`**: Enable read state filtering in book list (default: `true`)
- **`enableRatingFilter`**: Enable rating filtering in book list (default: `true`)
- **`enableAdvancedFilters`**: Enable advanced filters like publisher, language, ISBN, etc. (default: `true`)

### Other Options
- **`defaultPerPage`**: Default items per page in book list (default: `20`)
- **`enableOfflineMode`**: Enable offline mode features (default: `true`)

## Customization

### Method 1: Environment Variables
Set `VITE_API_BASE_URL` in your `.env` file:
```
VITE_API_BASE_URL=https://api.example.com
```

### Method 2: Local Config File
1. Copy `src/config.local.example.ts` to `src/config.local.ts`
2. Modify the values as needed
3. The local config file is gitignored and will not be committed

Example `config.local.ts`:
```typescript
import type { AppConfig } from './config'

export const config: Partial<AppConfig> = {
  defaultBackendUrl: 'https://api.example.com',
  allowCustomBackendUrl: false,
  enableShelfFilter: false,
  enableAdvancedFilters: false,
}
```

## Usage in Code

Import config values in your components:
```typescript
import { enableShelfFilter, defaultBackendUrl } from '@/config'
```

Or import the entire config object:
```typescript
import { config } from '@/config'
```
