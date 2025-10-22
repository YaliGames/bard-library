import type { MetadataProvider } from "@/types/metadata";
import { DoubanProvider } from "./douban";

const registry: Record<string, MetadataProvider> = {
  [DoubanProvider.id]: DoubanProvider,
};

export function listProviders(): MetadataProvider[] {
  return Object.values(registry);
}

export function getProvider(id: string): MetadataProvider | undefined {
  return registry[id];
}

export function registerProvider(p: MetadataProvider) {
  registry[p.id] = p;
}
