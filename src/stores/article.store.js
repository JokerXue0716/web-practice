import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth.store'

const STORAGE_KEY = 'articles'

function getLocalArticles() {
  return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
}

function saveLocalArticles(articles) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(articles))
}

export const useArticleStore = defineStore('article', () => {
  const articles = ref([])
  const authStore = useAuthStore()

  // 获取当前用户
  const getCurrentUser = () => {
    return authStore.user?.username || authStore.currentUser?.username || ''
  }

  // 加载本地当前用户的文章
  const loadArticles = () => {
    const all = getLocalArticles()
    const user = getCurrentUser()
    articles.value = all.filter(a => a.author === user)
  }

  // 新增或更新文章（只操作当前用户）
  const saveArticle = (article) => {
    let list = getLocalArticles()
    const user = getCurrentUser()
    if (!user) return
    article.author = user
    if (article.id) {
      // 更新
      const idx = list.findIndex(a => a.id === article.id && a.author === user)
      if (idx !== -1) {
        list[idx] = { ...list[idx], ...article, updatedAt: new Date().toISOString() }
      } else {
        list.push({ ...article, updatedAt: new Date().toISOString(), author: user })
      }
    } else {
      // 新增
      article.id = Date.now()
      article.createdAt = new Date().toISOString()
      article.updatedAt = article.createdAt
      article.author = user
      list.push({ ...article })
    }
    saveLocalArticles(list)
    articles.value = list.filter(a => a.author === user)
    return article.id
  }

  // 删除文章（只操作当前用户）
  const deleteArticle = (id) => {
    const user = getCurrentUser()
    let list = getLocalArticles().filter(a => !(a.id === id && a.author === user))
    saveLocalArticles(list)
    articles.value = list.filter(a => a.author === user)
  }

  // 获取当前用户的单篇文章
  const getArticleById = (id) => {
    const user = getCurrentUser()
    return getLocalArticles().find(a => a.id === id && a.author === user)
  }

  return {
    articles,
    loadArticles,
    saveArticle,
    deleteArticle,
    getArticleById
  }
})
