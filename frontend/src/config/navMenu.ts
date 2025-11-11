export type NavItem = {
  id: string
  label: string
  path?: string
  requiresAdminPermission?: boolean // 需要任一管理权限
  external?: boolean
}

export const navMenu: NavItem[] = [
  { id: 'home', label: '首页', path: '/' },
  { id: 'books', label: '书库', path: '/books' },
  { id: 'shelf', label: '书架', path: '/shelf' },
  { id: 'admin', label: '管理入口', path: '/admin/index', requiresAdminPermission: true },
]

export default navMenu

// 用户菜单配置：桌面下拉与移动抽屉复用此配置
export type UserMenuItem = {
  id: string
  label: string
  path?: string
  action?: 'logout'
  requiresAdminPermission?: boolean // 需要任一管理权限
  divided?: boolean
}

export const userMenu: UserMenuItem[] = [
  { id: 'user-profile', label: '个人资料', path: '/user/profile' },
  { id: 'user-settings', label: '用户设置', path: '/user/settings' },
  {
    id: 'system',
    label: '系统设置',
    path: '/admin/settings',
    divided: true,
    requiresAdminPermission: true,
  },
  { id: 'admin', label: '管理入口', path: '/admin/index', requiresAdminPermission: true },
  { id: 'logout', label: '退出登录', action: 'logout', divided: true },
]
