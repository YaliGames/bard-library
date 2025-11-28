import axios from 'axios'

const http = axios.create({
  baseURL: '/api/v1',
  timeout: 30000,
})

// 请求拦截器
http.interceptors.request.use(
  config => {
    // 可以在这里添加请求头等
    return config
  },
  error => {
    return Promise.reject(error)
  },
)

// 响应拦截器
http.interceptors.response.use(
  response => {
    return response
  },
  error => {
    // 处理错误响应
    if (error.response?.status === 404) {
      console.warn('API endpoint not found:', error.config.url)
    }
    return Promise.reject(error)
  },
)

export default http