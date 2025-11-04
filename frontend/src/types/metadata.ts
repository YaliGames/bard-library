export interface MetaSourceInfo {
  id: string
  description?: string
  link?: string
}

export interface MetaIdentifiers {
  [key: string]: string
}

export interface MetaRecord {
  id: string
  title: string
  authors: string[]
  publisher?: string
  description?: string
  url: string
  cover?: string
  rating?: number // 0-5
  publishedDate?: string // e.g., YYYY-MM-DD
  series?: string
  tags?: string[]
  identifiers?: MetaIdentifiers
  source: MetaSourceInfo
}

export interface MetadataProvider {
  id: string
  name: string
  search(query: string, limit?: number): Promise<MetaRecord[]>
  getById?(id: string): Promise<MetaRecord | null>
}
