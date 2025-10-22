import { metadataApi } from "@/api/metadata";
import type { MetadataProvider, MetaRecord } from "@/types/metadata";

export const DoubanProvider: MetadataProvider = {
  id: "douban",
  name: "豆瓣 Douban",
  async search(query: string, limit = 5): Promise<MetaRecord[]> {
    return metadataApi.search("douban", query, limit);
  },
  async getById(id: string): Promise<MetaRecord | null> {
    return metadataApi.book("douban", { id });
  },
};
