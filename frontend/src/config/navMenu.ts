export type NavItem = {
  id: string
  label: string
  path?: string
  adminOnly?: boolean
  external?: boolean
}

export const navMenu: NavItem[] = [
  { id: 'home', label: '首页', path: '/' },
  { id: 'books', label: '书库', path: '/books' },
  { id: 'shelf', label: '书架', path: '/shelf' },
  { id: 'admin', label: '管理入口', path: '/admin/index', adminOnly: true },
]

export default navMenu

// 用户菜单配置：桌面下拉与移动抽屉复用此配置
export type UserMenuItem = {
  id: string
  label: string
  path?: string
  action?: 'logout'
  adminOnly?: boolean
  divided?: boolean
}

export const userMenu: UserMenuItem[] = [
  { id: 'user-profile', label: '个人资料', path: '/user/profile' },
  { id: 'user-settings', label: '用户设置', path: '/user/settings' },
  { id: 'user-shelves', label: '我的书架', path: '/user/shelves' },
  { id: 'system', label: '系统设置', path: '/admin/settings', adminOnly: true },
  { id: 'admin', label: '管理入口', path: '/admin/index', adminOnly: true },
  { id: 'logout', label: '退出登录', action: 'logout', divided: true },
]

 
