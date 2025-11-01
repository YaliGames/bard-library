import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";
import { isLoggedIn, isRole } from "@/stores/auth";
import { http } from "@/api/http";

const routes: RouteRecordRaw[] = [
  { path: "/", name: "home", component: () => import("./pages/Home.vue") },
  {
    path: "/login",
    name: "login",
    component: () => import("./pages/Login.vue"),
  },
  {
    path: "/register",
    name: "register",
    component: () => import("./pages/Register.vue"),
  },
  {
    path: "/forgot",
    name: "forgot",
    component: () => import("./pages/ForgotPassword.vue"),
  },
  {
    path: "/reset",
    name: "reset",
    component: () => import("./pages/ResetPassword.vue"),
  },
  {
    path: "/shelf",
    name: "shelf",
    component: () => import("./pages/ShelfList.vue"),
  },
  {
    path: "/books",
    name: "books",
    component: () => import("./pages/BookList.vue"),
  },
  {
    path: "/shelf/:id",
    name: "shelf-detail",
    component: () => import("./pages/Shelf.vue"),
  },
  {
    path: "/books/:id",
    name: "book-detail",
    component: () => import("./pages/BookDetail.vue"),
  },
  {
    path: "/reader",
    children: [
      {
        path: "/txt/:id",
        name: "reader-txt",
        component: () => import("./pages/Reader/TxtReader.vue"),
      },
      {
        path: "/pdf/:id",
        name: "reader-pdf",
        component: () => import("./pages/Reader/PdfReader.vue"),
      },
      {
        path: "/epub/:id",
        name: "reader-epub",
        component: () => import("./pages/Reader/EpubReader.vue"),
      },
    ],
  },
  {
    path: "/user",
    redirect: {
      name: "user-index",
    },
    children: [
      {
        path: "profile",
        name: "user-profile",
        component: () => import("./pages/Profile.vue"),
      },
      {
        path: "settings",
        name: "user-settings",
        component: () => import("./pages/UserSettings.vue"),
      },
      {
        path: "shelves",
        name: "user-shelves",
        component: () => import("./pages/UserShelf.vue"),
      },
    ],
  },
  {
    path: "/admin",
    redirect: {
      name: "admin-index",
    },
    children: [
      {
        path: "index",
        name: "admin-index",
        component: () => import("./pages/Admin/AdminIndex.vue"),
      },
      {
        path: "upload",
        name: "admin-upload",
        component: () => import("./pages/Admin/AdminBookUpload.vue"),
      },
      {
        path: "books",
        name: "admin-book-list",
        component: () => import("./pages/Admin/AdminBookList.vue"),
      },
      {
        path: "books/:id",
        name: "admin-book-edit",
        component: () => import("./pages/Admin/AdminBookEdit.vue"),
      },
      {
        path: "authors",
        name: "admin-author-list",
        component: () => import("./pages/Admin/AdminAuthorList.vue"),
      },
      {
        path: "tags",
        name: "admin-tag-list",
        component: () => import("./pages/Admin/AdminTagList.vue"),
      },
      {
        path: "shelves",
        name: "admin-shelf-list",
        component: () => import("./pages/Admin/AdminShelfList.vue"),
      },
      {
        path: "series",
        name: "admin-series-list",
        component: () => import("./pages/Admin/AdminSeriesList.vue"),
      },
      {
        path: "files",
        name: "admin-file-manager",
        component: () => import("./pages/Admin/AdminFileManager.vue"),
      },
      {
        path: "settings",
        name: "system-settings",
        component: () => import("./pages/Admin/AdminSystemSettings.vue"),
      },
      {
        path: "txt/chapters/:id?",
        name: "admin-txt-chapters",
        component: () => import("./pages/Admin/AdminTxtChapters.vue"),
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// 公共权限设置缓存（避免每次路由都拉取）
let publicPerms: null | {
  allow_guest_access: boolean;
  allow_user_registration: boolean;
  allow_recover_password: boolean;
} = null;
let loadingPerms: Promise<any> | null = null;

async function ensurePublicPerms() {
  if (publicPerms) return publicPerms;
  if (!loadingPerms) {
    loadingPerms = http.get<{ permissions: typeof publicPerms }>("/api/v1/settings/public").then((res: any) => {
      publicPerms = (res?.permissions as any) || {
        allow_guest_access: true, allow_user_registration: true, allow_recover_password: true,
      };
      return publicPerms;
    }).catch(() => {
      publicPerms = { allow_guest_access: true, allow_user_registration: true, allow_recover_password: true };
      return publicPerms;
    }).finally(() => { loadingPerms = null; });
  }
  return loadingPerms;
}

// 路由守卫
router.beforeEach(async (to) => {
  const token = isLoggedIn();
  // 确保拿到公开权限配置
  await ensurePublicPerms();
  const perms = publicPerms!;

  if (!token && perms && !perms.allow_guest_access) {
    const name = String(to.name || "");
    const allow = ["login", "register", "forgot", "reset"];
    if (!allow.includes(name)) {
      return { name: "login", query: { redirect: to.fullPath } };
    }
  }

  const isAdminRoute = to.path.startsWith("/admin");
  // 未登录禁止访问 /admin
  if (isAdminRoute && !token) {
    return { name: "login", query: { redirect: to.fullPath } };
  }
  // 管理路由：要求 admin 角色
  if (isAdminRoute && token) {
    if (!isRole("admin")) {
      return { name: "home" };
    }
  }
  // 若已登录仍访问登录页，重定向到书库
  if (to.name === "login" && token) {
    return { name: "books" };
  }
});

export default router;
